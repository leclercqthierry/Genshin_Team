<?php

use GenshinTeam\Connexion\Database;
use PHPUnit\Framework\TestCase;

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
class DatabaseIntegrationTest extends TestCase
{
    /**
     * Configure une instance PDO SQLite en mémoire à injecter dans Database
     * pour les tests indépendants de toute connexion réelle à MySQL.
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
     * Vérifie que les variables d’environnement nécessaires à la connexion MySQL
     * sont bien définies, et qu'une connexion réelle est possible.
     *
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
     * Teste la méthode getInstance() de la classe Database en environnement test,
     * vérifie les attributs de connexion PDO définis et la validité de la connexion.
     *
     * @return void
     */
    public function testPdoConfigurationAndDsnConstruction(): void
    {
        Database::setInstance(null); // Reset instance singleton
        putenv('APP_ENV=test');

        file_put_contents('.env.test', implode(PHP_EOL, [
            'MYSQL_DATABASE=test_db',
            'MYSQL_USER=Thierry',
            'MYSQL_PASSWORD=Aubvu7k7',
            'PMA_HOST=mysql-container',
        ]));

        try {
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

        } catch (PDOException $e) {
            $this->fail('La connexion PDO n’aurait pas dû échouer : ' . $e->getMessage());
        }
    }
}
