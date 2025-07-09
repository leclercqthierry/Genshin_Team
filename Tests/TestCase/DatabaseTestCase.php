<?php

declare (strict_types = 1);

namespace Tests\TestCase;

use GenshinTeam\Connexion\Database;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Classe de base pour les tests impliquant une configuration de base de données.
 *
 * Cette classe assure un environnement de test propre en simulant :
 * - la présence d’un fichier `.env.test` dynamique adapté aux tests,
 * - une variable d’environnement `APP_ENV=test` pour forcer le mode test,
 * - une connexion PDO SQLite en mémoire pour éviter les dépendances réelles à MySQL,
 * - l’injection de cette connexion dans l’instance singleton de Database.
 *
 * Elle automatise également le nettoyage de l’environnement après chaque test pour
 * garantir l’isolation et éviter les effets de bord entre classes de test.
 */
abstract class DatabaseTestCase extends TestCase
{

    /**
     * Nom du fichier `.env.test` temporaire utilisé pour simuler l’environnement de test.
     *
     * @var string
     */
    protected string $testEnvFile = '.env.test';

    /**
     * Connexion PDO en mémoire utilisée pour les tests.
     *
     * @var \PDO
     */
    protected PDO $pdo;

    /**
     * Prépare l’environnement de test :
     * - crée un fichier `.env.test` temporaire,
     * - définit la variable d’environnement `APP_ENV`,
     * - instancie un PDO SQLite,
     * - injecte l’instance dans Database.
     *
     * @return void
     */
    protected function setUp(): void
    {
        if (! defined('PROJECT_ROOT')) {
            define('PROJECT_ROOT', __DIR__ . '/../..');
        }

        // Force le bon contexte  (ici test et non production)
        putenv('APP_ENV=test');

        // Crée dynamiquement le .env.test
        file_put_contents($this->testEnvFile, implode(PHP_EOL, [
            'MYSQL_DATABASE=test_db',
            'MYSQL_USER=Thierry',
            'MYSQL_PASSWORD=Aubvu7k7',
            'PMA_HOST=mysql-container',
        ]));

        // Injection d’un PDO SQLite pour les tests unitaires
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        Database::setInstance($this->pdo);
    }

    /**
     * Nettoie l’environnement de test :
     * - supprime le fichier `.env.test`,
     * - réinitialise `APP_ENV` et l’instance Database.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        @unlink($this->testEnvFile);
        putenv('APP_ENV');
        Database::setInstance(null);
    }
}
