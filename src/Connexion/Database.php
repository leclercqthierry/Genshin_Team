<?php
declare (strict_types = 1);

namespace GenshinTeam\Database;

use Dotenv\Dotenv;
use Exception;
use PDO;
use RuntimeException;

/**
 * Class Database
 *
 * Cette classe gère la connexion à la base de données en implémentant le pattern Singleton.
 * Elle utilise Dotenv pour charger les variables d'environnement depuis un fichier .env
 * et établit la connexion via PDO.
 *
 * En cas d'erreur (variable d'environnement manquante ou échec de connexion), l'erreur est
 * loguée et une vue d'erreur générique est affichée grâce au gestionnaire d'erreur injecté.
 *
 * @package GenshinTeam\Database
 */
class Database
{
    /**
     * Instance statique de PDO pour garantir une unique connexion à la base de données.
     *
     * @var PDO|null
     */
    private static ?PDO $instance = null;

    /**
     * Constructeur privé pour empêcher l'instanciation externe.
     */
    private function __construct()
    {}

    /**
     * Charge le fichier .env (s'il n'est pas déjà chargé).
     *
     * Choisit le fichier en fonction de l'environnement (test ou autre).
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
     * Retourne l'instance PDO, en créant la connexion si nécessaire.
     *
     * Implémente le pattern Singleton en créant une unique connexion. En cas d'erreur,
     * l'exception est gérée via le gestionnaire d'erreur injecté (sauf en mode test).
     *
     * @param bool $exit    Si true, interrompt l'exécution en cas d'erreur.
     * @param bool $testing Si true, désactive le traitement de l'erreur pour éviter des affichages indésirables.
     *
     * @return PDO L'instance PDO utilisée pour interagir avec la base de données.
     */
    public static function getInstance(bool $exit = true, bool $testing = false): PDO
    {
        if (self::$instance === null) {
            try {
                self::loadEnv();
                $params         = self::getDatabaseParams();
                self::$instance = self::createPDOInstance($params);
            } catch (Exception | RuntimeException $e) {
                // On laisse l'exception remonter, elle sera gérée par ErrorHandler plus haut
                throw new RuntimeException($e->getMessage(), $e->getCode(), $e);

            }
        }

        return self::$instance;
    }

    /**
     * Récupère et valide les paramètres de connexion à la base de données.
     */
    private static function getDatabaseParams(): array
    {
        $params = [
            'database' => $_ENV['MYSQL_DATABASE'] ?? '',
            'username' => $_ENV['MYSQL_USER'] ?? '',
            'password' => $_ENV['MYSQL_PASSWORD'] ?? '',
            'host'     => $_ENV['PMA_HOST'] ?? 'localhost',
        ];

        $envNames = [
            'database' => 'MYSQL_DATABASE',
            'username' => 'MYSQL_USER',
            'password' => 'MYSQL_PASSWORD',
            'host'     => 'PMA_HOST',
        ];

        foreach ($params as $key => $value) {
            if (! is_string($value) || empty($value)) {
                throw new Exception("La variable d'environnement {$envNames[$key]} est invalide.");
            }
        }

        return $params;
    }

    /**
     * Crée et retourne une instance PDO.
     */
    private static function createPDOInstance(array $params): PDO
    {
        $dsn = "mysql:host={$params['host']};dbname={$params['database']};charset=utf8mb4";

        return new PDO(
            $dsn,
            $params['username'],
            $params['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }

    /**
     * Permet d'injecter une instance PDO pour les tests unitaires.
     *
     * @param PDO|null $pdo Instance PDO à utiliser.
     *
     * @return void
     */
    public static function setInstance(?PDO $pdo): void
    {
        self::$instance = $pdo;
    }
}
