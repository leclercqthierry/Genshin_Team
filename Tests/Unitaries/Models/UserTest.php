<?php
declare (strict_types = 1);

use GenshinTeam\Database\Database;
use GenshinTeam\Models\User;
use GenshinTeam\Utils\ErrorHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../constants.php';

/**
 * Classe de test pour User.
 *
 * Vérifie le bon fonctionnement des méthodes d'interaction avec la base de données.
 */
class UserTest extends TestCase
{
    /**
     * Instance du modèle User à tester.
     *
     * @var User
     */
    private User $user;

    /**
     * Instance PDO pour base SQLite temporaire.
     *
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Chemin vers le fichier de log utilisé pour les tests d'erreurs.
     *
     * @var string
     */
    private string $errorLogFile;

    /**
     * Prépare l'environnement de test avant chaque exécution.
     *
     * - Crée une connexion SQLite en mémoire et configure la connexion.
     * - Crée la table temporaire.
     * - Injecte la connexion simulée dans Database ainsi qu'un gestionnaire d'erreur.
     * - Nettoie le fichier de log d'erreurs.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Création d'une connexion SQLite en mémoire
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Création de la table temporaire pour les tests
        $this->pdo->exec("
            CREATE TABLE zell_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nickname TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                id_role INTEGER NOT NULL
            )
        ");

        // Injection de la connexion simulée dans Database
        Database::setInstance($this->pdo);

        // Injection du gestionnaire d'erreur dans Database
        Database::setErrorHandler(new ErrorHandler());

        // Définir le chemin du fichier de log (pour ces tests, on utilise le log de production par défaut)
        $this->errorLogFile = PROJECT_ROOT . '/logs/error.log';
        if (file_exists($this->errorLogFile)) {
            file_put_contents($this->errorLogFile, '');
        }

        // Instanciation du modèle User
        $this->user = new User();
    }

    /**
     * Teste l'insertion d'un nouvel utilisateur.
     *
     * @return void
     */
    public function testCreateUser(): void
    {
        $result = $this->user->createUser('TestUser', 'test@example.com', 'hashed_password');
        $this->assertTrue($result, "L'utilisateur aurait dû être inséré en base.");

        // Vérifier l'insertion en base
        $stmt     = $this->pdo->query("SELECT * FROM zell_users WHERE email = 'test@example.com'");
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($userData, "L'utilisateur devrait exister.");
        $this->assertEquals('TestUser', $userData['nickname']);
        $this->assertEquals('hashed_password', $userData['password']);
    }

    /**
     * Teste la récupération d'un utilisateur par pseudo.
     *
     * @return void
     */
    public function testGetUserByNickname(): void
    {
        // Ajout d'un utilisateur fictif
        $this->pdo->exec("
            INSERT INTO zell_users (nickname, email, password, id_role)
            VALUES ('ExistingUser', 'existing@example.com', 'hashed_password', 2)
        ");

        $user = $this->user->getUserByNickname('ExistingUser');
        $this->assertNotNull($user, "L'utilisateur aurait dû être trouvé.");
        $this->assertEquals('existing@example.com', $user['email']);
    }

    /**
     * Teste la récupération d'un utilisateur par email.
     *
     * @return void
     */
    public function testGetUserByEmail(): void
    {
        // Ajout d'un utilisateur fictif
        $this->pdo->exec("
            INSERT INTO zell_users (nickname, email, password, id_role)
            VALUES ('AnotherUser', 'another@example.com', 'hashed_password', 2)
        ");

        $user = $this->user->getUserByEmail('another@example.com');
        $this->assertNotNull($user, "L'utilisateur aurait dû être trouvé.");
        $this->assertEquals('AnotherUser', $user['nickname']);
    }

    /**
     * Teste que l'invocation de getUserByEmail avec une table manquante logge bien une exception.
     *
     * @return void
     */
    public function testErrorHandlerLogsExceptionForGetUserByEmail(): void
    {
        $this->pdo->exec("DROP TABLE zell_users");

        // Capture de l'output et de l'exception éventuelle
        ob_start();
        try {
            $this->user->getUserByEmail('nonexistent@example.com');
        } catch (\Throwable $e) {
            // Exception volontairement attrapée pour poursuivre le test
        }
        ob_end_clean();

        $this->assertFileExists($this->errorLogFile);
        $logContent = file_get_contents($this->errorLogFile);
        $this->assertStringContainsString('SQLSTATE[HY000]', $logContent);

    }

    /**
     * Teste que l'invocation de createUser avec une table manquante logge bien une exception.
     *
     * @return void
     */
    public function testErrorHandlerLogsExceptionForCreateUser(): void
    {

        // Supprime la table afin de provoquer une erreur
        $this->pdo->exec("DROP TABLE zell_users");

        // On capture l'output pour éviter que l'affichage d'erreur n'affecte le test
        ob_start();
        try {
            $this->user->createUser('TestUser', 'test@example.com', 'hashed_password');
        } catch (\Throwable $e) {
            // Exception volontairement attrapée pour poursuivre le test
        }
        ob_end_clean();

        $this->assertFileExists($this->errorLogFile);
        $logContent = file_get_contents($this->errorLogFile);
        $this->assertStringContainsString('SQLSTATE[HY000]', $logContent);
    }

    /**
     * Teste que l'invocation de getUserByNickname avec une table manquante logge bien une exception.
     *
     * @return void
     */
    public function testErrorHandlerLogsExceptionForGetUserByNickname(): void
    {
        $this->pdo->exec("DROP TABLE zell_users");

        // Capture de l'output et de l'exception éventuelle
        ob_start();
        try {
            $this->user->getUserByNickname('NonExistentUser');
        } catch (\Throwable $e) {
            // Exception volontairement attrapée pour poursuivre le test
        }
        ob_end_clean();

        $this->assertFileExists($this->errorLogFile);
        $logContent = file_get_contents($this->errorLogFile);
        $this->assertStringContainsString('SQLSTATE[HY000]', $logContent);
    }

}
