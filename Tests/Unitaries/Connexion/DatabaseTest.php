<?php

use GenshinTeam\Database\Database;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../constants.php';

/**
 * Classe de test pour Database.
 *
 * Vérifie le bon fonctionnement du pattern Singleton et la connexion à la base de données.
 */
class DatabaseTest extends TestCase
{

    protected function setUp(): void
    {
        // Créer une connexion SQLite temporaire
        $pdoMock = new PDO('sqlite::memory:');
        $pdoMock->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Injecter la connexion SQLite dans Database
        Database::setInstance($pdoMock);
    }

    /**
     * Teste que l'instance de PDO est bien créée et respecte le pattern Singleton.
     *
     * @return void
     */
    public function testSingletonInstance()
    {
        $instance1 = Database::getInstance(exit:false, testing: true);
        $instance2 = Database::getInstance(exit:false, testing: true);

        // Vérifie que les deux instances sont identiques (Singleton)
        $this->assertSame($instance1, $instance2, "Database doit respecter le pattern Singleton.");
        $this->assertInstanceOf(PDO::class, $instance1, "Database doit retourner une instance de PDO.");
    }

    /**
     * Teste l'injection d'une instance PDO personnalisée (utile pour les tests).
     *
     * @return void
     */
    public function testSetInstance()
    {
        $pdoMock = new PDO('sqlite::memory:'); // Utilisation d'une base SQLite en mémoire
        Database::setInstance($pdoMock);

        $instance = Database::getInstance(exit:false, testing: true);

        $this->assertSame($pdoMock, $instance, "L'instance PDO injectée doit être identique.");
    }

    /**
     * Vérifie que la méthode loadEnv() charge correctement le fichier
     * d'environnement destiné aux tests (.env.test) lorsque APP_ENV est défini sur "test".
     *
     * La méthode force l'environnement de test, puis utilise la réflexion pour invoquer
     * la méthode privée statique loadEnv() de la classe Database. Le test vérifie ensuite
     * que la variable d'environnement MYSQL_DATABASE a bien été définie à "test_db",
     * indiquant que le fichier .env.test a bien été chargé.
     *
     * @runInSeparateProcess
     * @return void
     *
     * @covers \Database::loadEnv
     */
    public function testLoadEnvLoadsTestEnvFileWhenAppEnvIsTest(): void
    {
        // Forcer l'environnement de test
        putenv('APP_ENV=test');

        // Utiliser la réflexion pour appeler la méthode privée statique loadEnv() depuis la classe Database (remplacez si nécessaire)
        $refClass = new ReflectionClass(Database::class);
        $method   = $refClass->getMethod('loadEnv');
        $method->setAccessible(true);
        $method->invoke(null);

        // Vérifier que MYSQL_DATABASE est défini à partir du fichier .env.test
        $this->assertEquals('test_db', getenv('MYSQL_DATABASE'),
            "La variable MYSQL_DATABASE devrait être définie à 'test_db' depuis le fichier .env.test.");
    }

    /**
     * Vérifie que `getInstance` retourne bien une instance de `PDO`.
     *
     * @covers \GenshinTeam\Database\Database::getInstance
     */
    public function testGetInstanceReturnsPdo(): void
    {
        /** @var PDO */
        $pdo = Database::getInstance();

        assert($pdo instanceof PDO); // Vérification stricte pour PHPStan niveau 10

        $this->assertInstanceOf(PDO::class, $pdo, "getInstance() devrait retourner une instance de PDO.");
    }

    public function testGetInstanceThrowsExceptionOnEmptyDatabase(): void
    {
        // Réinitialise l'instance
        Database::setInstance(null);

        // configure un environnement de test avec une base de données vide
        file_put_contents('.env.test', 'MYSQL_DATABASE=');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('La variable d\'environnement MYSQL_DATABASE est invalide.');

        Database::getInstance(testing: true);
    }

    /**
     * Teste que getInstance() lève une exception lorsque MYSQL_USER est invalide.
     *
     * @return void
     *
     * @covers \GenshinTeam\Database\Database::getInstance
     */
    public function testGetInstanceThrowsExceptionOnInvalidUser(): void
    {
        // Réinitialise l'instance
        Database::setInstance(null);

        // Configure un environnement de test avec un utilisateur vide
        file_put_contents('.env.test', 'MYSQL_DATABASE=test_db' . PHP_EOL .
            'MYSQL_USER=');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('La variable d\'environnement MYSQL_USER est invalide.');

        Database::getInstance(testing: true);
    }

    /**
     * Teste que getInstance() lève une exception lorsque PMA_HOST est invalide.
     *
     * @return void
     *
     * @covers \GenshinTeam\Database\Database::getInstance
     */
    public function testGetInstanceThrowsExceptionOnInvalidHost(): void
    {

        // Réinitialise l'instance
        Database::setInstance(null);

        // Configure un environnement de test avec un hôte vide
        file_put_contents('.env.test', 'MYSQL_DATABASE=test_db' . PHP_EOL .
            'MYSQL_USER=test_user' . PHP_EOL .
            'MYSQL_PASSWORD=test_pass' . PHP_EOL .
            'PMA_HOST=');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('La variable d\'environnement PMA_HOST est invalide.');

        Database::getInstance(testing: true);
    }

    /**
     * Teste que getInstance() lève une exception lorsque MYSQL_PASSWORD est invalide.
     *
     * @return void
     *
     * @covers \GenshinTeam\Database\Database::getInstance
     */
    public function testGetInstanceThrowsExceptionOnInvalidPassword(): void
    {

        // Réinitialise l'instance
        Database::setInstance(null);

        // Configure un environnement de test avec un mot de passe vide
        file_put_contents('.env.test', 'MYSQL_DATABASE=test_db' . PHP_EOL .
            'MYSQL_USER=test_user' . PHP_EOL .
            'MYSQL_PASSWORD=' . PHP_EOL .
            'PMA_HOST=localhost');

        // Simule un mot de passe invalide
        putenv('MYSQL_PASSWORD=');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('La variable d\'environnement MYSQL_PASSWORD est invalide.');

        Database::getInstance(testing: true);
    }

}
