<?php
declare (strict_types = 1);

use GenshinTeam\Connexion\Database;
use GenshinTeam\Models\PasswordReset;
use GenshinTeam\Utils\MailSenderInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Classe de test unitaire pour la classe PasswordReset.
 *
 * Elle couvre les fonctionnalités liées à la vérification, l'invalidation et
 * la génération des liens de réinitialisation de mot de passe.
 *
 * @covers \GenshinTeam\Models\PasswordReset
 */
class PasswordResetTest extends TestCase
{
    /**
     * Logger simulé pour vérifier la journalisation interne.
     *
     * @var LoggerInterface
     */
    private LoggerInterface $loggerMock;

    /**
     * Simulation de l'objet PDO.
     *
     * @var \PHPUnit\Framework\MockObject\MockObject&\PDO
     */
    private $pdoMock;

    /**
     * Instance de PasswordReset à tester.
     *
     * @var PasswordReset
     */
    private PasswordReset $reset;

    /**
     * Initialise les mocks et l’instance avant chaque test.
     */
    protected function setUp(): void
    {
        $this->pdoMock    = $this->createMock(\PDO::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        /** @var \PHPUnit\Framework\MockObject\MockObject&MailSenderInterface */
        $mailerMock = $this->createMock(MailSenderInterface::class);

                                               // On injecte manuellement la connexion PDO mockée
        Database::setInstance($this->pdoMock); // à créer dans la classe Database
        $this->reset = new PasswordReset($this->loggerMock, $mailerMock);
    }

    /**
     * Vérifie que verifyToken retourne l’adresse email associée à un token valide.
     */
    public function testVerifyTokenReturnsEmail(): void
    {
        $token = 'token-test';
        /** @var \PHPUnit\Framework\MockObject\MockObject&\PDOStatement */
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([':token' => $token]);
        $stmt->expects($this->once())->method('fetch')->willReturn(['email' => 'foo@example.com']);

        $this->pdoMock->expects($this->once())->method('prepare')->willReturn($stmt);

        $this->assertSame('foo@example.com', $this->reset->verifyToken($token));
    }

    /**
     * Vérifie que verifyToken retourne null si le token est invalide ou introuvable.
     */
    public function testVerifyTokenReturnsNullOnFailure(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject&\PDOStatement */
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute');
        $stmt->method('fetch')->willReturn(false);

        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->assertNull($this->reset->verifyToken('invalid-token'));
    }

    /**
     * Vérifie que invalidateToken exécute correctement la suppression du token expiré.
     */
    public function testInvalidateTokenRunsDelete(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject&\PDOStatement */
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([':token' => 'expired']);

        $this->pdoMock->expects($this->once())->method('prepare')->willReturn($stmt);

        $this->reset->invalidateToken('expired');
    }

    /**
     * Vérifie que updateUserPassword met à jour le mot de passe de l’utilisateur.
     */
    public function testUpdateUserPassword(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject&\PDOStatement */
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([
            ':password' => 'hash123',
            ':email'    => 'user@example.com',
        ]);

        $this->pdoMock->expects($this->once())->method('prepare')->willReturn($stmt);

        $this->reset->updateUserPassword('user@example.com', 'hash123');
    }

    /**
     * Vérifie que generateResetLink insère correctement le token et appelle l'envoi de mail.
     *
     * Ce test surclasse la méthode sendResetEmail pour en capturer les paramètres sans envoi réel.
     */
    public function testGenerateResetLinkInsertsTokenAndSendsEmail(): void
    {
        $email = 'user@example.com';

        /** @var \PHPUnit\Framework\MockObject\MockObject&\PDOStatement $stmt */
        $stmt = $this->createMock(\PDOStatement::class);

        // Vérifie que la requête INSERT est bien préparée et exécutée
        $stmt->expects($this->once())
            ->method('execute')
            ->with($this->callback(
                /** @param array{':email': string, ':token': string, ':expires': string} $params */
                function (array $params) use ($email) {
                    assert(is_string($params[':token']) && is_string($params[':expires']));
                    return $params[':email'] === $email
                    && preg_match('/^[a-f0-9]{64}$/', $params[':token']) // token = 64 caractères hex
                    && strtotime($params[':expires']) > time();
                }));

        /** @var \PHPUnit\Framework\MockObject\MockObject&\PDO $pdo */
        $pdo = $this->createMock(\PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        /** @var \PHPUnit\Framework\MockObject\MockObject&LoggerInterface $logger */
        $logger = $this->createMock(LoggerInterface::class);

        // On crée une sous-classe anonyme pour surcharger sendResetEmail()
        $mock = new class($logger, $pdo) extends PasswordReset
        {
            /** @var array{email: string, token: string} $captured*/
            public array $captured = ['email' => '', 'token' => ''];

            public function __construct(LoggerInterface $logger, \PDO $pdo)
            {
                $this->logger = $logger;
                $this->pdo    = $pdo;
            }

            protected function sendResetEmail(string $email, string $token): void
            {
                // On capture les valeurs sans envoyer de mail
                $this->captured = ['email' => $email, 'token' => $token];
            }
        };

        $mock->generateResetLink($email);

        // On vérifie que sendResetEmail a bien été appelée avec le bon email et un token valide
        $this->assertSame($email, $mock->captured['email']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $mock->captured['token']);
    }

    /**
     * Vérifie que sendResetEmail utilise correctement le MailSenderInterface pour envoyer un mail.
     */
    public function testSendResetEmailCallsMailer(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $pdo    = $this->createMock(PDO::class);

        $mailer = $this->createMock(MailSenderInterface::class);
        $mailer->expects($this->once())
            ->method('sendPasswordReset')
            ->with('user@example.com', 'token123');

        // On injecte manuellement toutes les dépendances
        $reset = new class($logger, $mailer, $pdo) extends PasswordReset
        {
            public function __construct(LoggerInterface $logger, MailSenderInterface $mailer, \PDO $pdo)
            {
                $this->pdo    = $pdo;
                $this->logger = $logger;
                $this->mailer = $mailer;
            }

            public function callSendResetEmail(string $email, string $token): void
            {
                $this->sendResetEmail($email, $token);
            }
        };

        $reset->callSendResetEmail('user@example.com', 'token123');
    }
}
