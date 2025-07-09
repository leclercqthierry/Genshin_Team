<?php

use GenshinTeam\Connexion\Database;
use Tests\TestCase\DatabaseTestCase;

/**
 * Tests unitaires de la classe Database.
 *
 * Vérifie :
 * - le respect du pattern Singleton,
 * - le bon comportement de l’injection manuelle d’une instance PDO,
 * - le chargement correct des fichiers .env selon l’environnement,
 * - la gestion des erreurs de configuration via les variables d’environnement.
 *
 * @covers \GenshinTeam\Connexion\Database
 */
class DatabaseTest extends DatabaseTestCase
{
    /**
     * Teste que Database retourne toujours la même instance (Singleton).
     *
     * @return void
     */
    public function testSingletonInstance(): void
    {
        /** @var \PDO $instance1 */
        $instance1 = Database::getInstance(exit: false, testing: true);

        /** @var \PDO $instance2 */
        $instance2 = Database::getInstance(exit: false, testing: true);

        $this->assertSame($instance1, $instance2, 'L’instance doit respecter le pattern Singleton.');
        $this->assertInstanceOf(PDO::class, $instance1);
    }

    /**
     * Vérifie que l’injection manuelle d’un PDO personnalisé est respectée.
     *
     * @return void
     */
    public function testSetInstance(): void
    {
        /** @var \PDO $pdoMock */
        $pdoMock = new PDO('sqlite::memory:');
        Database::setInstance($pdoMock);

        /** @var \PDO $instance */
        $instance = Database::getInstance(exit: false, testing: true);

        $this->assertSame($pdoMock, $instance);
    }

    /**
     * Vérifie que le fichier `.env.test` est bien chargé si APP_ENV vaut "test".
     *
     * @runInSeparateProcess
     * @return void
     *
     * @covers \GenshinTeam\Connexion\Database::loadEnv
     */
    public function testLoadEnvLoadsTestEnvFileWhenAppEnvIsTest(): void
    {
        putenv('APP_ENV=test');

        $refClass = new ReflectionClass(Database::class);
        $method   = $refClass->getMethod('loadEnv');
        $method->setAccessible(true);
        $method->invoke(null);

        $this->assertSame('test_db', getenv('MYSQL_DATABASE'));
    }

    /**
     * Vérifie que getInstance() retourne un PDO valide.
     *
     * @return void
     *
     * @covers \GenshinTeam\Connexion\Database::getInstance
     */
    public function testGetInstanceReturnsPdo(): void
    {
        /** @var \PDO $pdo */
        $pdo = Database::getInstance();
        $this->assertInstanceOf(PDO::class, $pdo);
    }

    /**
     * Teste que l’absence de MYSQL_DATABASE déclenche une exception.
     *
     * @return void
     */
    public function testGetInstanceThrowsExceptionOnEmptyDatabase(): void
    {
        Database::setInstance(null);
        file_put_contents('.env.test', 'MYSQL_DATABASE=');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("La variable d'environnement MYSQL_DATABASE est invalide ou manquante.");

        Database::getInstance(testing: true);
    }

    /**
     * Teste que l’absence de MYSQL_USER déclenche une exception.
     *
     * @return void
     *
     * @covers \GenshinTeam\Connexion\Database::getInstance
     */
    public function testGetInstanceThrowsExceptionOnInvalidUser(): void
    {
        Database::setInstance(null);
        file_put_contents('.env.test', "MYSQL_DATABASE=test_db\nMYSQL_USER=");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("La variable d'environnement MYSQL_USER est invalide ou manquante.");

        Database::getInstance(testing: true);
    }

    /**
     * Teste que l’absence de PMA_HOST déclenche une exception.
     *
     * @return void
     */
    public function testGetInstanceThrowsExceptionOnInvalidHost(): void
    {
        Database::setInstance(null);
        file_put_contents('.env.test', implode(PHP_EOL, [
            'MYSQL_DATABASE=test_db',
            'MYSQL_USER=Thierry',
            'MYSQL_PASSWORD=Aubvu7k7',
            'PMA_HOST=',
        ]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("La variable d'environnement PMA_HOST est invalide ou manquante.");

        Database::getInstance(testing: true);
    }

    /**
     * Teste que l’absence de MYSQL_PASSWORD déclenche une exception.
     *
     * @return void
     */
    public function testGetInstanceThrowsExceptionOnInvalidPassword(): void
    {
        Database::setInstance(null);
        file_put_contents('.env.test', implode(PHP_EOL, [
            'MYSQL_DATABASE=test_db',
            'MYSQL_USER=Thierry',
            'MYSQL_PASSWORD=',
            'PMA_HOST=mysql-container',
        ]));

        putenv('MYSQL_PASSWORD=');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("La variable d'environnement MYSQL_PASSWORD est invalide ou manquante.");

        Database::getInstance(testing: true);
    }

    /**
     * Teste l'accessibilité du constructeur privé de la classe Database
     * en contournant son accessibilité via Reflection.
     *
     * Ce test vérifie que l'instanciation manuelle via Reflection fonctionne
     * correctement même lorsque le constructeur est privé.
     *
     * @covers \GenshinTeam\Connexion\Database::__construct
     * @return void
     */
    public function testPrivateConstructorCanBeInvoked(): void
    {
        $refClass = new ReflectionClass(Database::class);

        // Instanciation sans passer par le constructeur
        $instance = $refClass->newInstanceWithoutConstructor();

        // Appel manuel du constructeur privé
        $constructor = $refClass->getConstructor();
        if ($constructor !== null) {
            $constructor->setAccessible(true);
            $constructor->invoke($instance);
        }

        // Assertion de type ou autre test logique
        $this->assertInstanceOf(Database::class, $instance);
    }

}
