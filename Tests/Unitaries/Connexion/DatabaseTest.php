<?php

use GenshinTeam\Connexion\Database;
use PHPUnit\Framework\TestCase;

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
class DatabaseTest extends TestCase
{
    /**
     * Configure une instance PDO SQLite en mémoire pour injection dans Database.
     *
     * @return void
     */
    protected function setUp(): void
    {
        /** @var \PDO $pdoMock */
        $pdoMock = new PDO('sqlite::memory:');
        $pdoMock->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        Database::setInstance($pdoMock);
    }

    /**
     * Teste que Database retourne toujours la même instance (Singleton).
     *
     * @return void
     */
    public function testSingletonInstance(): void
    {
        /** @var \PDO $instance1 */
        $instance1 = Database::getInstance(exit:false, testing: true);

        /** @var \PDO $instance2 */
        $instance2 = Database::getInstance(exit:false, testing: true);

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
        $instance = Database::getInstance(exit:false, testing: true);

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
            'MYSQL_USER=test_user',
            'MYSQL_PASSWORD=test_pass',
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
            'MYSQL_USER=test_user',
            'MYSQL_PASSWORD=',
            'PMA_HOST=localhost',
        ]));

        putenv('MYSQL_PASSWORD=');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("La variable d'environnement MYSQL_PASSWORD est invalide ou manquante.");

        Database::getInstance(testing: true);
    }
}
