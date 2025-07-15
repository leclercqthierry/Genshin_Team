<?php
declare (strict_types = 1);

namespace Tests\TestCase;

use GenshinTeam\Connexion\Database;
use GenshinTeam\Controllers\StatController;
use GenshinTeam\Models\Stat;
use GenshinTeam\Models\StatModel;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorPresenterInterface;
use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Classe de base pour les tests unitaires du contrôleur StatController.
 *
 * Cette classe :
 * - Crée un environnement isolé avec une base de données SQLite en mémoire
 * - Prépare des vues temporaires pour le rendu HTML simulé
 * - Fournit des méthodes utilitaires pour instancier le contrôleur et simuler des requêtes HTTP
 */
abstract class StatControllerTestCase extends TestCase
{
    protected string $viewPath;
    protected PDO $pdo;

    /**
     * Initialise l'environnement de test.
     *
     * Ce setup inclut :
     * - La définition de constantes
     * - La création de répertoires de vues temporaires avec du contenu simulé
     * - La mise en place d'une base SQLite en mémoire avec la table zell_Stat
     * - La réinitialisation des superglobals PHP
     */
    protected function setUp(): void
    {
        if (! defined('BASE_URL')) {
            define('BASE_URL', 'http://localhost');
        }

        // Vue temporaire
        $this->viewPath = sys_get_temp_dir() . '/views_' . uniqid();
        @mkdir("{$this->viewPath}/stats", 0777, true);
        @mkdir("{$this->viewPath}/templates", 0777, true);
        @mkdir("{$this->viewPath}/partials", 0777, true);

        file_put_contents("{$this->viewPath}/stats/add-stat.php", '<form>add</form>');
        file_put_contents("{$this->viewPath}/stats/edit-stat.php", '<form>edit</form>');
        file_put_contents("{$this->viewPath}/templates/default.php", '<html><?= $title ?><?= $content ?></html>');
        file_put_contents("{$this->viewPath}/stats/delete-stat-confirm.php", '<form>confirm delete</form>');
        file_put_contents("{$this->viewPath}/stats/stats-list.php", '<ul><li>stat</li></ul>');
        file_put_contents("{$this->viewPath}/partials/select-item.php", '<select>select</select>');

        // Base SQLite en mémoire
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('
            CREATE TABLE zell_stats (
                id_stat INTEGER PRIMARY KEY AUTOINCREMENT,
                Stat TEXT NOT NULL UNIQUE
            )
        ');
        Database::setInstance($this->pdo);

        $_POST                     = $_SESSION                     = $_SERVER                     = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    /**
     * Nettoie l'environnement de test en supprimant les vues temporaires
     * et en réinitialisant la base de données et les superglobals.
     */
    protected function tearDown(): void
    {
        $this->deleteRecursive($this->viewPath);
        Database::setInstance(null);
        $_POST = $_SESSION = $_SERVER = [];
    }

    /**
     * Instancie un StatController avec ses dépendances mockées ou par défaut.
     *
     * @param StatModel|null $model Modèle simulé ou réel du moyen d'obtention
     * @param Renderer|null $renderer Rendu simulé ou réel
     * @return StatController Instance du contrôleur prêt pour les tests
     */
    protected function getController(?StatModel $model = null, ?Renderer $renderer = null): StatController
    {
        $renderer = $renderer ?? new Renderer($this->viewPath);

        $logger    = $this->createMock(LoggerInterface::class);
        $presenter = $this->createMock(ErrorPresenterInterface::class);
        $session   = new SessionManager();

        return new StatController($renderer, $logger, $presenter, $session, $model ?? new StatModel($this->pdo, $logger));
    }

    /**
     * Simule une requête POST en injectant les données fournies dans $_POST
     * et en configurant le jeton CSRF et la méthode HTTP.
     *
     * @param array<string, int|string> $data Données à injecter dans la requête POST
     */
    protected function preparePost(array $data): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['csrf_token']    = 'token';
        $_POST                     = array_merge(['csrf_token' => 'token'], $data);
    }

    /**
     * Supprime récursivement un répertoire et son contenu.
     *
     * @param string $path Chemin vers le répertoire à supprimer
     */
    private function deleteRecursive(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item instanceof \SplFileInfo) {
                $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
            }
        }

        @rmdir($path);
    }
}
