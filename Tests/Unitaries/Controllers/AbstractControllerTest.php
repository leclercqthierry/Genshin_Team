<?php
declare (strict_types = 1);

use GenshinTeam\Controllers\AbstractController;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use PHPUnit\Framework\TestCase;

class DummyController extends AbstractController
{
    public function run(): void
    {}

    protected function handleRequest(): void
    {}

    public function callRenderDefault(): string
    {
        ob_start();
        $this->renderDefault();
        return ob_get_clean();
    }

    public function callRedirect(string $url): void
    {
        parent::redirect($url);
    }

    // Méthodes pour tester les protected
    public function callAddError(string $key, string $msg): void
    {
        $this->addError($key, $msg);
    }

    public function callGetOld(array $defaults = []): array
    {
        return $this->getOld($defaults);
    }

    public function callIsCsrfTokenValid(): bool
    {
        return $this->isCsrfTokenValid();
    }
}

class AbstractControllerTest extends TestCase
{
    private string $viewPath;

    protected function setUp(): void
    {
        $this->viewPath = sys_get_temp_dir() . '/views_' . uniqid();
        @mkdir($this->viewPath, 0777, true);
        @mkdir($this->viewPath . '/templates', 0777, true);
        file_put_contents($this->viewPath . '/templates/default.php', '<html><?= $title ?? "" ?><?= $content ?? "" ?></html>');
    }

    protected function tearDown(): void
    {
        @unlink($this->viewPath . '/templates/default.php');
        @rmdir($this->viewPath . '/templates');
        @rmdir($this->viewPath);
    }

    public function testAddAndGetData(): void
    {
        $renderer   = new Renderer($this->viewPath);
        $session    = new SessionManager();
        $controller = new DummyController($renderer, $session);

        $controller->addData('foo', 'bar');
        $this->assertSame('bar', $controller->getData('foo'));
        $this->assertNull($controller->getData('unknown'));
    }

    public function testAddAndGetErrors(): void
    {
        $renderer   = new Renderer($this->viewPath);
        $session    = new SessionManager();
        $controller = new DummyController($renderer, $session);

        $controller->callAddError('global', 'Erreur globale');
        $controller->callAddError('email', 'Erreur email');
        $this->assertSame(['global' => 'Erreur globale', 'email' => 'Erreur email'], $controller->getErrors());
    }

    public function testRenderDefault(): void
    {
        $renderer   = new Renderer($this->viewPath);
        $session    = new SessionManager();
        $controller = new DummyController($renderer, $session);

        $controller->addData('title', 'Titre');
        $controller->addData('content', 'Contenu');
        $output = $controller->callRenderDefault();

        $this->assertStringContainsString('Titre', $output);
        $this->assertStringContainsString('Contenu', $output);
    }

    public function testGetOldReturnsDefaults(): void
    {
        $renderer   = new Renderer($this->viewPath);
        $session    = new SessionManager();
        $controller = new DummyController($renderer, $session);

        $defaults = ['nickname' => 'Jean', 'email' => 'a@b.c'];
        $this->assertSame($defaults, $controller->callGetOld($defaults));
    }

    public function testGetOldReturnsOldData(): void
    {
        $renderer   = new Renderer($this->viewPath);
        $session    = new SessionManager();
        $controller = new DummyController($renderer, $session);

        $controller->addData('old', ['nickname' => 'Paul']);
        $this->assertSame(['nickname' => 'Paul'], $controller->callGetOld(['nickname' => 'Jean']));
    }

    public function testIsCsrfTokenValid(): void
    {
        $renderer   = new Renderer($this->viewPath);
        $session    = new SessionManager();
        $controller = new DummyController($renderer, $session);

        $_SESSION['csrf_token'] = 'abc';
        $_POST['csrf_token']    = 'abc';
        $this->assertTrue($controller->callIsCsrfTokenValid());

        $_POST['csrf_token'] = 'wrong';
        $this->assertFalse($controller->callIsCsrfTokenValid());
    }
}
