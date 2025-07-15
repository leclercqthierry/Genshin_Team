<?php
declare (strict_types = 1);

use GenshinTeam\Connexion\Database;
use GenshinTeam\Controllers\RegisterController;
use GenshinTeam\Entities\User;
use GenshinTeam\Models\UserModel;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorPresenterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DummyRegisterController extends RegisterController
{
    /** @var string|null */
    public ?string $redirectedTo = null;

    public function callLoginAndRedirect(string $nickname): void
    {
        $this->loginAndRedirect($nickname);
    }

    protected function redirect(string $target): void
    {
        $this->redirectedTo = $target; // capture sans rediriger
    }

    public function getRedirectedUrl(): ?string
    {
        return $this->redirectedTo;
    }

}

/**
 * Tests fonctionnels du RegisterController.
 *
 * Ces tests couvrent les cas d'affichage, de protection CSRF,
 * de validation, et de simulation de modèles existants.
 *
 * @covers \GenshinTeam\Controllers\RegisterController
 */
class RegisterControllerTest extends TestCase
{
    /** @var string */
    private string $viewPath;

    /**
     * Prépare un environnement de test complet :
     * - base SQLite simulée,
     * - vue register.php,
     * - layout par défaut.
     *
     * @return void
     */
    protected function setUp(): void
    {
        if (! defined('BASE_URL')) {
            define('BASE_URL', 'http://localhost');
        }

        // Simule une base de données avec une table utilisateurs
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('
            CREATE TABLE zell_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nickname TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                id_role INTEGER NOT NULL
            )
        ');
        Database::setInstance($pdo);

        // Prépare des vues dans un dossier temporaire
        $this->viewPath = sys_get_temp_dir() . '/views_' . uniqid();
        @mkdir($this->viewPath, 0777, true);
        @mkdir($this->viewPath . '/templates', 0777, true);

        // Vue d’inscription simplifiée
        file_put_contents($this->viewPath . '/register.php', <<<'PHP'
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $err): ?>
                    <div role="alert"><?php echo htmlspecialchars($err); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            <form>register</form>
        PHP);

        // Template global par défaut
        file_put_contents($this->viewPath . '/templates/default.php', '<html><?= $title ?? "" ?><?= $content ?? "" ?></html>');

        // Réinitialisation des superglobales
        $_SESSION                  = [];
        $_POST                     = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    /**
     * Supprime les fichiers de test et réinitialise l’environnement.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        @unlink($this->viewPath . '/register.php');
        @unlink($this->viewPath . '/templates/default.php');
        @rmdir($this->viewPath . '/templates');
        @rmdir($this->viewPath);
        $_SESSION = [];
    }

    /**
     * Crée une instance mockée de RegisterController avec redirection désactivée.
     *
     * @param SessionManager|null $session
     * @param UserModel|null $userModel
     * @return RegisterController
     */
    private function getController(?SessionManager $session = null, ?UserModel $userModel = null): RegisterController
    {
        $renderer  = new Renderer($this->viewPath);
        $logger    = $this->createMock(LoggerInterface::class);
        $presenter = $this->createMock(ErrorPresenterInterface::class);
        $session ??= new SessionManager();

        return $this->getMockBuilder(RegisterController::class)
            ->setConstructorArgs([$renderer, $logger, $presenter, $session, $userModel])
            ->onlyMethods(['redirect'])
            ->getMock();
    }

    private function getDummyRegisterController(?SessionManager $session = null, ?UserModel $userModel = null): DummyRegisterController
    {
        $renderer  = new Renderer($this->viewPath);
        $logger    = $this->createMock(LoggerInterface::class);
        $presenter = $this->createMock(ErrorPresenterInterface::class);
        $session ??= new SessionManager();
        $userModel ??= $this->createMock(UserModel::class);

        return new DummyRegisterController($renderer, $logger, $presenter, $session, $userModel);
    }

    /**
     * Vérifie que le formulaire d’inscription s’affiche en mode GET.
     *
     * @return void
     */
    public function testDisplayRegisterForm(): void
    {
        $controller = $this->getController();

        ob_start();
        $controller->run();
        $output = ob_get_clean();
        self::assertIsString($output);

        $this->assertStringContainsString("S'inscrire", $output);
        $this->assertStringContainsString('<form>register</form>', $output);
    }

    /**
     * Vérifie qu’un jeton CSRF invalide déclenche une erreur.
     *
     * @return void
     */
    public function testRegisterFormWithCsrfError(): void
    {
        $controller                = $this->getController();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token']       = 'invalid';

        ob_start();
        $controller->run();
        $output = ob_get_clean();
        self::assertIsString($output);

        $this->assertStringContainsString('Requête invalide', $output);
    }

    /**
     * Vérifie qu’un pseudo contenant du JS est refusé par la validation.
     *
     * @return void
     */
    public function testRegisterFormWithXssInNickname(): void
    {
        $session = new SessionManager();
        $session->set('csrf_token', 'abc');
        $controller = $this->getController($session);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST                     = [
            'csrf_token'       => 'abc',
            'nickname'         => '<script>alert(1)</script>',
            'email'            => 'jean@example.com',
            'password'         => 'Password123!',
            'confirm-password' => 'Password123!',
        ];

        ob_start();
        $controller->run();
        $output = ob_get_clean();
        self::assertIsString($output);

        $this->assertStringContainsString(
            'Votre pseudo doit contenir au moins 4 caractères alphanumériques',
            $output
        );
    }

