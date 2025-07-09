<?php
declare (strict_types = 1);

use GenshinTeam\Controllers\IndexController;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorPresenterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Teste le comportement de IndexController dans différents contextes utilisateur.
 *
 * @covers \GenshinTeam\Controllers\IndexController
 */
class IndexControllerTest extends TestCase
{
    /** @var string */
    private string $viewPath;

    /**
     * Prépare un environnement temporaire avec des fichiers de vue HTML.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->viewPath = sys_get_temp_dir() . '/views_' . uniqid();
        @mkdir($this->viewPath . '/templates', 0777, true);

        file_put_contents($this->viewPath . '/index.php', '<p>Accueil</p>');
        file_put_contents(
            $this->viewPath . '/templates/default.php',
            '<html><head><title><?= $title ?></title></head><body><?= $content ?></body></html>'
        );
    }

    /**
     * Supprime les fichiers temporaires après exécution des tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        @unlink($this->viewPath . '/index.php');
        @unlink($this->viewPath . '/templates/default.php');
        @rmdir($this->viewPath . '/templates');
        @rmdir($this->viewPath);
    }

    /**
     * Vérifie le rendu de la page d’accueil pour un visiteur non connecté.
     *
     * @return void
     */
    public function testDisplayHomeWhenNotConnected(): void
    {
        $_SESSION = [];

        $renderer  = new Renderer($this->viewPath);
        $logger    = $this->createMock(LoggerInterface::class);
        $presenter = $this->createMock(ErrorPresenterInterface::class);
        $session   = new SessionManager();
        $session->clear();

        $controller = new IndexController($renderer, $logger, $presenter, $session);

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('Bienvenue sur Genshin Team', $output);
        $this->assertStringContainsString('<p>Accueil</p>', $output);
    }

    /**
     * Vérifie le rendu personnalisé pour un utilisateur connecté.
     *
     * @return void
     */
    public function testDisplayHomeWhenConnected(): void
    {
        $renderer  = new Renderer($this->viewPath);
        $logger    = $this->createMock(LoggerInterface::class);
        $presenter = $this->createMock(ErrorPresenterInterface::class);
        $session   = new SessionManager();
        $session->set('user', 'Jean');

        $controller = new IndexController($renderer, $logger, $presenter, $session);

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('Bienvenue sur Genshin Team, Jean', $output);
        $this->assertStringContainsString('<p>Accueil</p>', $output);
    }

    /**
     * Vérifie que le contrôleur appelle ErrorPresenter::present()
     * si la vue d’accueil est absente ou échoue.
     *
     * @return void
     */
    public function testErrorRenderingView(): void
    {
        @unlink($this->viewPath . '/index.php');
        $_SESSION = [];

        $renderer  = new Renderer($this->viewPath);
        $logger    = $this->createMock(LoggerInterface::class);
        $presenter = $this->createMock(ErrorPresenterInterface::class);
        $session   = new SessionManager();

        $controller = new IndexController($renderer, $logger, $presenter, $session);

        $presenter->expects($this->once())->method('present');

        $controller->run();
    }

    /**
     * Vérifie que la méthode setCurrentRoute() est bien définie et exécutable,
     * même si elle n'a aucun effet observable (implémentation vide).
     *
     * Ce test garantit que la classe implémente correctement la méthode abstraite
     * héritée de AbstractController, et qu'elle peut être invoquée sans erreur.
     *
     * @return void
     *
     * @covers \GenshinTeam\Controllers\AdminController::setCurrentRoute
     */
    public function testSetCurrentRouteIsCallable(): void
    {
        $renderer  = new Renderer($this->viewPath);
        $logger    = $this->createMock(LoggerInterface::class);
        $presenter = $this->createMock(ErrorPresenterInterface::class);
        $session   = new SessionManager();

        $controller = new IndexController($renderer, $logger, $presenter, $session);

        // L'appel ne doit rien faire, mais il ne doit surtout pas planter
        $controller->setCurrentRoute('index');

        // Tu peux ajouter une assertion vide ou une ligne de vérification basique
        $this->expectNotToPerformAssertions();
    }
}
