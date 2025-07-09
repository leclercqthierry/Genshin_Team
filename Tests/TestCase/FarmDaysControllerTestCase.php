<?php
declare (strict_types = 1);

namespace Tests\TestCase;

use GenshinTeam\Connexion\Database;
use GenshinTeam\Controllers\FarmDaysController;
use GenshinTeam\Models\FarmDays;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorPresenterInterface;
use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Classe de base pour les tests du contrôleur FarmDaysController.
 *
 * Fournit :
 * - un Renderer pointant vers un répertoire temporaire de vues,
 * - une session isolée,
 * - une instance mockable de FarmDays,
 * - une base SQLite en mémoire injectée dans Database,
 * - un nettoyage strict après chaque test (fichiers, constantes, DB).
 */
abstract class FarmDaysControllerTestCase extends TestCase
{
    protected string $viewPath;
    protected PDO $pdo;

    protected function setUp(): void
    {
        // Créer le répertoire temporaire pour les vues
        $this->viewPath = sys_get_temp_dir() . '/views_' . uniqid();
        @mkdir("{$this->viewPath}/farm-days", 0777, true);
        @mkdir("{$this->viewPath}/partials", 0777, true);
        @mkdir("{$this->viewPath}/templates", 0777, true);

        // Créer des vues minimales
        file_put_contents("{$this->viewPath}/farm-days/add-farm-days.php", '<form>add</form>');
        file_put_contents("{$this->viewPath}/farm-days/delete-farm-days-confirm.php", '<form>confirm</form>');
        file_put_contents("{$this->viewPath}/partials/select-item.php", '<select>select</select>');
        file_put_contents("{$this->viewPath}/farm-days/farm-days-list.php", '<ul>list</ul>');
        file_put_contents("{$this->viewPath}/templates/default.php", '<html><?= $title ?? "" ?><?= $content ?? "" ?></html>');

        // Définir PROJECT_ROOT si nécessaire
        if (! defined('PROJECT_ROOT')) {
            define('PROJECT_ROOT', __DIR__ . '/../..');
        }

        // Simuler l'environnement de test
        putenv('APP_ENV=test');

        // Préparer base de données en mémoire
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        Database::setInstance($this->pdo);

        // Nettoyer les superglobales à chaque test
        $_POST = $_SESSION = $_SERVER = [];
    }

    protected function tearDown(): void
    {
        // Supprimer les fichiers créés
        $this->deleteRecursive($this->viewPath);

        // Nettoyer Database et env
        Database::setInstance(null);
        putenv('APP_ENV');
    }

    /**
     * Fournit un contrôleur instancié avec dépendances isolées.
     */
    protected function getController(?FarmDays $model = null): FarmDaysController
    {
        $renderer  = new Renderer($this->viewPath);
        $logger    = $this->createMock(LoggerInterface::class);
        $presenter = $this->createMock(ErrorPresenterInterface::class);
        $session   = new SessionManager();

        return new FarmDaysController($renderer, $logger, $presenter, $session, $model ?? $this->createMock(FarmDays::class));
    }

    /**
     * Supprime un dossier récursivement.
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
            if (! $item instanceof \SplFileInfo) {
                continue;
            }
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($path);
    }

    /**
     * @param array<string, int|string|array<int, string>> $postData
     */
    protected function preparePostRequest(array $postData): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['csrf_token']    = 'token';
        $_POST                     = array_merge(['csrf_token' => 'token'], $postData);
    }

}
