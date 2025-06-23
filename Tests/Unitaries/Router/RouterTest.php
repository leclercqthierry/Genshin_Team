<?php
declare (strict_types = 1);

use GenshinTeam\Controllers\AbstractController;
use GenshinTeam\Controllers\LogoutController;
use GenshinTeam\Controllers\NotFoundController;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Router\Router;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorPresenterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

require_once __DIR__ . '/../../../constants.php';

/**
 * Contrôleur factice utilisé uniquement pour tester le routage.
 */
class HomeController extends AbstractController
{
    /**
     * Ne fait rien : méthode simulée pour respecter la structure du contrôleur.
     *
     * @return void
     */
    public function handleRequest(): void
    {}

    /**
     * Simule l’exécution du contrôleur (ne fait rien dans ce test).
     *
     * @return void
     */
    public function run(): void
    {}
}

/**
 * Étend Router pour exposer resolveController() en tant que méthode publique testable.
 */
class TestableRouter extends Router
{
    /**
     * Proxifie resolveController pour permettre des assertions de test unitaires.
     *
     * @param string $uri
     * @return AbstractController
     */
    public function testResolveController(string $uri): AbstractController
    {
        return $this->resolveController($uri);
    }
}

/**
 * Contrôleur simulé ne dérivant pas d’AbstractController.
 *
 * Utilisé pour tester la résilience du résolveur face à des classes invalides.
 */
class FakeController
{}

/**
 * Tests unitaires de la classe Router.
 *
 * @covers \GenshinTeam\Router\Router
 */
class RouterTest extends TestCase
{
    /** @var Router */
    private Router $router;

    /**
     * Crée une nouvelle instance du routeur avec ses dépendances simulées.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $mockLogger    = $this->createMock(LoggerInterface::class);
        $mockPresenter = $this->createMock(ErrorPresenterInterface::class);
        $session       = new SessionManager();

        $this->router = new Router($mockLogger, $mockPresenter, $session);

        // Assure que la session est propre pour éviter les fuites entre tests
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    /**
     * Vérifie que le dispatch appelle `run()` sur le bon contrôleur défini pour une URI existante.
     *
     * @return void
     */
    public function testDispatchWithExistingRoute(): void
    {
        // On enregistre la route "/home" qui doit pointer vers HomeController
        $this->router->addRoute('home', HomeController::class);

        // Simule une requête HTTP vers cette route
        $_SERVER['REQUEST_URI'] = '/home';

        // Mocke un moteur de rendu à injecter dans le contrôleur
        $mockRenderer = $this->createMock(Renderer::class);

        // Crée un mock de contrôleur surchargé pour vérifier l’appel à `run()`
        $mockController = $this->getMockBuilder(HomeController::class)
            ->setConstructorArgs([$mockRenderer, new SessionManager()])
            ->onlyMethods(['run'])
            ->getMock();

        $mockController->expects($this->once())->method('run');

        // On injecte le mock manuellement pour le test
        $this->router->setControllerInstance($mockController);

        // Et on déclenche le dispatch simulé
        $this->router->dispatch();
    }

    /**
     * Vérifie que le dispatch utilise `NotFoundController` si aucune route ne correspond.
     *
     * @return void
     */
    public function testDispatchWithNonExistingRoute(): void
    {
        // Simule une URI qui n'existe dans aucune route
        $_SERVER['REQUEST_URI'] = '/unknown';

        // Crée un mock du contrôleur 404
        $mockController = $this->createMock(NotFoundController::class);
        $mockController->expects($this->once())->method('run');

        // Injection manuelle dans le routeur
        $this->router->setControllerInstance($mockController);

        $this->router->dispatch();
    }

