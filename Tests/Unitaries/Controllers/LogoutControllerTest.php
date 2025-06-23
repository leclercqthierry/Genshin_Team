<?php
declare (strict_types = 1);

use GenshinTeam\Controllers\LogoutController;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorPresenterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Teste le comportement du contrôleur de déconnexion (LogoutController).
 *
 * Vérifie que la session est correctement vidée et détruite, et que
 * l’utilisateur est redirigé après déconnexion.
 *
 * @covers \GenshinTeam\Controllers\LogoutController
 */
class LogoutControllerTest extends TestCase
{
    /**
     * Initialise une session avec des données simulées avant chaque test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Démarre la session si elle n’est pas active
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Pré-remplit la session avec des données fictives
        $_SESSION = [
            'user' => 'Jean',
            'foo'  => 'bar',
        ];
    }

    /**
     * Nettoie l’environnement de test après exécution de chaque méthode.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // Supprime la session si elle est toujours active
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }

    /**
     * Vérifie que run() vide et détruit la session, puis effectue une redirection.
     *
     * @return void
     */
    public function testLogoutClearsAndDestroysSessionAndRedirects(): void
    {
        // Crée des mocks pour les dépendances du contrôleur
        $renderer  = $this->createMock(Renderer::class);
        $logger    = $this->createMock(LoggerInterface::class);
        $presenter = $this->createMock(ErrorPresenterInterface::class);
        $session   = new SessionManager();

        // Mock du contrôleur avec redirection interceptée pour éviter un exit()
        $controller = $this->getMockBuilder(LogoutController::class)
            ->setConstructorArgs([$renderer, $logger, $presenter, $session])
            ->onlyMethods(['redirect'])
            ->getMock();

        // Vérifie que la redirection vers "index" est bien appelée
        $controller->expects($this->once())
            ->method('redirect')
            ->with('index');

        // Exécute la logique de déconnexion
        $controller->run();

        // Vérifie que la session a bien été vidée côté global
        $this->assertEmpty($_SESSION, 'La superglobale $_SESSION doit être vide après logout');

        // Vérifie que le gestionnaire de session considère la session comme terminée
        $this->assertFalse($session->isStarted(), 'La session doit être fermée après logout');
    }
}
