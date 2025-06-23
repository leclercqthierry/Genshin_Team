<?php
declare (strict_types = 1);

use GenshinTeam\Controllers\IndexController;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use PHPUnit\Framework\TestCase;

class IndexControllerTest extends TestCase
{
    private string $viewPath;

    protected function setUp(): void
    {
        // Crée un dossier temporaire pour les vues
        $this->viewPath = sys_get_temp_dir() . '/views_' . uniqid();
        @mkdir($this->viewPath, 0777, true);

        // Crée le dossier templates AVANT d'écrire le fichier dedans
        @mkdir($this->viewPath . '/templates', 0777, true);

        // Crée le template index.php
        file_put_contents($this->viewPath . '/index.php', '<p>Accueil</p>');
        // Crée le template default.php
        file_put_contents($this->viewPath . '/templates/default.php', '<html><head><title><?= $title ?></title></head><body><?= $content ?></body></html>');
    }

    protected function tearDown(): void
    {
        @unlink($this->viewPath . '/index.php');
        @unlink($this->viewPath . '/templates/default.php');
        @rmdir($this->viewPath);
        @rmdir($this->viewPath . '/templates');
    }

    public function testDisplayHomeWhenNotConnected(): void
    {
        $_SESSION  = [];
        $renderer  = new Renderer($this->viewPath);
        $logger    = $this->createMock(\Psr\Log\LoggerInterface::class);
        $presenter = $this->createMock(\GenshinTeam\Utils\ErrorPresenterInterface::class);
        $session   = new SessionManager();
        $session->clear();

        $controller = new IndexController($renderer, $logger, $presenter, $session);

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertStringContainsString('Bienvenue sur Genshin Team', $output);
        $this->assertStringContainsString('<p>Accueil</p>', $output);
    }

    public function testDisplayHomeWhenConnected(): void
    {
        $renderer  = new Renderer($this->viewPath);
        $logger    = $this->createMock(\Psr\Log\LoggerInterface::class);
        $presenter = $this->createMock(\GenshinTeam\Utils\ErrorPresenterInterface::class);
        $session   = new SessionManager();
        $session->set('user', 'Jean');

        $controller = new IndexController($renderer, $logger, $presenter, $session);

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertStringContainsString('Bienvenue sur Genshin Team, Jean', $output);
        $this->assertStringContainsString('<p>Accueil</p>', $output);
    }

    public function testErrorRenderingView(): void
    {
        // Supprime le template index.php pour provoquer une erreur
        @unlink($this->viewPath . '/index.php');
        $_SESSION = [];

        $renderer  = new Renderer($this->viewPath);
        $logger    = $this->createMock(\Psr\Log\LoggerInterface::class);
        $presenter = $this->createMock(\GenshinTeam\Utils\ErrorPresenterInterface::class);
        $session   = new SessionManager();

        $controller = new IndexController($renderer, $logger, $presenter, $session);

        $presenter->expects($this->once())->method('present');

        // Appel de run() qui va déclencher l'erreur
        $controller->run();
    }
}