    /**
     * Vérifie que `dispatch()` lève une exception si l'URI de requête est manquante ou invalide.
     *
     * @covers \GenshinTeam\Router\Router::dispatch
     * @return void
     *
     * @throws Exception
     */
    public function testDispatchThrowsExceptionIfUriIsInvalid(): void
    {
        $mockLogger    = $this->createMock(LoggerInterface::class);
        $mockPresenter = $this->createMock(ErrorPresenterInterface::class);
        $session       = new SessionManager();
        $router        = new Router($mockLogger, $mockPresenter, $session);

        // Simule une requête invalide en forçant $_SERVER['REQUEST_URI'] à null
        $_SERVER['REQUEST_URI'] = null;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("L'URI de la requête n'est pas valide.");

        $router->dispatch();
    }

    /**
     * Vérifie que `resolveController()` retourne un NotFoundController si la classe ciblée n'existe pas.
     *
     * @covers \GenshinTeam\Router\Router::resolveController
     * @return void
     */
    public function testResolveControllerTriggersNotFoundControllerForInvalidClass(): void
    {
        $mockLogger    = $this->createMock(LoggerInterface::class);
        $mockPresenter = $this->createMock(ErrorPresenterInterface::class);
        $router        = new TestableRouter($mockLogger, $mockPresenter, new SessionManager());

        $router->addRoute('invalid-route', 'AbsolutelyNonExistentClass');

        $controller = $router->testResolveController('invalid-route');

        $this->assertInstanceOf(
            NotFoundController::class,
            $controller,
            "Le contrôleur retourné doit être NotFoundController si la classe n'existe pas."
        );
    }

    /**
     * Vérifie que `resolveController()` lève une exception si la classe cible n'hérite pas d'AbstractController.
     *
     * @covers \GenshinTeam\Router\Router::resolveController
     * @return void
     *
     * @throws Exception
     */
    public function testResolveControllerThrowsExceptionIfControllerNotExtendingAbstractController(): void
    {
        $mockLogger    = $this->createMock(LoggerInterface::class);
        $mockPresenter = $this->createMock(ErrorPresenterInterface::class);
        $router        = new TestableRouter($mockLogger, $mockPresenter, new SessionManager());

        $router->addRoute('fake-route', 'FakeController');

        // Vérifie la présence de la route et sa valeur associée
        $routes = $router->getRoutes();
        $this->assertArrayHasKey('fake-route', $routes);
        $this->assertSame('FakeController', $routes['fake-route']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Le contrôleur 'GenshinTeam\\Controllers\\FakeController' n'hérite pas de AbstractController.");

        $router->testResolveController('fake-route');
    }

    /**
     * Vérifie que `resolveController()` retourne correctement une instance pour une route valide.
     *
     * @covers \GenshinTeam\Router\Router::resolveController
     * @return void
     */
    public function testResolveControllerCoversValidReturn(): void
    {
        $mockLogger    = $this->createMock(LoggerInterface::class);
        $mockPresenter = $this->createMock(ErrorPresenterInterface::class);
        $router        = new TestableRouter($mockLogger, $mockPresenter, new SessionManager());

        $router->addRoute('logout', 'LogoutController');

        $controller = $router->testResolveController('logout');

        $this->assertInstanceOf(
            LogoutController::class,
            $controller,
            "resolveController doit retourner une instance de LogoutController."
        );
    }

    /**
     * Vérifie que les routes enregistrées sont bien récupérables via `getRoutes()`.
     *
     * @covers \GenshinTeam\Router\Router::addRoute
     * @covers \GenshinTeam\Router\Router::getRoutes
     * @return void
     */
    public function testRoutesStorageAndRetrieval(): void
    {
        $mockLogger    = $this->createMock(LoggerInterface::class);
        $mockPresenter = $this->createMock(ErrorPresenterInterface::class);
        $router        = new Router($mockLogger, $mockPresenter, new SessionManager());

        $router->addRoute('home', 'HomeController');
        $router->addRoute('test-route', 'TestController');

        $routes = $router->getRoutes();

        $this->assertArrayHasKey('home', $routes);
        $this->assertSame('HomeController', $routes['home']);

        $this->assertArrayHasKey('test-route', $routes);
        $this->assertSame('TestController', $routes['test-route']);
    }
}
