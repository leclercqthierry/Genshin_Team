<?php
declare (strict_types = 1);

require_once __DIR__ . '/constants.php';
require_once PROJECT_ROOT . '/vendor/autoload.php';

use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Router\Router;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorHandler;
use GenshinTeam\Utils\ErrorPresenter;
use GenshinTeam\Utils\NullErrorPresenter;
use Monolog\Handler\StreamHandler;
use Psr\Log\LogLevel;

// Instancie le logger PSR-3 (ici, Monolog)
$logger = new \Monolog\Logger('app');
$logger->pushHandler(new StreamHandler(
    PROJECT_ROOT . '/logs/error.log',
    LogLevel::ERROR
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

// Création et configuration du routeur
$router = new Router($logger, $errorPresenter, $session);

$routes = [
    'index'            => 'IndexController',
    ''                 => 'IndexController',
    'login'            => 'LoginController',
    'forgot-password'  => 'ForgotPasswordController',
    'reset-password'   => 'ResetPasswordController',
    'register'         => 'RegisterController',
    'logout'           => 'LogoutController',
    'admin'            => 'AdminController',

    // FarmDays
    'add-farm-days'    => 'FarmDaysController',
    'edit-farm-days'   => 'FarmDaysController',
    'delete-farm-days' => 'FarmDaysController',
    'farm-days-list'   => 'FarmDaysController',

    // Stats
    'add-stat'         => 'StatController',
    'edit-stat'        => 'StatController',
    'delete-stat'      => 'StatController',
    'stats-list'       => 'StatController',

    // Obtaining
    'add-obtaining'    => 'ObtainingController',
    'edit-obtaining'   => 'ObtainingController',
    'delete-obtaining' => 'ObtainingController',
    'obtaining-list'   => 'ObtainingController',
];

foreach ($routes as $path => $controller) {
    $router->addRoute($path, $controller);
}

// Gestion globale des erreurs
try {
    $router->dispatch();
} catch (\Throwable $e) {
    $handler = new ErrorHandler($logger);
    $payload = $handler->handle($e);
    $errorPresenter->present($payload);
}
