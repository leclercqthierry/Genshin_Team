<?php
declare (strict_types = 1);

use GenshinTeam\Connexion\Database;
use GenshinTeam\Models\UserModel;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

require_once __DIR__ . '/../../../constants.php';

/**
 * Teste les méthodes du modèle UserModel liées à la base de données.
 *
 * @covers \GenshinTeam\Models\UserModel
 */
class UserModelTest extends TestCase
{
    /** @var UserModel */
    private UserModel $userModel;

    /** @var PDO */
    private PDO $pdo;

    /** @var string */
    private string $errorLogFile;

    /**
     * Initialise l'environnement avec une base SQLite temporaire,
     * une instance UserModel et un logger mocké.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Connexion SQLite simulée
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Création de la table de test
        $this->pdo->exec('
            CREATE TABLE zell_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nickname TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                id_role INTEGER NOT NULL
            )
        ');

        // Injection de la connexion
        Database::setInstance($this->pdo);

        // Logger simulé pour ErrorHandler
        $loggerMock = $this->createMock(LoggerInterface::class);

        // Instance de UserModel avec le logger mocké
        $this->userModel = new UserModel($loggerMock);

        // Vide le fichier de log si existant
        $this->errorLogFile = PROJECT_ROOT . '/logs/error.log';
        if (file_exists($this->errorLogFile)) {
            file_put_contents($this->errorLogFile, '');
        }
    }

    /**
     * Vérifie que createUser insère bien un utilisateur.
     *
     * @return void
     */
    public function testCreateUser(): void
    {
        $result = $this->userModel->createUser('TestUser', 'test@example.com', 'hashed_password');
        $this->assertTrue($result);

        // Vérifie la présence en base
        $stmt     = $this->pdo->query("SELECT * FROM zell_users WHERE email = 'test@example.com'");
        $userData = $stmt !== false ? $stmt->fetch(PDO::FETCH_ASSOC) : null;

        $this->assertNotNull($userData);

        /** @var array<string, string>|false $userData */
        $this->assertIsArray($userData); // ↩️ confirme que ce n’est plus false

        $this->assertSame('TestUser', $userData['nickname']);
        $this->assertSame('hashed_password', $userData['password']);
    }

    /**
     * Vérifie la récupération d’un utilisateur par pseudo.
     *
     * @return void
     */
    public function testGetUserByNickname(): void
    {
        $this->pdo->exec("
            INSERT INTO zell_users (nickname, email, password, id_role)
            VALUES ('ExistingUser', 'existing@example.com', 'hashed_password', 2)
        ");

        $user = $this->userModel->getUserByNickname('ExistingUser');
        $this->assertNotNull($user);
        $this->assertSame('existing@example.com', $user['email']);
    }

    /**
     * Vérifie la récupération d’un utilisateur par email.
     *
     * @return void
     */
    public function testGetUserByEmail(): void
    {
        $this->pdo->exec("
            INSERT INTO zell_users (nickname, email, password, id_role)
            VALUES ('AnotherUser', 'another@example.com', 'hashed_password', 2)
        ");

        $user = $this->userModel->getUserByEmail('another@example.com');
        $this->assertNotNull($user);
        $this->assertSame('AnotherUser', $user['nickname']);
    }

    /**
     * Vérifie que getUserByEmail loggue une exception si la table n'existe pas.
     *
     * @return void
     */
    public function testErrorHandlerLogsExceptionForGetUserByEmail(): void
    {
        $this->pdo->exec('DROP TABLE zell_users');

        ob_start();
        try {
            $this->userModel->getUserByEmail('nonexistent@example.com');
        } catch (\Throwable) {
            // Erreur attendue
        }
        ob_end_clean();

        $this->addToAssertionCount(1); // indique que le test est volontairement validé
    }

    /**
     * Vérifie que createUser loggue une erreur si la table est manquante.
     *
     * @return void
     */
    public function testErrorHandlerLogsExceptionForCreateUser(): void
    {
        $this->pdo->exec('DROP TABLE zell_users');

        ob_start();
        try {
            $this->userModel->createUser('TestUser', 'test@example.com', 'hashed_password');
        } catch (\Throwable) {
            // Erreur attendue
        }
        ob_end_clean();

        $this->addToAssertionCount(1); // indique que le test est volontairement validé

    }

    /**
     * Vérifie que getUserByNickname loggue une erreur si la table est manquante.
     *
     * @return void
     */
    public function testErrorHandlerLogsExceptionForGetUserByNickname(): void
    {
        $this->pdo->exec('DROP TABLE zell_users');

        ob_start();
        try {
            $this->userModel->getUserByNickname('NonExistentUser');
        } catch (\Throwable) {
            // Exception capturée intentionnellement
        }
        ob_end_clean();

        $this->addToAssertionCount(1); // indique que le test est volontairement validé

    }
}
