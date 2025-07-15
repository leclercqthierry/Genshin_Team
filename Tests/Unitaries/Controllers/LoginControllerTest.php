<?php
declare (strict_types = 1);

use GenshinTeam\Connexion\Database;
use GenshinTeam\Controllers\LoginController;
use GenshinTeam\Models\UserModel;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorPresenterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests fonctionnels du contrôleur LoginController.
 *
 * Ces tests valident :
 * - l’affichage du formulaire de connexion,
 * - la protection CSRF,
 * - le comportement après échec ou succès d’authentification,
 * - le rendu d'erreurs.
 *
 * @covers \GenshinTeam\Controllers\LoginController
 */
class LoginControllerTest extends TestCase
{
    /** @var string */
    private string $viewPath;

    /**
     * Initialise l’environnement : base SQLite en mémoire et vues fictives.
     *
     * @return void
     */
    protected function setUp(): void
    {
        if (! defined('BASE_URL')) {
            define('BASE_URL', 'http://localhost');
        }

        // Création d'une base SQLite temporaire en mémoire
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

        // Création d'un dossier temporaire pour les vues
        $this->viewPath = sys_get_temp_dir() . '/views_' . uniqid();
        @mkdir($this->viewPath, 0777, true);
        @mkdir($this->viewPath . '/templates', 0777, true);

        // Création d’un template de formulaire de login factice
        file_put_contents($this->viewPath . '/login.php', <<<'PHP'
            <?php if (isset($errors['global'])): ?>
                <div role="alert"><?php echo htmlspecialchars($errors['global']); ?></div>
            <?php endif; ?>
            <form>login</form>
        PHP);

        // Template par défaut utilisé pour le layout global
        file_put_contents($this->viewPath . '/templates/default.php', '<html><?= $title ?? "" ?><?= $content ?? "" ?></html>');

        // Initialisation des superglobales
        $_SESSION                  = [];
        $_POST                     = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    /**
     * Nettoyage post-test : suppression des fichiers temporaires et des superglobales.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        @unlink($this->viewPath . '/login.php');
        @unlink($this->viewPath . '/templates/default.php');
        @rmdir($this->viewPath . '/templates');
        @rmdir($this->viewPath);
        $_SESSION = [];
        $_POST    = [];
    }

    /**
     * Crée une instance mockée de LoginController avec redirection neutralisée.
     *
     * @param SessionManager|null $session
     * @return LoginController
     */
    private function getController(?SessionManager $session = null): LoginController
    {
        $renderer  = new Renderer($this->viewPath);
        $logger    = $this->createMock(LoggerInterface::class);
        $presenter = $this->createMock(ErrorPresenterInterface::class);
        $session   = $session ?? new SessionManager();

        // On évite tout appel à header() en mockant redirect()
        return $this->getMockBuilder(LoginController::class)
            ->setConstructorArgs([$renderer, $logger, $presenter, $session])
            ->onlyMethods(['redirect'])
            ->getMock();
    }

    /**
     * Vérifie que le formulaire s’affiche correctement en mode GET.
     *
     * @return void
     */
    public function testDisplayLoginForm(): void
    {
        $controller = $this->getController();

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        self::assertIsString($output); // pour PHPStan

        $this->assertStringContainsString('Se Connecter', $output);
        $this->assertStringContainsString('<form>login</form>', $output);
    }

    /**
     * Vérifie qu’un jeton CSRF invalide affiche une erreur.
     *
     * @return void
     */
    public function testDisplayFormWithCsrfError(): void
    {
        $controller                = $this->getController();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token']       = 'invalid';

        ob_start();
        $controller->run();
        $output = ob_get_clean();
        self::assertIsString($output); // pour PHPStan

        $this->assertStringContainsString('Requête invalide', $output);
        $this->assertStringContainsString('<form>login</form>', $output);
    }

    /**
     * Vérifie que trop de tentatives empêchent la connexion.
     *
     * @return void
     */
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
        self::assertIsString($output); // pour PHPStan

        $this->assertStringContainsString('Trop de tentatives échouées', $output);
    }

    /**
     * Vérifie qu’un envoi vide renvoie un message d’erreur.
     *
     * @return void
     */
    public function testEmptyFieldsShowError(): void
    {
        $session = new SessionManager();
        $session->set('csrf_token', 'abc');

        $controller                = $this->getController($session);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST                     = [
            'csrf_token' => 'abc',
            'nickname'   => '',
            'password'   => '',
        ];

        ob_start();
        $controller->run();
        $output = ob_get_clean();
        self::assertIsString($output); // pour PHPStan

        $this->assertStringContainsString('Veuillez remplir tous les champs', $output);
    }

    /**
     * Vérifie qu’un login échoué incrémente les tentatives.
     *
     * @return void
     */
    public function testFailedLoginIncrementsAttempts(): void
    {
        $session = new SessionManager();
        $session->set('csrf_token', 'abc');

        $controller = $this->getController($session);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST                     = [
            'csrf_token' => 'abc',
            'nickname'   => 'Jean',
            'password'   => 'wrong',
        ];

        $refClass = new ReflectionClass($controller);
        $method   = $refClass->getMethod('handleLogin');
        $method->setAccessible(true);

        ob_start();
        $controller->run();
        ob_end_clean();

        $this->assertSame(1, $session->get('login_attempts'));
    }

