<?php
declare (strict_types = 1);

use GenshinTeam\Controllers\AdminController;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorPresenterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Classe de test unitaire pour AdminController.
 *
 * @covers \GenshinTeam\Controllers\AdminController
 */
class AdminControllerTest extends TestCase
{
    /** @var string Répertoire temporaire contenant les vues à tester */
    private string $viewPath;

    /**
     * Prépare un environnement temporaire pour les vues avant chaque test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Prépare un dossier temporaire pour les vues
        $this->viewPath = sys_get_temp_dir() . '/views_' . uniqid();
        @mkdir($this->viewPath, 0777, true);
        @mkdir($this->viewPath . '/templates', 0777, true);

        // Vue admin simplifiée
        file_put_contents($this->viewPath . '/admin.php', '<h1>Admin</h1>');

        // Template global par défaut
        file_put_contents($this->viewPath . '/templates/default.php', '<html><?= $title ?? "" ?><?= $content ?? "" ?></html>');
    }

    /**
     * Nettoie les fichiers temporaires après chaque test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        @unlink($this->viewPath . '/admin.php');
        @unlink($this->viewPath . '/templates/default.php');
        @rmdir($this->viewPath . '/templates');
        @rmdir($this->viewPath);
    }

    /**
     * Instancie un AdminController avec ses dépendances injectées.
     *
     * @param SessionManager $session Session en cours
     * @return AdminController
     */
    private function getController(SessionManager $session): AdminController
    {
        $renderer  = new Renderer($this->viewPath);
        $logger    = $this->createMock(LoggerInterface::class);
        $presenter = $this->createMock(ErrorPresenterInterface::class);

        return new AdminController($renderer, $logger, $presenter, $session);
    }

    /**
     * Vérifie que l'accès est refusé si l'utilisateur n'est pas connecté.
     *
     * @return void
     */
    public function testAccessDeniedIfNotConnected(): void
    {
        $session = new SessionManager();
        $session->set('user', null);
        $session->set('id_role', null);

        ob_start();
        $controller = $this->getController($session);
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);

        $this->assertStringContainsString('Accès interdit', $output);
        $this->assertStringContainsString('Vous n\'avez pas accès à cette page.', $output);
    }

    /**
     * Vérifie que l'accès est refusé à un utilisateur connecté non administrateur.
     *
     * @return void
     */
    public function testAccessDeniedIfNotAdmin(): void
    {
        $session = new SessionManager();
        $session->set('user', 'Jean');
        $session->set('id_role', 2); // Non admin

        ob_start();
        $controller = $this->getController($session);
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);

        $this->assertStringContainsString('Accès interdit', $output);
        $this->assertStringContainsString('Vous n\'avez pas accès à cette page.', $output);
    }

    /**
     * Vérifie que l'accès est accordé à un administrateur authentifié.
     *
     * @return void
     */
    public function testAccessGrantedIfAdmin(): void
    {
        $session = new SessionManager();
        $session->set('user', 'Admin');
        $session->set('id_role', 1); // Admin

        ob_start();
        $controller = $this->getController($session);
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);

        $this->assertStringContainsString('Panneau d\'administration', $output);
        $this->assertStringContainsString('<h1>Admin</h1>', $output);
    }

    /**
     * Vérifie que les exceptions sont interceptées et présentées correctement.
     *
     * @return void
     */
    public function testHandleRequestHandlesException(): void
    {
        $session = new SessionManager();
        $session->set('user', 'Admin');
        $session->set('id_role', 1);

        // Mock Renderer pour lancer une exception
        $renderer = $this->getMockBuilder(Renderer::class)
            ->setConstructorArgs([$this->viewPath])
            ->onlyMethods(['render'])
            ->getMock();
        $renderer->method('render')->willThrowException(new \Exception('Erreur vue'));

        $logger    = $this->createMock(LoggerInterface::class);
        $presenter = $this->createMock(ErrorPresenterInterface::class);
        $presenter->expects($this->once())->method('present');

        $controller = new AdminController($renderer, $logger, $presenter, $session);

        $controller->run();
        $this->addToAssertionCount(1);
    }

    /**
     * Vérifie que handleRequest() intercepte correctement une exception levée par renderDefault()
     * lorsque l'accès est refusé à un utilisateur non connecté ou non administrateur.
     *
     * Le test simule un scénario d'accès interdit (user et id_role à null), ce qui entraîne un appel
     * à renderDefault() à l’intérieur d’un bloc try/catch spécifique. Cette méthode déclenche ici une
     * exception contrôlée via un mock du Renderer.
     *
     * L'objectif est de confirmer que l'exception est capturée proprement par handleRequest()
     * et déléguée au ErrorPresenterInterface sans provoquer d'erreur fatale.
     *
     * @return void
     *
     * @covers \GenshinTeam\Controllers\AdminController::handleRequest
     */
    public function testHandleRequestCatchesRenderErrorWhenAccessDenied(): void
    {
        $session = new SessionManager();
        $session->set('user', null);    // Pas connecté
        $session->set('id_role', null); // Pas admin

        // Mock Renderer pour injecter une exception via renderDefault()
        $renderer = $this->getMockBuilder(Renderer::class)
            ->setConstructorArgs([$this->viewPath])
            ->onlyMethods(['render'])
            ->getMock();

        // Forcer render() à lancer une exception (via renderDefault)
        $renderer->method('render')->willThrowException(new \Exception('Erreur de rendu simulée'));

        // On ne teste pas le contenu ici, juste la gestion de l'exception
        $logger    = $this->createMock(LoggerInterface::class);
        $presenter = $this->createMock(ErrorPresenterInterface::class);

        // On s’attend à ce que handleException soit appelé 1x avec l’exception levée
        $presenter->expects($this->once())->method('present');

        $controller = new AdminController($renderer, $logger, $presenter, $session);

        // Appel
        $controller->run();

        // On ne s’attend pas à un plantage, donc : test réussi = aucun crash
        $this->addToAssertionCount(1);
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
        $controller = $this->getController(new SessionManager());

        // L'appel ne doit rien faire, mais il ne doit surtout pas planter
        $controller->setCurrentRoute('admin.dashboard');

        // Tu peux ajouter une assertion vide ou une ligne de vérification basique
        $this->expectNotToPerformAssertions();
    }

}
