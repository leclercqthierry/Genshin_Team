<?php
declare (strict_types = 1);

use GenshinTeam\Controllers\NotFoundController;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorPresenterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests du contrôleur NotFoundController responsable de l'affichage de la page 404.
 *
 * @covers \GenshinTeam\Controllers\NotFoundController
 */
class NotFoundControllerTest extends TestCase
{
    /** @var string */
    private string $viewPath;

    /**
     * Prépare un environnement temporaire avec un template 404 personnalisé.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Crée un dossier temporaire pour y stocker les vues
        $this->viewPath = sys_get_temp_dir() . '/views_' . uniqid();
        @mkdir($this->viewPath, 0777, true);
        @mkdir($this->viewPath . '/templates', 0777, true);

        // Crée une vue simulée pour 404.php
        file_put_contents($this->viewPath . '/404.php', '<p>404 custom</p>');

        // Crée un template principal simple pour le layout par défaut
        file_put_contents(
            $this->viewPath . '/templates/default.php',
            '<html><head><title><?= $title ?></title></head><body><?= $content ?></body></html>'
        );
    }

    /**
     * Supprime les fichiers temporaires créés pendant les tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        @unlink($this->viewPath . '/404.php');
        @unlink($this->viewPath . '/templates/default.php');
        @rmdir($this->viewPath . '/templates');
        @rmdir($this->viewPath);
    }

    /**
     * Vérifie que la page 404 s'affiche correctement avec le contenu attendu.
     *
     * @return void
     */
    public function testDisplay404(): void
    {
        $renderer  = new Renderer($this->viewPath);
        $logger    = $this->createMock(LoggerInterface::class);
        $presenter = $this->createMock(ErrorPresenterInterface::class);
        $session   = new SessionManager();

        $controller = new NotFoundController($renderer, $logger, $presenter, $session);

        // Capture le rendu HTML
        ob_start();
        $controller->run();
        $output = ob_get_clean();

        // Vérifie que l'en-tête et la vue personnalisée sont présents dans la réponse
        $this->assertIsString($output);
        $this->assertStringContainsString('404 - Page non trouvée', $output);
        $this->assertStringContainsString('<p>404 custom</p>', $output);
    }

    /**
     * Vérifie que le contrôleur gère une erreur de rendu si le fichier 404 est manquant.
     *
     * @return void
     */
    public function testErrorRendering404(): void
    {
        // Supprime volontairement le fichier 404.php pour déclencher une erreur
        @unlink($this->viewPath . '/404.php');

        $renderer  = new Renderer($this->viewPath);
        $logger    = $this->createMock(LoggerInterface::class);
        $presenter = $this->createMock(ErrorPresenterInterface::class);
        $session   = new SessionManager();

        // Le presenter doit être invoqué pour gérer l'erreur d'affichage
        $presenter->expects($this->once())->method('present');

        $controller = new NotFoundController($renderer, $logger, $presenter, $session);
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
        $renderer  = $this->createMock(Renderer::class);
        $logger    = $this->createMock(LoggerInterface::class);
        $presenter = $this->createMock(ErrorPresenterInterface::class);
        $session   = new SessionManager();

        $controller = new NotFoundController($renderer, $logger, $presenter, $session);

        // L'appel ne doit rien faire, mais il ne doit surtout pas planter
        $controller->setCurrentRoute('index');

        // Tu peux ajouter une assertion vide ou une ligne de vérification basique
        $this->expectNotToPerformAssertions();
    }
}
