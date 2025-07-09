<?php

use GenshinTeam\Connexion\Database;
use Tests\TestCase\DatabaseTestCase;

/**
 * Test d’intégration de la classe Database.
 *
 * Cette classe vérifie :
 * - la configuration correcte de l’instance PDO (attributs, DSN),
 * - la validité de la connexion à la base de données via PDO,
 * - le comportement du pattern Singleton implémenté par Database.
 *
 * @covers \GenshinTeam\Connexion\Database
 */
class DatabaseIntegrationTest extends DatabaseTestCase
{

    /**
     * Vérifie que les variables d’environnement nécessaires à la connexion MySQL
     * sont bien définies, et qu'une connexion réelle est possible.
     *
     * @group integration
     * @return void
     */
    public function testDatabaseConnection(): void
    {
        $this->assertNotEmpty(getenv('MYSQL_DATABASE'), 'MYSQL_DATABASE est vide !');
        $this->assertNotEmpty(getenv('MYSQL_USER'), 'MYSQL_USER est vide !');
        $this->assertNotEmpty(getenv('MYSQL_PASSWORD'), 'MYSQL_PASSWORD est vide !');

        try {
            /** @var \PDO $pdo */
            $pdo = new PDO(
                'mysql:host=mysql-container;dbname=test_db;charset=utf8mb4',
                'Thierry',
                'Aubvu7k7'
            );

            /** @var \PDOStatement $stmt */
            $stmt = $pdo->query('SELECT 1');
            $this->assertNotFalse($stmt, 'Connexion à la base réussie.');
        } catch (PDOException $e) {
            $this->fail('Échec de connexion : ' . $e->getMessage());
        }
    }

    /**
     * Vérifie que Database::getInstance() retourne un PDO correctement configuré.
     *
     * @return void
     */
    public function testPdoConfigurationAndDsnConstruction(): void
    {
        Database::setInstance(null); // Reset instance singleton

        /** @var \PDO $pdo */
        $pdo = Database::getInstance(testing: true);

        $this->assertInstanceOf(PDO::class, $pdo);

        $this->assertSame(
            PDO::ERRMODE_EXCEPTION,
            $pdo->getAttribute(PDO::ATTR_ERRMODE),
            'Le mode d’erreur attendu est PDO::ERRMODE_EXCEPTION'
        );

        $this->assertSame(
            PDO::FETCH_ASSOC,
            $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE),
            'Le mode de fetch attendu est PDO::FETCH_ASSOC'
        );

        /** @var \PDOStatement $stmt */
        // En effet PDO est configuré pour renvoyer une PDOException en cas d'erreur qui sera capturé dans le catch

        $stmt = $pdo->query('SELECT 1');
        $this->assertNotFalse($stmt, 'Requête SELECT 1 devrait réussir.');
    }
}
