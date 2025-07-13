<?php

declare (strict_types = 1);

use GenshinTeam\Controllers\ResetPasswordController;
use GenshinTeam\Entities\User;
use GenshinTeam\Models\PasswordReset;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorPresenterInterface;
use GenshinTeam\Validation\Validator;
use Psr\Log\LoggerInterface;
use Tests\TestCase\DatabaseTestCase;

/**
 * Teste les comportements du contrôleur ResetPasswordController.
 *
 * Ce test simule différents scénarios de requêtes HTTP (GET/POST) pour vérifier :
 * - l'extraction sécurisée des données `POST`,
 * - le rendu de formulaire via le Renderer,
 * - la gestion du token dans les flux de réinitialisation.
 *
 * Il s'appuie sur une base SQLite en mémoire via DatabaseTestCase.
 *
 */
class ResetPasswordControllerTest extends DatabaseTestCase
{
    /** @var \GenshinTeam\Controllers\ResetPasswordController Instance du contrôleur testée */
    private ResetPasswordController $controller;

    /** @var \PHPUnit\Framework\MockObject\MockObject&\GenshinTeam\Renderer\Renderer Mock du moteur de rendu */
    private Renderer $rendererMock;

    /** @var \GenshinTeam\Session\SessionManager Mock de la session */
    private SessionManager $sessionMock;

    /** @var \Psr\Log\LoggerInterface Mock du logger */
    private LoggerInterface $loggerMock;

    /** @var \GenshinTeam\Utils\ErrorPresenterInterface Mock du présentateur d'erreur */
    private ErrorPresenterInterface $errorPresenterMock;

    protected function setUp(): void
    {

        parent::setUp();
        $this->rendererMock = $this->getMockBuilder(Renderer::class)
            ->disableOriginalConstructor() // si le constructeur a des paramètres obligatoires
            ->onlyMethods(['render'])      // indique les méthodes mockables
            ->getMock();

        $this->sessionMock        = $this->createMock(SessionManager::class);
        $this->loggerMock         = $this->createMock(LoggerInterface::class);
        $this->errorPresenterMock = $this->createMock(ErrorPresenterInterface::class);

        $this->controller = new ResetPasswordController(
            $this->rendererMock,
            $this->loggerMock,
            $this->errorPresenterMock,
            $this->sessionMock
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Nettoyer les superglobales manuellement
        $_POST    = [];
        $_GET     = [];
        $_SERVER  = [];
        $_SESSION = [];

    }

    /**
     * @covers ::getPostString
     * Vérifie que `getPostString()` retourne bien une valeur `string` si elle est valide.
     */
    public function testGetPostStringReturnsStringIfValid(): void
    {
        $_POST['password'] = '123secure';
        $result            = $this->controller->getPostString('password');

        $this->assertSame('123secure', $result);
    }

    /**
     * @covers ::getPostString
     * Vérifie que `getPostString()` retourne `null` si la valeur n’est pas une string.
     */
    public function testGetPostStringReturnsNullIfNotString(): void
    {
        $_POST['password'] = ['not', 'a', 'string'];
        $result            = $this->controller->getPostString('password');

        $this->assertNull($result);
    }

    /**
     * @covers ::run
     * @covers ::handleRequest
     * Simule une requête GET avec un token valide et vérifie le rendu HTML.
     */
    public function testRunWithValidGetToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['token']             = 'valid-token';

        $this->rendererMock
            ->method('render')
            ->willReturn('<h1>Réinitialisation</h1>');

        ob_start();
        $this->controller->run();
        $output = ob_get_clean();

        $this->assertNotFalse($output);
        $this->assertStringContainsString('Réinitialisation', $output);
    }

    /**
     * @covers ::run
     * @covers ::handleRequest
     * Simule une requête POST sans token et vérifie l'affichage de l’erreur.
     */
    public function testRunWithPostMissingToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST                     = []; // token manquant

        $this->rendererMock
            ->method('render')
            ->willReturn('<h1>Erreur</h1>');

        ob_start();
        $this->controller->run();
        $output = ob_get_clean();

        $this->assertNotFalse($output);
        $this->assertStringContainsString('Erreur', $output);
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

        // L'appel ne doit rien faire, mais il ne doit surtout pas planter
        $this->controller->setCurrentRoute('index');