    /**
     * Vérifie qu’un pseudo déjà existant empêche l’inscription.
     *
     * @return void
     */
    public function testRegisterFormWithNicknameAlreadyUsed(): void
    {
        $session = new SessionManager();
        $session->set('csrf_token', 'abc');

        // Simule un utilisateur déjà existant dans la base
        $userMock = $this->getMockBuilder(UserModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUserByNickname'])
            ->getMock();
        $userMock->method('getUserByNickname')->willReturn(['nickname' => 'Jean']);

        $controller = $this->getController($session, $userMock);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST                     = [
            'csrf_token'       => 'abc',
            'nickname'         => 'Jean',
            'email'            => 'jean@example.com',
            'password'         => 'Password123!',
            'confirm-password' => 'Password123!',
        ];

        ob_start();
        $controller->run();
        $output = ob_get_clean();
        self::assertIsString($output);

        $this->assertStringContainsString("Ce pseudo est déjà utilisé", $output);
    }

    /**
     * Vérifie que l’inscription échoue si l’email est déjà utilisé.
     *
     * @return void
     */
    public function testRegisterFormWithEmailAlreadyUsed(): void
    {
        $session = new SessionManager();
        $session->set('csrf_token', 'abc');

        // Simule un pseudo inexistant mais un email déjà utilisé
        $userMock = $this->getMockBuilder(UserModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUserByNickname', 'getUserByEmail'])
            ->getMock();
        $userMock->method('getUserByNickname')->willReturn(null);
        $userMock->method('getUserByEmail')->willReturn(['email' => 'jean@example.com']);

        $controller = $this->getController($session, $userMock);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST                     = [
            'csrf_token'       => 'abc',
            'nickname'         => 'Jean',
            'email'            => 'jean@example.com',
            'password'         => 'Password123!',
            'confirm-password' => 'Password123!',
        ];

        ob_start();
        $controller->run();
        $output = ob_get_clean();
        self::assertIsString($output);

        $this->assertStringContainsString('Cet email est déjà utilisé', $output);
    }

    /**
     * Vérifie que l’échec de la création de l’utilisateur affiche une erreur générique.
     *
     * @return void
     */
    public function testRegisterFormWithUserCreationFailure(): void
    {
        $session = new SessionManager();
        $session->set('csrf_token', 'abc');

        // Simule une validation OK mais un échec en base
        $userMock = $this->getMockBuilder(UserModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUserByNickname', 'getUserByEmail', 'createUser'])
            ->getMock();
        $userMock->method('getUserByNickname')->willReturn(null);
        $userMock->method('getUserByEmail')->willReturn(null);
        $userMock->method('createUser')->willReturn(false);

        $controller = $this->getController($session, $userMock);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST                     = [
            'csrf_token'       => 'abc',
            'nickname'         => 'Jean',
            'email'            => 'jean@example.com',
            'password'         => 'Password123!',
            'confirm-password' => 'Password123!',
        ];

        ob_start();
        $controller->run();
        $output = ob_get_clean();
        self::assertIsString($output);

        $this->assertStringContainsString("Erreur lors de la création de l'utilisateur", html_entity_decode($output));
    }

    /**
     * Vérifie qu’une inscription réussie redirige l’utilisateur vers l’index.
     *
     * @return void
     */
    public function testSuccessfulRegisterRedirects(): void
    {
        $renderer  = new Renderer($this->viewPath);
        $logger    = $this->createMock(\Psr\Log\LoggerInterface::class);
        $presenter = $this->createMock(\GenshinTeam\Utils\ErrorPresenterInterface::class);
        $session   = new SessionManager();
        $session->set('csrf_token', 'abc');

        // Simule une validation OK et un createUser qui retourne true
        $userMock = $this->getMockBuilder(UserModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUserByNickname', 'getUserByEmail', 'createUser'])
            ->getMock();
        $userMock->method('getUserByNickname')->willReturn(null);
        $userMock->method('getUserByEmail')->willReturn(null);
        $userMock->method('createUser')->willReturn(true);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST                     = [
            'csrf_token'       => 'abc',
            'nickname'         => 'Jean',
            'email'            => 'jean@example.com',
            'password'         => 'Password123!',
            'confirm-password' => 'Password123!',
        ];

        // Mocke la méthode redirect pour ne pas exécuter header()
        $controller = $this->getMockBuilder(RegisterController::class)
            ->setConstructorArgs([$renderer, $logger, $presenter, $session, $userMock])
            ->onlyMethods(['redirect'])
            ->getMock();

        // On s’attend à une redirection vers l’index
        $controller->expects($this->once())->method('redirect')->with('index');

        // Appel direct de la méthode protégée via réflexion
        $ref    = new ReflectionClass($controller);
        $method = $ref->getMethod('handleRegister');
        $method->setAccessible(true);
        $method->invoke($controller);
    }

    /**
     * Vérifie que showRegisterForm() gère proprement une erreur de rendu.
     *
     * @return void
     */
    public function testShowRegisterFormHandlesRenderException(): void
    {
        $renderer  = $this->createMock(Renderer::class);
        $logger    = $this->createMock(\Psr\Log\LoggerInterface::class);
        $presenter = $this->createMock(\GenshinTeam\Utils\ErrorPresenterInterface::class);
        $session   = new SessionManager();

        // Fait échouer le rendu du layout par défaut
        $renderer->method('render')->willReturnCallback(function (string $view): string {
            if ($view === 'templates/default') {
                throw new \Exception('Rendering failed');
            }
            return '';
        });

        // Vérifie que le presenter est appelé en cas d’erreur de rendu
        $presenter->expects($this->once())->method('present');

        $controller = $this->getMockBuilder(RegisterController::class)
            ->setConstructorArgs([$renderer, $logger, $presenter, $session])
            ->onlyMethods(['redirect'])
            ->getMock();

        // Appel manuel de la méthode protégée via réflexion
        $refMethod = (new ReflectionClass($controller))->getMethod('showRegisterForm');
        $refMethod->setAccessible(true);
        $refMethod->invoke($controller);
    }

    /**
     * Teste la gestion des exceptions dans la méthode handleRegister().
     *
     * Ce test simule un scénario dans lequel le modèle UserModel déclenche une exception
     * lors de l'appel à getUserByNickname(), ce qui force le contrôleur à capturer
     * cette exception dans un bloc try/catch et à invoquer handleException().
     *
     * L'objectif est de s'assurer que le bloc catch est bien exécuté et que la
     * méthode handleException() est effectivement appelée.
     *
     * @return void
     *
     * @covers \GenshinTeam\Controllers\RegisterController::handleRegister
     */
    public function testHandleRegisterCatchesThrowable(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        // Faux modèle qui déclenche une exception
        $userModel = $this->createMock(UserModel::class);
        $userModel->method('getUserByNickname')
            ->willThrowException(new RuntimeException('Erreur simulée'));

        // Mocks des autres dépendances
        $renderer       = new Renderer($this->viewPath);
        $sessionManager = new SessionManager();
        $sessionManager->set('csrf_token', 'valid_token');
        $logger         = $this->createMock(LoggerInterface::class);
        $errorPresenter = $this->createMock(ErrorPresenterInterface::class);

        // Simule les données du formulaire
        $_POST = [
            'nickname'         => 'TestUser',
            'email'            => 'test@example.com',
            'password'         => 'Password80??',
            'confirm-password' => 'Password80??',
            'csrf_token'       => 'valid_token',
        ];

        // Contrôleur personnalisé pour détecter l'appel à handleException()
        $controller = new class($renderer, $logger, $errorPresenter, $sessionManager, $userModel) extends RegisterController
        {
            public bool $caught = false;

            protected function isCsrfTokenValid(): bool
            {
                return true;
            }

            protected function handleException(\Throwable $e): void
            {
                $this->caught = true;
            }
        };

        // Appel de la méthode protégée via Reflection
        $ref    = new \ReflectionClass($controller);
        $method = $ref->getMethod('handleRegister');
        $method->setAccessible(true);
        $method->invoke($controller);

        // ✅ Le bloc catch a bien été exécuté
        $this->assertTrue($controller->caught, 'Le bloc catch n’a pas été déclenché comme prévu');
    }

    /**
     * Vérifie que la méthode setCurrentRoute() est bien définie et exécutable,
     * même si elle n'a aucun effet observable (implémentation vide).
     *
     * Ce test garantit que la classe implémente correctement la méthode abstraite
     * héritée de AbstractController, et qu'elle peut être invoquée sans erreur.
     *
     * @return void
     *
     * @covers \GenshinTeam\Controllers\AdminController::setCurrentRoute
     */
    public function testSetCurrentRouteIsCallable(): void
    {
        $renderer  = $this->createMock(Renderer::class);
        $logger    = $this->createMock(LoggerInterface::class);
        $presenter = $this->createMock(ErrorPresenterInterface::class);
        $session   = new SessionManager();

        $controller = new RegisterController($renderer, $logger, $presenter, $session);

        // L'appel ne doit rien faire, mais il ne doit surtout pas planter
        $controller->setCurrentRoute('index');

        // Tu peux ajouter une assertion vide ou une ligne de vérification basique
        $this->expectNotToPerformAssertions();
    }

    public function testLoginAndRedirectStoresUserIfFound(): void
    {
        $userData = [
            'id_user'  => 1,
            'nickname' => 'yae',
            'email'    => 'test@live.fr',
            'password' => 'truc',
            'id_role'  => 2]; // données simulées
        $userModel = $this->createMock(UserModel::class);
        $userModel->method('getUserByNickname')->with('yae')->willReturn($userData);

        $session    = new SessionManager();
        $controller = $this->getDummyRegisterController($session, $userModel);

        $controller->callLoginAndRedirect('yae');

        $user = $session->get('user');

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('yae', $user->getNickname());
    }

}
