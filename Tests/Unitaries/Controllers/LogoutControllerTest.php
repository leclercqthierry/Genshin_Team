<?php
declare (strict_types = 1);

use GenshinTeam\Controllers\LogoutController;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use PHPUnit\Framework\TestCase;

class LogoutControllerTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = ['user' => 'Jean', 'foo' => 'bar'];
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }

    public function testLogoutClearsAndDestroysSessionAndRedirects(): void
    {
        $renderer  = $this->createMock(Renderer::class);
        $logger    = $this->createMock(\Psr\Log\LoggerInterface::class);
        $presenter = $this->createMock(\GenshinTeam\Utils\ErrorPresenterInterface::class);
        $session   = new SessionManager();

        // On s'attend à ce que la méthode redirect soit appelée avec 'index'
        $controller = $this->getMockBuilder(LogoutController::class)
            ->setConstructorArgs([$renderer, $logger, $presenter, $session])
            ->onlyMethods(['redirect'])
            ->getMock();

        $controller->expects($this->once())
            ->method('redirect')
            ->with('index');

        // On lance la déconnexion
        $controller->run();

        // La session doit être vide et détruite
        $this->assertEmpty($_SESSION);
        $this->assertFalse($session->isStarted());
    }
}
