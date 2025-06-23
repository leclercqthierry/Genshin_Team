<?php
declare (strict_types = 1);

use GenshinTeam\Controllers\NotFoundController;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use PHPUnit\Framework\TestCase;

class NotFoundControllerTest extends TestCase
{
    private string $viewPath;

    protected function setUp(): void
    {
        $this->viewPath = sys_get_temp_dir() . '/views_' . uniqid();
        @mkdir($this->viewPath, 0777, true);
        @mkdir($this->viewPath . '/templates', 0777, true);
        // Vue 404
        file_put_contents($this->viewPath . '/404.php', '<p>404 custom</p>');
        // Template par défaut
        file_put_contents($this->viewPath . '/templates/default.php', '<html><head><title><?= $title ?></title></head><body><?= $content ?></body></html>');
    }

    protected function tearDown(): void
    {
        @unlink($this->viewPath . '/404.php');
        @unlink($this->viewPath . '/templates/default.php');
        @rmdir($this->viewPath . '/templates');
        @rmdir($this->viewPath);
    }

    public function testDisplay404(): void
    {
        $renderer  = new Renderer($this->viewPath);
        $logger    = $this->createMock(\Psr\Log\LoggerInterface::class);
        $presenter = $this->createMock(\GenshinTeam\Utils\ErrorPresenterInterface::class);

        $session = new SessionManager();

        $controller = new NotFoundController($renderer, $logger, $presenter, $session);

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertStringContainsString('404 - Page non trouvée', $output);
        $this->assertStringContainsString('<p>404 custom</p>', $output);
    }

    public function testErrorRendering404(): void
    {
        // Supprime la vue 404 pour provoquer une erreur
        @unlink($this->viewPath . '/404.php');

        $renderer  = new Renderer($this->viewPath);
        $logger    = $this->createMock(\Psr\Log\LoggerInterface::class);
        $presenter = $this->createMock(\GenshinTeam\Utils\ErrorPresenterInterface::class);
        $session   = new SessionManager();

        // On s'attend à ce que le presenter soit appelé
        $presenter->expects($this->once())->method('present');

        $controller = new NotFoundController($renderer, $logger, $presenter, $session);
        $controller->run();
    }
}
