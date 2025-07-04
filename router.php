<?php
declare (strict_types = 1);

require_once __DIR__ . '/constants.php';
require_once PROJECT_ROOT . '/vendor/autoload.php';

use GenshinTeam\Connexion\Database;
use GenshinTeam\Models\FarmDays;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Router\Router;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorHandler;
use GenshinTeam\Utils\ErrorPresenter;
use GenshinTeam\Utils\NullErrorPresenter;
use Psr\Log\LogLevel;

// Instancie le logger PSR-3 (ici, Monolog)
$logger = new \Monolog\Logger('app');
$logger->pushHandler(new \Monolog\Handler\StreamHandler(
    PROJECT_ROOT . '/logs/error.log',
    \Psr\Log\LogLevel::ERROR
));

// Instancie le presenter d’erreur (NullErrorPresenter pour les tests, ErrorPresenter sinon)
if (defined('PHPUNIT_RUNNING')) {
    $errorPresenter = new NullErrorPresenter();
} else {
    $renderer       = new Renderer(PROJECT_ROOT . '/src/Views');
    $errorPresenter = new ErrorPresenter($renderer, PROJECT_ROOT . '/src/Views');
}

// Configuration sécurisée des cookies de session
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',    // Domaine courant
    'secure'   => false, // Modifier à true si vous utilisez HTTPS
    'httponly' => true,
    'samesite' => 'Lax', // 'Strict' pourrait dégrader l'expérience utilisateur lors de navigations entre sites
]);

// Instancie le SessionManager
$session = new SessionManager();

$farmDayModel = new FarmDays(Database::getInstance(), $logger);

// Création et configuration du routeur
$router = new Router($logger, $errorPresenter, $session);

$router->addRoute('index', 'IndexController'); // En cas de clic sur le logo
$router->addRoute('', 'IndexController');
$router->addRoute('login', 'LoginController');
$router->addRoute('register', 'RegisterController');
$router->addRoute('logout', 'LogoutController');
$router->addRoute('admin', 'AdminController');
$router->addRoute('add-farm-days', 'FarmDaysController');
$router->addRoute('edit-farm-days', 'FarmDaysController');
$router->addRoute('delete-farm-days', 'FarmDaysController');
$router->addRoute('farm-days-list', 'FarmDaysController');
$router->addRoute('add-stat', 'StatController');
$router->addRoute('edit-stat', 'StatController');
$router->addRoute('delete-stat', 'StatController');
$router->addRoute('stats-list', 'StatController');
$router->addRoute('add-obtaining', 'ObtainingController');
$router->addRoute('edit-obtaining', 'ObtainingController');
$router->addRoute('delete-obtaining', 'ObtainingController');
$router->addRoute('obtaining-list', 'ObtainingController');

// Gestion globale des erreurs
try {
    $router->dispatch();
} catch (\Throwable $e) {
    $handler = new ErrorHandler($logger);
    $payload = $handler->handle($e);
    $errorPresenter->present($payload);
}