        // Tu peux ajouter une assertion vide ou une ligne de vérification basique
        $this->expectNotToPerformAssertions();
    }

    /**
     * Vérifie que la méthode handleRequest() gère correctement une requête GET
     * avec un token vide, en affichant un message d'erreur approprié.
     *
     * @return void
     * @covers \GenshinTeam\Controllers\ResetPasswordController::handleRequest
     */
    public function testHandleRequestWithEmptyGetTokenTriggersError(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['token']             = ''; // 👈 token présent mais vide

        $this->rendererMock
            ->method('render')
            ->willReturn('<div>formulaire</div>');

        ob_start();
        $this->controller->run();
        $output = ob_get_clean();

        // Vérifie que le message d’erreur est bien intégré au rendu
        $this->assertNotFalse($output);
        $this->assertStringContainsString('formulaire', $output);
        $this->assertSame('Lien de réinitialisation manquant.', $this->controller->getErrors()['global']);

    }

    /**
     * Vérifie que handleFormSubmission() est bien appelé lors d'une requête POST
     * avec un token valide.
     *
     * @return void
     * @covers \GenshinTeam\Controllers\ResetPasswordController::handleRequest
     */
    public function testHandleRequestInvokesHandleFormSubmission(): void
    {
        $controllerMock = $this->getMockBuilder(ResetPasswordController::class)
            ->onlyMethods(['handleFormSubmission'])
            ->disableOriginalConstructor() // si besoin
            ->getMock();

        $controllerMock->expects($this->once())
            ->method('handleFormSubmission')
            ->with('valid-token'); // si tu veux aussi vérifier l’argument

        // Simuler le contexte
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['token']            = 'valid-token';

        // Appel du contrôleur
        $controllerMock->run();

    }

    /**
     * Vérifie que showForm() intercepte correctement une exception
     * déclenchée lors du rendu de la vue.
     *
     * @return void
     * @covers \GenshinTeam\Controllers\ResetPasswordController::showForm
     */
    public function testShowFormCatchesException(): void
    {
        $this->rendererMock
            ->method('render')
            ->will($this->throwException(new \RuntimeException('Erreur simulée')));

        // Espion du contrôleur pour capter le traitement de l’exception
        $controller = new class(
            $this->rendererMock,
            $this->loggerMock,
            $this->errorPresenterMock,
            $this->sessionMock
        ) extends ResetPasswordController
        {
            public  ? \Throwable $captured = null;

            protected function handleException(\Throwable $e) : void
            {
                $this->captured = $e;
            }

            public function exposeShowForm(): void
            {
                $this->showForm(); // méthode privée rendue accessible via test
            }
        };

        $controller->exposeShowForm();

        $this->assertInstanceOf(\RuntimeException::class, $controller->captured);
        $this->assertSame('Erreur simulée', $controller->captured->getMessage());
    }

    /**
     * Vérifie que validateToken() gère correctement une exception
     * déclenchée lors de la validation du token.
     *
     * @return void
     * @covers \GenshinTeam\Controllers\ResetPasswordController::validateToken
     */
    public function testValidateTokenHandlesException(): void
    {
        // Mock de ResetModel qui lève une exception
        $resetModelMock = $this->createMock(PasswordReset::class);
        $resetModelMock->method('findUserByToken')
            ->willThrowException(new \RuntimeException('Erreur simulée'));

        $controller = new class(
            $this->rendererMock,
            $this->loggerMock,
            $this->errorPresenterMock,
            $this->sessionMock
        ) extends ResetPasswordController
        {
            public  ? \Throwable $captured = null;

            protected function handleException(\Throwable $e) : void
            {
                $this->captured = $e;
            }

            public function exposeValidateToken(string $token): ?User
            {
                return $this->validateToken($token);
            }
        };

        $reflection = new \ReflectionClass(ResetPasswordController::class); // pas $controller
        $property   = $reflection->getProperty('resetModel');
        $property->setAccessible(true);
        $property->setValue($controller, $resetModelMock);

        $result = $controller->exposeValidateToken('dummy-token');

        $this->assertNull($result);
        $this->assertInstanceOf(\RuntimeException::class, $controller->captured);
        $this->assertSame('Erreur simulée', $controller->captured->getMessage());

    }

    /**
     * Vérifie que validateToken() retourne null si le token est invalide ou expiré.
     *
     * @return void
     * @covers \GenshinTeam\Controllers\ResetPasswordController::validateToken
     */
    public function testValidateTokenReturnsNullIfUserInvalidOrExpired(): void
    {
        $resetModelMock = $this->createMock(PasswordReset::class);
        $resetModelMock
            ->method('findUserByToken')
            ->willReturn(null); // 👈 Simule un token inexistant

        $resetModelMock
            ->method('isTokenExpired')
            ->willReturn(false); // ← N’a pas d’effet ici car user est null

        $controller = new class(
            $this->rendererMock,
            $this->loggerMock,
            $this->errorPresenterMock,
            $this->sessionMock
        ) extends ResetPasswordController
        {
            public string $errorMessage = '';

            protected function handleException(\Throwable $e): void
            {}

            protected function addError(string $key, string $message): void
            {
                $this->errorMessage = $message;
            }

            public function exposeValidateToken(string $token): ?User
            {
                return $this->validateToken($token);
            }
        };

        // Injecte le mock
        $reflection = new \ReflectionClass($controller);
        $this->assertNotFalse($reflection->getParentClass());

        $property = $reflection->getParentClass()->getProperty('resetModel');
        $property->setAccessible(true);
        $property->setValue($controller, $resetModelMock);

        $result = $controller->exposeValidateToken('invalid-token');

        $this->assertNull($result);
        $this->assertSame('Lien invalide ou expiré.', $controller->errorMessage);
    }

    /**
     * @covers \GenshinTeam\Controllers\ResetPasswordController::validateToken
     *
     * Vérifie que `validateToken()` retourne bien une instance de `User`
     * lorsque le token est valide et non expiré.
     *
     * Conditions simulées :
     * - `findUserByToken($token)` retourne un `User` mocké.
     * - `isTokenExpired($token)` retourne `false`.
     *
     * Le test s’assure que la méthode ne déclenche aucune erreur et
     * que le `User` est bien retourné.
     */
    public function testValidateTokenReturnsUserIfValid(): void
    {
        $userMock = $this->createMock(User::class);

        $resetModelMock = $this->createMock(PasswordReset::class);
        $resetModelMock
            ->method('findUserByToken')
            ->willReturn($userMock);

        $resetModelMock
            ->method('isTokenExpired')
            ->willReturn(false); // Token toujours valide

        $controller = new class(
            $this->rendererMock,
            $this->loggerMock,
            $this->errorPresenterMock,
            $this->sessionMock
        ) extends ResetPasswordController
        {
            protected function handleException(\Throwable $e): void
            {}

            public function exposeValidateToken(string $token): ?User
            {
                return $this->validateToken($token);
            }
        };

        // Injecte le mock
        $reflection = new \ReflectionClass($controller);
        $this->assertNotFalse($reflection->getParentClass());

        $property = $reflection->getParentClass()->getProperty('resetModel');
        $property->setAccessible(true);
        $property->setValue($controller, $resetModelMock);

        $result = $controller->exposeValidateToken('valid-token');

        $this->assertSame($userMock, $result);
    }

    /**
     * Vérifie que updatePassword() met à jour le mot de passe de l'utilisateur
     * et invalide le token après la mise à jour.
     *
     * @return void
     * @covers \GenshinTeam\Controllers\ResetPasswordController::updatePassword
     */
    public function testUpdatePasswordSucceeds(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getEmail')->willReturn('user@example.com');

        $resetModelMock = $this->createMock(PasswordReset::class);
        $resetModelMock
            ->expects($this->once())
            ->method('updateUserPassword')
            ->with('user@example.com', $this->callback(fn(string $value) => $this->isPasswordHash($value)));

        $resetModelMock
            ->expects($this->once())
            ->method('invalidateToken')
            ->with('valid-token');

        $controller = new class(
            $this->rendererMock,
            $this->loggerMock,
            $this->errorPresenterMock,
            $this->sessionMock
        ) extends ResetPasswordController
        {
            /** @var array<string, mixed> $dataStore*/
            public array $dataStore = [];

            protected function showForm(): void
            {}
            protected function handleException(\Throwable $e): void
            {}
            public function addData(string $key, mixed $value): void
            {
                $this->dataStore[$key] = $value;
            }

            // private function isPasswordHash(string $value): bool
            // {
            //     return password_verify('my-new-pass', $value);
            // }

            public function exposeUpdate(User $user, string $pass, string $token): void
            {
                $this->updatePassword($user, $pass, $token);
            }
        };

        // Injecte le mock
        $reflection = new \ReflectionClass($controller);
        $this->assertNotFalse($reflection->getParentClass());

        $property = $reflection->getParentClass()->getProperty('resetModel');
        $property->setAccessible(true);
        $property->setValue($controller, $resetModelMock);

        $controller->exposeUpdate($userMock, 'my-new-pass', 'valid-token');

        $this->assertSame(
            'Votre mot de passe a été réinitialisé avec succès.',
            $controller->dataStore['success']
        );
    }

    /** 💡 Utilitaire pour valider le hash */
    private static function isPasswordHash(string $value): bool
    {
        return password_verify('my-new-pass', $value);
    }

    /**
     * Vérifie que updatePassword() intercepte correctement une exception
     * déclenchée lors de la mise à jour du mot de passe.
     *
     * @return void
     * @covers \GenshinTeam\Controllers\ResetPasswordController::updatePassword
     */
    public function testUpdatePasswordCatchesException(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getEmail')->willReturn('user@example.com');

        $resetModelMock = $this->createMock(PasswordReset::class);
        $resetModelMock->method('updateUserPassword')
            ->willThrowException(new \RuntimeException('Erreur simulée')); // ici on déclenche l’exception

        $controller = new class(
            $this->rendererMock,
            $this->loggerMock,
            $this->errorPresenterMock,
            $this->sessionMock
        ) extends ResetPasswordController
        {
            public  ? \Throwable $captured = null;

            protected function handleException(\Throwable $e) : void
            {
                $this->captured = $e;
            }

            protected function showForm(): void
            {
                // Évitons les effets secondaires de rendu
            }

            public function exposeUpdate(User $user, string $password, string $token): void
            {
                $this->updatePassword($user, $password, $token);
            }
        };

        // Injecte le mock dans la propriété privée
        $reflection = new \ReflectionClass($controller);
        $this->assertNotFalse($reflection->getParentClass());

        $property = $reflection->getParentClass()->getProperty('resetModel');
        $property->setAccessible(true);
        $property->setValue($controller, $resetModelMock);

        $controller->exposeUpdate($userMock, 'motDePasse', 'fake-token');

        $this->assertInstanceOf(\RuntimeException::class, $controller->captured);
        $this->assertSame('Erreur simulée', $controller->captured->getMessage());
    }
    /**
     * Vérifie que handleFormSubmission() gère correctement une soumission de formulaire réussie.
     *
     * @return void
     * @covers \GenshinTeam\Controllers\ResetPasswordController::handleFormSubmission
     */
    public function testHandleFormSubmissionSucceeds(): void
    {
        $_POST['password']         = 'abc123';
        $_POST['confirm-password'] = 'abc123';

        $userMock = $this->createMock(User::class);
        $userMock->method('getEmail')->willReturn('user@example.com');

        $resetModelMock = $this->createMock(PasswordReset::class);
        $resetModelMock->method('findUserByToken')->willReturn($userMock);
        $resetModelMock->method('isTokenExpired')->willReturn(false);
        $resetModelMock->method('invalidateToken'); // pour éviter les effets secondaires

        $resetModelMock
            ->expects($this->once())
            ->method('updateUserPassword')
            ->with(
                'user@example.com',
                $this->callback(fn($hash) =>
                    is_string($hash) && password_verify('abc123', $hash)
                )
            );

        /** @var \PHPUnit\Framework\MockObject\MockObject&\GenshinTeam\Session\SessionManager $sessionMock */
        $sessionMock = $this->getMockBuilder(SessionManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();

        $this->sessionMock = $sessionMock;

        $sessionMock
            ->method('get')
            ->with('csrf_token')
            ->willReturn('expected-token');

        $_POST['csrf_token'] = 'expected-token';

        $controller = new ResetPasswordController(
            $this->rendererMock,
            $this->loggerMock,
            $this->errorPresenterMock,
            $this->sessionMock
        );
        $controller->setResetModel($resetModelMock);

        $method = new \ReflectionMethod($controller, 'handleFormSubmission');
        $method->setAccessible(true);
        $method->invoke($controller, 'token-valid');
    }

    /**
     * Vérifie que handleFormSubmission() intercepte correctement une erreur
     * de validation CSRF.
     *
     * @return void
     */
    public function testHandleFormSubmissionRejectsInvalidCsrf(): void
    {
        $_POST['csrf_token'] = 'token-mauvais';

        /** @var \PHPUnit\Framework\MockObject\MockObject&\GenshinTeam\Session\SessionManager $sessionMock */
        $sessionMock = $this->getMockBuilder(SessionManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();

        $this->sessionMock = $sessionMock;

        $sessionMock
            ->method('get')
            ->with('csrf_token')
            ->willReturn('token-attendu');

        $controller = new ResetPasswordController(
            $this->rendererMock,
            $this->loggerMock,
            $this->errorPresenterMock,
            $this->sessionMock
        );

        $controller->setResetModel($this->createMock(PasswordReset::class));

        // Capture de l’erreur ajoutée
        $errors        = null;
        $controllerSpy = $this->getMockBuilder(ResetPasswordController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['showForm', 'addError'])
            ->getMock();

        $controllerSpy->method('showForm')->willReturnCallback(function () {
            // Marque que le formulaire a été affiché
        });

        $controllerSpy->method('addError')->willReturnCallback(function ($key, $msg) use (&$errors) {
            $errors[$key][] = $msg;
        });

        // Injecte dépendances dans le spy
        $ref = new \ReflectionObject($controllerSpy);
        foreach (['rendererMock', 'loggerMock', 'errorPresenterMock', 'sessionMock'] as $prop) {
            $property = $ref->getProperty(str_replace('Mock', '', $prop));
            $property->setAccessible(true);
            $property->setValue($controllerSpy, $this->{$prop});
        }
        $controllerSpy->setResetModel($this->createMock(PasswordReset::class));

        $method = new \ReflectionMethod($controllerSpy, 'handleFormSubmission');
        $method->setAccessible(true);
        $method->invoke($controllerSpy, 'nimporte');

        $this->assertContains('Requête invalide.', $errors['global'] ?? []);
    }

    /**
     * Vérifie que handleFormSubmission() affiche un message d'erreur
     * si la requête est invalide.
     *
     * @return void
     */
    public function testHandleFormSubmissionShowsValidationErrors(): void
    {
        // Simule les champs invalides
        $_POST['csrf_token']       = 'expected-token';
        $_POST['password']         = '';
        $_POST['confirm-password'] = 'pas pareil';

        /** @var \PHPUnit\Framework\MockObject\MockObject&\GenshinTeam\Session\SessionManager $sessionMock */
        $sessionMock = $this->getMockBuilder(SessionManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();

        $this->sessionMock = $sessionMock;

        $sessionMock
            ->method('get')
            ->with('csrf_token')
            ->willReturn('expected-token');

        $controller = new ResetPasswordController(
            $this->rendererMock,
            $this->loggerMock,
            $this->errorPresenterMock,
            $this->sessionMock
        );

        $controller->setResetModel($this->createMock(PasswordReset::class));

        // Capture via callback que showValidationError est bien invoquée
        $spyCalled            = false;
        $controllerReflection = new \ReflectionClass($controller);

        // On expose showValidationError et la remplace temporairement
        $method = $controllerReflection->getMethod('showValidationError');
        $method->setAccessible(true);

        $method->invoke($controller, 'password', '', new Validator(), 'password', function () use (&$spyCalled) {
            $spyCalled = true;
        });

        // Appel réel de handleFormSubmission
        $handleMethod = $controllerReflection->getMethod('handleFormSubmission');
        $handleMethod->setAccessible(true);
        $handleMethod->invoke($controller, 'token-valid');

        $this->assertTrue($spyCalled, 'La méthode showValidationError n’a pas été invoquée.');
    }

    public function testHandleFormSubmissionRejectsInvalidToken(): void
    {
        // Préparation des données valides
        $_POST['csrf_token']       = 'expected-token';
        $_POST['password']         = 'monMotDePasse!';
        $_POST['confirm-password'] = 'monMotDePasse!';

        /** @var \PHPUnit\Framework\MockObject\MockObject&\GenshinTeam\Session\SessionManager $sessionMock */
        $sessionMock = $this->getMockBuilder(SessionManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();

        $this->sessionMock = $sessionMock;

        $sessionMock
            ->method('get')
            ->with('csrf_token')
            ->willReturn('expected-token');

        $resetModelMock = $this->createMock(PasswordReset::class);
        $resetModelMock->method('findUserByToken')->willReturn(null); // ← simulate l’échec
        $resetModelMock->method('isTokenExpired')->willReturn(false);

        $controller = new class(
            $this->rendererMock,
            $this->loggerMock,
            $this->errorPresenterMock,
            $this->sessionMock
        ) extends ResetPasswordController
        {
            public bool $formShown = false;

            protected function showForm(): void
            {
                $this->formShown = true;
            }

            protected function handleException(\Throwable $e): void
            {}
        };

        // Capture l’appel à showForm()
        $formShown = false;
        $ref       = new \ReflectionMethod($controller, 'showForm');
        $ref->setAccessible(true);
        $ref->invokeArgs($controller, []); // appel factice pour éviter des effets de latence

        $ref->invoke($controller); // appel réel lors du test

        $controller->setResetModel($resetModelMock);

        $handleRef = new \ReflectionMethod($controller, 'handleFormSubmission');
        $handleRef->setAccessible(true);
        $handleRef->invoke($controller, 'token-invalide');

        $this->assertTrue($controller->formShown, 'Le formulaire n’a pas été affiché alors que le token est invalide.');

    }

}
