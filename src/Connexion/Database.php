<?php
declare (strict_types = 1);

namespace GenshinTeam\Connexion;

use Dotenv\Dotenv;
use Exception;
use PDO;
use RuntimeException;

/**
 * Gère la connexion à la base de données via PDO en mode Singleton.
 *
 * Cette classe charge les variables d'environnement via Dotenv
 * et fournit une instance PDO unique.
 *
 * @package GenshinTeam\Connexion
 */
class Database
{
    /**
     * Instance unique de PDO.
     *
     * @var PDO|null
     */
    private static ?PDO $instance = null;

    /**
     * Empêche l'instanciation de la classe (pattern Singleton).
     */
    private function __construct()
    {
    }

    /**
     * Charge les variables d'environnement à partir du fichier .env adapté.
     *
     * @return void
     */
    private static function loadEnv(): void
    {
        $envFile = (getenv('APP_ENV') === 'test') ? '.env.test' : '.env';
        $dotenv  = Dotenv::createMutable(PROJECT_ROOT, $envFile);
        $dotenv->load();
    }

    /**
     * Retourne l'instance PDO unique, en l'initialisant si nécessaire.
     *
     * @param bool $exit    Non utilisé — conservé pour rétrocompatibilité éventuelle.
     * @param bool $testing Active le mode test pour désactiver les erreurs visibles.
     *
     * @return PDO
     *
     * @throws RuntimeException Si la connexion échoue ou les paramètres sont invalides.
     */
    public static function getInstance(bool $exit = true, bool $testing = false): PDO
    {
        if (self::$instance === null) {
            try {
                self::loadEnv();
                $params         = self::getDatabaseParams();
                self::$instance = self::createPDOInstance($params);
            } catch (Exception | RuntimeException $e) {
                // Laisse l'erreur remonter pour être gérée par un gestionnaire central
                throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
            }
        }

        return self::$instance;
    }

    /**
     * Récupère et valide les paramètres de connexion à partir des variables d'environnement.
     *
     * @return array{database: string, username: string, password: string, host: string}
     *
     * @throws Exception Si une variable requise est absente ou invalide.
     */
    private static function getDatabaseParams(): array
    {
        $requiredVars = [
            'MYSQL_DATABASE',
            'MYSQL_USER',
            'MYSQL_PASSWORD',
            'PMA_HOST',
        ];

        $params = [];

        foreach ($requiredVars as $var) {
            if (! isset($_ENV[$var]) || ! is_string($_ENV[$var]) || $_ENV[$var] === '') {
                throw new Exception("La variable d'environnement {$var} est invalide ou manquante.");
            }
            $params[$var] = $_ENV[$var];
        }

        return [
            'database' => $params['MYSQL_DATABASE'],
            'username' => $params['MYSQL_USER'],
            'password' => $params['MYSQL_PASSWORD'],
            'host'     => $params['PMA_HOST'],
        ];
    }

    /**
     * Crée une nouvelle instance PDO avec les paramètres fournis.
     *
     * @param array{database: string, username: string, password: string, host: string} $params
     *
     * @return PDO
     */
    private static function createPDOInstance(array $params): PDO
    {
        $dsn = "mysql:host={$params['host']};dbname={$params['database']};charset=utf8mb4";

        return new PDO(
            $dsn,
            $params['username'],
            $params['password'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    /**
     * Injecte une instance personnalisée de PDO (principalement pour les tests).
     *
     * @param PDO|null $pdo
     *
     * @return void
     */
    public static function setInstance(?PDO $pdo): void
    {
        self::$instance = $pdo;
    }
}
