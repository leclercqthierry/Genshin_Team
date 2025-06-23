<?php
declare (strict_types = 1);

use GenshinTeam\Controllers\AbstractController;
use GenshinTeam\Controllers\LogoutController;
use GenshinTeam\Controllers\NotFoundController;
use GenshinTeam\Router\Router;
use GenshinTeam\Session\SessionManager;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../constants.php';

/**
 * Classe factice pour simuler un contrôleur.
 *
 * Cette classe est utilisée à des fins de test uniquement.
 */

class HomeController extends AbstractController
{

    /**
     * Cette méthode ne réalise ici aucune action concrète dans ce contexte de test.
     *
     * @return void
     */
    public function handleRequest(): void
    {}

    /**
     * Simule l'exécution d'une requête et le cycle de vie d'un contrôleur standard.
     *
     * @return void
     */
    public function run(): void
    {}
}

/**
 * Classe de test qui expose la méthode `resolveController`.
 *
 * Cette classe étend `Router` et permet de tester directement la résolution de contrôleurs.
 */
class TestableRouter extends Router
{
    /**
     * Teste la résolution d'un contrôleur à partir d'un URI.
     *
     * Cette méthode est un simple proxy vers `resolveController`, permettant son test unitaire.
     *
     * @param string $uri L'URI à traiter pour obtenir le contrôleur associé.
     * @return AbstractController Le contrôleur résolu.
     */
    public function testResolveController(string $uri): AbstractController
    {
        return $this->resolveController($uri);
    }
}

/**
 * Classe factice représentant un contrôleur fictif.
 *
 * Cette classe est principalement utilisée pour des tests pour lesquels le contrôleur n'hérite pas de AbstractController.
 */
class FakeController
{}

/**
 * Classe de test unitaire pour Router.
 */
class RouterTest extends TestCase
{
    /**
     * Instance du routeur utilisée dans les tests.
     *
     * @var Router
     */
    private Router $router;

    /**
     * Initialise une nouvelle instance de `Router` avant chaque test.
     *
     * Cette méthode est automatiquement exécutée avant chaque test unitaire,
     * garantissant un environnement de test propre. Elle ferme la session
     * existante si celle-ci est active pour éviter tout conflit.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $mockLogger    = $this->createMock(\Psr\Log\LoggerInterface::class);
        $mockPresenter = $this->createMock(\GenshinTeam\Utils\ErrorPresenterInterface::class);
        $session       = new SessionManager();
        $this->router  = new Router($mockLogger, $mockPresenter, $session);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    /**
     * Vérifie que le dispatch d'une route existante exécute le bon contrôleur.
     *
     * Cette méthode teste que `dispatch` invoque correctement l'exécution du contrôleur
     * associé à une route définie. Elle simule une requête et vérifie que la méthode `run`
     * du contrôleur est bien appelée une seule fois.
     *
     * @covers \GenshinTeam\Router\Router::dispatch
     * @return void
     */
    public function testDispatchWithExistingRoute(): void
    {
        // Ajout d'une route pour le test
        $this->router->addRoute('home', 'HomeController');

        // Simulation de l'URI de la requête
        $_SERVER['REQUEST_URI'] = '/home';

        // Création d'un mock pour le moteur de rendu
        $mockRenderer = $this->createMock(\GenshinTeam\Renderer\Renderer::class);

        // Mock du contrôleur avec injection du mock de rendu
        $mockController = $this->getMockBuilder(HomeController::class)
            ->setConstructorArgs([$mockRenderer, new SessionManager()])
            ->getMock();

        // Vérification que la méthode `run` est appelée une fois
        $mockController->expects($this->once())->method('run');

        // Injection du mock dans le routeur
        $this->router->setControllerInstance($mockController);

        // Exécution du dispatch
        $this->router->dispatch();
    }

    /**
     * Vérifie que le dispatch d'une route inexistante exécute le contrôleur 404.
     *
     * Cette méthode teste que `dispatch` invoque correctement le contrôleur de page
     * non trouvée (`NotFoundController`) lorsque l'URI demandée ne correspond à aucune
     * route existante. Elle simule une requête et vérifie que la méthode `run` est bien appelée.
     *
     * @covers \GenshinTeam\Router\Router::dispatch
     * @return void
     */
    public function testDispatchWithNonExistingRoute(): void
    {
        // Simulation d'une requête avec une route inexistante
        $_SERVER['REQUEST_URI'] = '/unknown';

        // Création d'un mock pour le contrôleur 404
        $mockController = $this->createMock(NotFoundController::class);

        // Vérification que la méthode `run` est appelée une fois
        $mockController->expects($this->once())->method('run');

        // Injection du mock pour simuler la page 404
        $this->router->setControllerInstance($mockController);

        // Exécution du dispatch
        $this->router->dispatch();
    }

    /**
     * Vérifie que `dispatch` lève une exception si l'URI de la requête est invalide.
     *
     * Cette méthode teste que `Router::dispatch` génère correctement une exception
     * lorsque `$_SERVER['REQUEST_URI']` est null ou mal définie.
     *
     * @covers \GenshinTeam\Router\Router::dispatch
     * @return void
     *
     * @throws Exception Si l'URI de la requête est invalide.
     */
    public function testDispatchThrowsExceptionIfUriIsInvalid(): void
    {
        $mockLogger    = $this->createMock(\Psr\Log\LoggerInterface::class);
        $mockPresenter = $this->createMock(\GenshinTeam\Utils\ErrorPresenterInterface::class);
        $session       = new SessionManager();
        $router        = new Router($mockLogger, $mockPresenter, $session);

        // Simuler une valeur invalide pour $_SERVER['REQUEST_URI']
        $_SERVER['REQUEST_URI'] = null;

        // Vérifier que l'exception est bien levée
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("L'URI de la requête n'est pas valide.");

        $router->dispatch();
    }

