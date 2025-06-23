<?php

use GenshinTeam\Database\Database;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../constants.php';

/**
 * Classe de test pour Database.
 *
 * Vérifie le bon fonctionnement du pattern Singleton et la connexion à la base de données.
 */
class DatabaseIntegrationTest extends TestCase
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
     * Teste la connexion à la base de données avec des paramètres valides.
     *
     * @return void
     */
    public function testDatabaseConnection()
    {

        $this->assertNotEmpty(getenv('MYSQL_DATABASE'), "MYSQL_DATABASE est vide !");
        $this->assertNotEmpty(getenv('MYSQL_USER'), "MYSQL_USER est vide !");
        $this->assertNotEmpty(getenv('MYSQL_PASSWORD'), "MYSQL_PASSWORD est vide !");

        try {
            $pdo = new PDO("mysql:host=mysql-container;dbname=test_db;charset=utf8mb4", "Thierry", "Aubvu7k7");
            $this->assertNotFalse($pdo->query("SELECT 1"), "Connexion à la base réussie.");
        } catch (PDOException $e) {
            $this->fail("Échec de connexion : " . $e->getMessage());
        }
    }

    /**
     * Teste la construction du DSN et la configuration PDO.
     */
    public function testPdoConfigurationAndDsnConstruction(): void
    {
        // Réinitialise l'instance
        Database::setInstance(null);

        putenv('APP_ENV=test');

        // Configure des valeurs valides avec putenv
        file_put_contents('.env.test', 'MYSQL_DATABASE=test_db' . PHP_EOL .
            'MYSQL_USER=Thierry' . PHP_EOL .
            'MYSQL_PASSWORD=Aubvu7k7' . PHP_EOL .
            'PMA_HOST=mysql-container');

        try {
            // Tente de créer l'instance
            $pdo = Database::getInstance(testing: true);

            // Vérifie que c'est une instance PDO
            $this->assertInstanceOf(PDO::class, $pdo);

            // Vérifie les attributs PDO configurés
            $this->assertEquals(
                PDO::ERRMODE_EXCEPTION,
                $pdo->getAttribute(PDO::ATTR_ERRMODE),
                'PDO::ATTR_ERRMODE devrait être PDO::ERRMODE_EXCEPTION'
            );

            $this->assertEquals(
                PDO::FETCH_ASSOC,
                $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE),
                'PDO::ATTR_DEFAULT_FETCH_MODE devrait être PDO::FETCH_ASSOC'
            );

            // Vérifie que la connexion est fonctionnelle
            $this->assertTrue($pdo->query('SELECT 1')->fetch()[1] == 1, 'La connexion devrait être fonctionnelle');

        } catch (PDOException $e) {
            $this->fail('La connexion PDO n\'aurait pas dû échouer : ' . $e->getMessage());
        }
    }
}