    /**
     * Vérifie qu’un login correct redirige et nettoie la session.
     *
     * @return void
     */
    public function testSuccessfulLoginRedirectsAndResetsAttempts(): void
    {
        $session = new SessionManager();
        $session->set('csrf_token', 'abc');

        $userMock = $this->createMock(UserModel::class);
        $userMock->method('getUserByNickname')->willReturn([
            'id_user'  => 1,
            'nickname' => 'Jean',
            'email'    => 'test@live.fr',
            'password' => password_hash('secret', PASSWORD_DEFAULT),
            'id_role'  => 2,
        ]);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST                     = [
            'csrf_token' => 'abc',
            'id_user'    => 1,
            'nickname'   => 'Jean',
            'email'      => 'test@live.fr',
            'password'   => 'secret',
        ];

        $renderer  = new Renderer($this->viewPath);
        $logger    = $this->createMock(LoggerInterface::class);
        $presenter = $this->createMock(ErrorPresenterInterface::class);

        $controller = $this->getMockBuilder(LoginController::class)
            ->setConstructorArgs([$renderer, $logger, $presenter, $session, $userMock])
            ->onlyMethods(['redirect'])
            ->getMock();

        // Attente explicite de redirection
        $controller->expects($this->once())->method('redirect')->with('index');

        try {
            $controller->run();
        } catch (\Throwable) {
            // Ignore les erreurs liées à header()
        }

        // Vérifications post-authentification
        $this->assertSame(0, $session->get('login_attempts'));
        $user = $session->get('user');
        $this->assertIsArray($user); // assure que c’est bien un tableau

        $this->assertSame('Jean', $user['nickname']);
        $this->assertSame(2, $user['id_role']);

        $this->assertNotEmpty($session->get('csrf_token'));
    }

    /**
     * Vérifie que la méthode showLoginForm() intercepte les erreurs de rendu
     * et déclenche l’appel au ErrorPresenter pour afficher une erreur utilisateur.
     *
     * Ce test simule un échec de rendu de template dans Renderer::render()
     * et s’assure que le contrôleur appelle bien ErrorPresenterInterface::present().
     *
     * @return void
     */
    public function testShowLoginFormHandlesRenderException(): void
    {
        // Création d’un Renderer mocké qui lève une exception sur le layout
        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturnCallback(static function (string $view): string {
            if ($view === 'templates/default') {
                throw new \Exception('Rendering failed'); // Simule une panne de template
            }
            return '';
        });

        // Logger et Presenter mockés
        $logger    = $this->createMock(\Psr\Log\LoggerInterface::class);
        $presenter = $this->createMock(\GenshinTeam\Utils\ErrorPresenterInterface::class);

        // Session neutre
        $session = new SessionManager();

        // On s’attend à ce que le Presenter soit invoqué pour gérer l’exception
        $presenter->expects($this->once())->method('present');

        // Création du contrôleur avec la méthode redirect mockée (évite exit())
        $controller = $this->getMockBuilder(LoginController::class)
            ->setConstructorArgs([$renderer, $logger, $presenter, $session])
            ->onlyMethods(['redirect'])
            ->getMock();

        // Utilise la réflexion pour invoquer la méthode protégée showLoginForm()
        $refMethod = (new \ReflectionClass($controller))->getMethod('showLoginForm');
        $refMethod->setAccessible(true);

        // Appel réel de la méthode protégée dans un contexte contrôlé
        $refMethod->invoke($controller);
    }

    /**
     * Teste que la méthode handleLogin capture correctement les exceptions de type Throwable.
     *
     * Ce test simule une requête POST avec des données de connexion valides et un jeton CSRF valide.
     * Il utilise un mock du modèle UserModel configuré pour lancer une exception lors de l'appel à getUserByNickname().
     * Le contrôleur personnalisé surcharge handleException() pour indiquer si une exception a été capturée.
     * Le test vérifie que le bloc catch a bien été exécuté en s'assurant que la propriété $caught vaut true.
     *
     * @return void
     */
    public function testHandleLoginCatchesThrowable(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        // Simule les données postées avec un CSRF valide
        $_POST = [
            'nickname'   => 'TestUser',
            'password'   => 'SecurePass123!',
            'csrf_token' => 'valid_token',
        ];

        // Faux modèle qui déclenche une exception sur getUserByNickname()
        $userModel = $this->createMock(UserModel::class);
        $userModel->method('getUserByNickname')
            ->willThrowException(new \RuntimeException('Erreur simulée'));

        // Dépendances du contrôleur
        $renderer       = new Renderer($this->viewPath);
        $sessionManager = new SessionManager();
        $sessionManager->set('csrf_token', 'valid_token');
        $logger         = $this->createMock(LoggerInterface::class);
        $errorPresenter = $this->createMock(ErrorPresenterInterface::class);

        // Contrôleur personnalisé pour capturer l’appel à handleException()
        $controller = new class($renderer, $logger, $errorPresenter, $sessionManager, $userModel) extends LoginController
        {
            public bool $caught = false;

            protected function isCsrfTokenValid(): bool
            {return true;}
            protected function hasTooManyAttempts(): bool
            {return false;}
            protected function hasEmptyFields(string $nickname, string $password): bool
            {return false;}
            protected function handleException(\Throwable $e): void
            {$this->caught = true;}
        };

        // Appel de la méthode protégée via Reflection
        $ref    = new \ReflectionClass($controller);
        $method = $ref->getMethod('handleLogin');
        $method->setAccessible(true);
        $method->invoke($controller);

        // ✅ Vérifie que le bloc catch a bien été exécuté
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
        $renderer  = new Renderer($this->viewPath);
        $logger    = $this->createMock(LoggerInterface::class);
        $presenter = $this->createMock(ErrorPresenterInterface::class);
        $session   = new SessionManager();

        $controller = new LoginController($renderer, $logger, $presenter, $session);

        // L'appel ne doit rien faire, mais il ne doit surtout pas planter
        $controller->setCurrentRoute('index');

        // Tu peux ajouter une assertion vide ou une ligne de vérification basique
        $this->expectNotToPerformAssertions();
    }

}