    /**
     * Vérifie que la résolution d'un contrôleur déclenche `NotFoundController`
     * lorsque la classe associée à la route n'existe pas.
     *
     * Cette méthode teste que `resolveController` retourne une instance de
     * `NotFoundController` lorsque la classe spécifiée dans `addRoute` est inexistante.
     *
     * @covers \GenshinTeam\Router\Router::resolveController
     * @return void
     */
    public function testResolveControllerTriggersNotFoundControllerForInvalidClass(): void
    {
        $mockLogger    = $this->createMock(\Psr\Log\LoggerInterface::class);
        $mockPresenter = $this->createMock(\GenshinTeam\Utils\ErrorPresenterInterface::class);

        $session = new SessionManager();
        $router  = new TestableRouter($mockLogger, $mockPresenter, $session);

        // Ajouter une route avec une classe inexistante
        $router->addRoute('invalid-route', 'AbsolutelyNonExistentClass');

        // Tenter de résoudre le contrôleur
        $controller = $router->testResolveController('invalid-route');

        // Vérifier que `NotFoundController` est bien retourné
        $this->assertInstanceOf(NotFoundController::class, $controller, 'Le contrôleur retourné pour une classe inexistante doit être NotFoundController.');
    }

    /**
     * Vérifie que `resolveController` lève une exception si le contrôleur
     * enregistré n'hérite pas de `AbstractController`.
     *
     * Cette méthode teste que `Router::resolveController` détecte correctement
     * qu'un contrôleur invalide a été associé à une route et génère l'exception appropriée.
     *
     * @covers \GenshinTeam\Router\Router::resolveController
     * @return void
     *
     * @throws Exception Si le contrôleur spécifié n'hérite pas de `AbstractController`.
     */
    public function testResolveControllerThrowsExceptionIfControllerNotExtendingAbstractController(): void
    {
        $mockLogger    = $this->createMock(\Psr\Log\LoggerInterface::class);
        $mockPresenter = $this->createMock(\GenshinTeam\Utils\ErrorPresenterInterface::class);

        $session = new SessionManager();
        $router  = new TestableRouter($mockLogger, $mockPresenter, $session);

        // Ajouter une fausse route qui pointe vers un contrôleur invalide
        $router->addRoute('fake-route', 'FakeController');

        // Vérifier que l'ajout de la route fonctionne si `getRoutes()` est disponible
        if (method_exists($router, 'getRoutes')) {
            $this->assertArrayHasKey('fake-route', $router->getRoutes(), "La route 'fake-route' n'est pas enregistrée.");
            $this->assertEquals('FakeController', $router->getRoutes()['fake-route'], "La route 'fake-route' ne pointe pas vers FakeController.");
        }

        // Vérifier que `resolveController()` lève bien l'exception attendue
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Le contrôleur 'GenshinTeam\\Controllers\\FakeController' n'hérite pas de AbstractController.");

        // Appeler la méthode de test qui expose `resolveController()`
        $router->testResolveController('fake-route');
    }

    /**
     * Vérifie que `resolveController` retourne bien le contrôleur attendu pour une route valide.
     *
     * Cette méthode teste que `Router::resolveController` fournit correctement une instance du contrôleur
     * enregistré via `addRoute`, garantissant ainsi un fonctionnement cohérent.
     *
     * @covers \GenshinTeam\Router\Router::resolveController
     * @return void
     */
    public function testResolveControllerCoversValidReturn(): void
    {
        $mockLogger    = $this->createMock(\Psr\Log\LoggerInterface::class);
        $mockPresenter = $this->createMock(\GenshinTeam\Utils\ErrorPresenterInterface::class);

        $session = new SessionManager();
        $router  = new TestableRouter($mockLogger, $mockPresenter, $session);

        // Ajouter une route avec un contrôleur valide
        $router->addRoute('logout', 'LogoutController');

        // Vérifier que LogoutController est bien retourné
        $controller = $router->testResolveController('logout');

        // Ajout d'une assertion pour garantir le retour attendu
        $this->assertInstanceOf(
            LogoutController::class,
            $controller,
            "resolveController devrait retourner une instance de LogoutController pour une route valide."
        );
    }

    /**
     * Vérifie que `getRoutes` retourne bien les routes enregistrées après l'ajout via `addRoute`.
     *
     * Cette méthode teste que plusieurs routes ajoutées via `addRoute` sont correctement
     * stockées et accessibles via `getRoutes()`.
     *
     * @covers \GenshinTeam\Router\Router::addRoute
     * @covers \GenshinTeam\Router\Router::getRoutes
     * @return void
     */
    public function testRoutesStorageAndRetrieval(): void
    {
        $mockLogger    = $this->createMock(\Psr\Log\LoggerInterface::class);
        $mockPresenter = $this->createMock(\GenshinTeam\Utils\ErrorPresenterInterface::class);
        $session       = new SessionManager();
        $router        = new Router($mockLogger, $mockPresenter, $session);

        // Ajout de plusieurs routes
        $router->addRoute('home', 'HomeController');
        $router->addRoute('test-route', 'TestController');

        // Récupération des routes enregistrées
        $routes = $router->getRoutes();

        // Vérifications des valeurs attendues
        $this->assertArrayHasKey('home', $routes, "La route 'home' devrait être présente.");
        $this->assertEquals('HomeController', $routes['home'], "La route 'home' devrait pointer vers 'HomeController'.");

        $this->assertArrayHasKey('test-route', $routes, "La route 'test-route' devrait être présente.");
        $this->assertEquals('TestController', $routes['test-route'], "La route 'test-route' devrait pointer vers 'TestController'.");
    }

}
