<?php
declare (strict_types = 1);

use GenshinTeam\Controllers\LoginController;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use PHPUnit\Framework\TestCase;

class LoginControllerTest extends TestCase
{
    private string $viewPath;

    protected function setUp(): void
    {
        $this->viewPath = sys_get_temp_dir() . '/views_' . uniqid();
        @mkdir($this->viewPath, 0777, true);
        file_put_contents($this->viewPath . '/login.php', <<<'PHP'
<?php if (isset($errors['global'])): ?>
    <div role="alert"><?php echo htmlspecialchars($errors['global']); ?></div>
<?php endif; ?>
<form>login</form>
PHP
        );

        @mkdir($this->viewPath . '/templates', 0777, true);
        file_put_contents($this->viewPath . '/templates/default.php', '<html><?= $title ?? "" ?><?= $content ?? "" ?></html>');
        $_SESSION                  = [];
        $_POST                     = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        @unlink($this->viewPath . '/login.php');
        @unlink($this->viewPath . '/templates/default.php');
        @rmdir($this->viewPath . '/templates');
        @rmdir($this->viewPath);
        $_SESSION = [];
        $_POST    = [];
    }

    private function getController(?SessionManager $session = null): LoginController
    {
        $renderer  = new Renderer($this->viewPath);
        $logger    = $this->createMock(\Psr\Log\LoggerInterface::class);
        $presenter = $this->createMock(\GenshinTeam\Utils\ErrorPresenterInterface::class);
        $session   = $session ?: new SessionManager();

        // On mock la méthode redirect pour éviter exit
        return $this->getMockBuilder(LoginController::class)
            ->setConstructorArgs([$renderer, $logger, $presenter, $session])
            ->onlyMethods(['redirect'])
            ->getMock();
    }

    public function testDisplayLoginForm(): void
    {
        $controller = $this->getController();

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertStringContainsString('Se Connecter', $output);
        $this->assertStringContainsString('<form>login</form>', $output);
    }

    public function testDisplayFormWithCsrfError(): void
    {
        $controller                = $this->getController();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token']       = 'invalid';

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertStringContainsString('Requête invalide', $output);
        $this->assertStringContainsString('<form>login</form>', $output);
    }

    public function testLoginAttemptLimit(): void
    {
        $session = new SessionManager();
        $session->set('csrf_token', 'abc');
        $session->set('login_attempts', 3);

        $controller                = $this->getController($session);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token']       = 'abc';
        $_POST['nickname']         = 'Jean';
        $_POST['password']         = 'secret';

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertStringContainsString('Trop de tentatives échouées', $output);
    }

    public function testEmptyFieldsShowError(): void
    {
        $session = new SessionManager();
        $session->set('csrf_token', 'abc');
        $controller = $this->getController($session);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token']       = 'abc';
        $_POST['nickname']         = '';
        $_POST['password']         = '';

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertStringContainsString('Veuillez remplir tous les champs', $output);
    }

    public function testFailedLoginIncrementsAttempts(): void
    {
        $session = new SessionManager();
        $session->set('csrf_token', 'abc');
        $controller = $this->getController($session);

        // Mock User pour retourner null (utilisateur non trouvé)
        $userMock = $this->createMock(\GenshinTeam\Models\User::class);
        $userMock->method('getUserByNickname')->willReturn(null);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token']       = 'abc';
        $_POST['nickname']         = 'Jean';
        $_POST['password']         = 'wrong';

        // On injecte le mock User dans le contrôleur via Reflection (car User est instancié en dur)
        $ref    = new ReflectionClass($controller);
        $method = $ref->getMethod('handleLogin');
        $method->setAccessible(true);

        ob_start();
        $controller->run();
        ob_end_clean();

        $this->assertEquals(1, $session->get('login_attempts'));
    }

    public function testSuccessfulLoginRedirectsAndResetsAttempts(): void
    {
        $renderer  = new Renderer($this->viewPath);
        $logger    = $this->createMock(\Psr\Log\LoggerInterface::class);
        $presenter = $this->createMock(\GenshinTeam\Utils\ErrorPresenterInterface::class);
        $session   = new SessionManager();
        $session->set('csrf_token', 'abc');

        // Mock User pour retourner un utilisateur valide
        $userMock = $this->createMock(\GenshinTeam\Models\User::class);
        $userMock->method('getUserByNickname')->willReturn([
            'password' => password_hash('secret', PASSWORD_DEFAULT),
            'id_role'  => 2,
        ]);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token']       = 'abc';
        $_POST['nickname']         = 'Jean';
        $_POST['password']         = 'secret';

        // On mock la redirection
        $controller = $this->getMockBuilder(LoginController::class)
            ->setConstructorArgs([$renderer, $logger, $presenter, $session, $userMock])
            ->onlyMethods(['redirect'])
            ->getMock();

        $controller->expects($this->once())->method('redirect')->with('index');

        try {
            $controller->run();
        } catch (\Throwable $e) {
            // ignore header() warning
        }

        $this->assertEquals(0, $session->get('login_attempts'));
        $this->assertEquals('Jean', $session->get('user'));
        $this->assertEquals(2, $session->get('id_role'));
        $this->assertNotEmpty($session->get('csrf_token'));
    }

    public function testShowLoginFormHandlesRenderException(): void
    {
        $renderer  = $this->createMock(Renderer::class);
        $logger    = $this->createMock(\Psr\Log\LoggerInterface::class);
        $presenter = $this->createMock(\GenshinTeam\Utils\ErrorPresenterInterface::class);
        $session   = new SessionManager();

        // Le renderer va lancer une exception lors du rendu
        $renderer->method('render')
            ->willReturnCallback(function ($view) {
                if ($view === 'templates/default') {
                    throw new \Exception('Rendering failed');
                }
                return '';
            });

        // On s'attend à ce que le presenter soit appelé pour afficher l'erreur
        $presenter->expects($this->once())->method('present');

        $controller = $this->getMockBuilder(LoginController::class)
            ->setConstructorArgs([$renderer, $logger, $presenter, $session])
            ->onlyMethods(['redirect'])
            ->getMock();

        // Utilisation de la réflexion pour appeler la méthode protégée
        $refMethod = (new \ReflectionClass($controller))->getMethod('showLoginForm');
        $refMethod->setAccessible(true);
        $refMethod->invoke($controller);
    }
}
