<?php
declare (strict_types = 1);

namespace Tests\Controllers;

use GenshinTeam\Controllers\ForgotPasswordController;
use GenshinTeam\Models\PasswordReset;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorPresenterInterface;
use Psr\Log\LoggerInterface;
use Tests\TestCase\DatabaseTestCase;

final class ForgotPasswordControllerTest extends DatabaseTestCase
{
    /** @var Renderer&\PHPUnit\Framework\MockObject\MockObject */
    private Renderer $renderer;
    private LoggerInterface $logger;
    private ErrorPresenterInterface $presenter;
    private SessionManager $session;
    private ForgotPasswordController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->renderer = $this->createMock(Renderer::class);
        $this->renderer->method('render')->willReturnCallback(function (string $view, array $data = []) {

            /** @var string $title */
            $title  = $data['title'] ?? '';
            $output = '<h1>' . $title . '</h1>';

            /** @var array{errors?: array<string, array<string>>, success?: string} $data */
            if (isset($data['errors'])) {
                foreach ($data['errors'] as $errorList) {
                    foreach ((array) $errorList as $msg) {
                        $output .= "<div class='error'>{$msg}</div>";
                    }
                }
            }

            if (! empty($data['success'])) {
                $output .= "<div class='success'>{$data['success']}</div>";
            }

            return $output;
        });

        $this->logger    = $this->createMock(LoggerInterface::class);
        $this->presenter = $this->createMock(ErrorPresenterInterface::class);
        $this->session   = new SessionManager();

        $this->controller = new ForgotPasswordController(
            $this->renderer,
            $this->logger,
            $this->presenter,
            $this->session
        );
    }

    /**
     * Vérifie l'affichage du formulaire de réinitialisation
     * lorsqu'une requête GET est reçue.
     *
     * @return void
     */
    public function testDisplayFormWhenGetRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        ob_start();
        $this->controller->run();
        $output = ob_get_clean();

        $this->assertNotFalse($output);
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('Réinitialisation de mot de passe', $output);
    }

    /**
     * Vérifie que le contrôleur rejette une requête POST avec
     * un token CSRF invalide.
     *
     * @return void
     */
    public function testRejectOnInvalidCsrfToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->session->set('csrf_token', 'expected');
        $_POST['csrf_token'] = 'wrong';

        ob_start();
        $this->controller->run();
        $output = ob_get_clean();

        $this->assertNotFalse($output);
        $this->assertStringContainsString('Requête invalide', $output);
    }

    /**
     * Vérifie que le contrôleur affiche une erreur
     * lorsque l'adresse email est vide.
     *
     * @return void
     */
    public function testRejectOnEmptyEmail(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST                     = ['csrf_token' => $this->session->get('csrf_token'), 'email' => ''];

        ob_start();
        $this->controller->run();
        $output = ob_get_clean();

        $this->assertNotFalse($output);
        $this->assertStringContainsString("L'adresse email est requise", $output);
    }

    /**
     * Vérifie que le contrôleur affiche une erreur
     * lorsque le format de l'email est incorrect.
     *
     * @return void
     */
    public function testRejectOnInvalidEmailFormat(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST                     = ['csrf_token' => $this->session->get('csrf_token'), 'email' => 'abc'];

        ob_start();
        $this->controller->run();
        $output = ob_get_clean();

        $this->assertNotFalse($output);
        $this->assertStringContainsString("Veuillez saisir une adresse email valide", $output);
    }

    /**
     * Vérifie qu'un message de confirmation est affiché
     * après soumission d'une adresse email valide.
     *
     * @return void
     */
    public function testSuccessMessageAfterValidEmailSubmission(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST                     = [
            'csrf_token' => $this->session->get('csrf_token'),
            'email'      => 'user@example.com',
        ];

        $mockResetModel = $this->createMock(PasswordReset::class);
        $mockResetModel->method('generateResetLink');

        $ref = new \ReflectionProperty($this->controller, 'resetModel');
        $ref->setAccessible(true);
        $ref->setValue($this->controller, $mockResetModel);

        ob_start();
        $this->controller->run();
        $output = ob_get_clean();

        $this->assertNotFalse($output);
        $this->assertStringContainsString("Si votre adresse est reconnue", $output);
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
     * Vérifie que handleException() est bien appelé en cas
     * d'erreur dans generateResetLink().
     *
     * @return void
     */
    public function testHandleRequestInvokesHandleExceptionOnError(): void
    {
        // Spy sur handleException()
        // Remplace le contrôleur par un spy réel via partialMock
        $spyController = $this->getMockBuilder(ForgotPasswordController::class)
            ->setConstructorArgs([$this->renderer, $this->logger, $this->presenter, $this->session])
            ->onlyMethods(['handleException']) // on ne mock QUE cette méthode
            ->getMock();

        // Maintenant, on injecte notre modèle mocké
        $mockResetModel = $this->createMock(\GenshinTeam\Models\PasswordReset::class);
        $mockResetModel->method('generateResetLink')->willThrowException(new \Exception('Erreur simulée'));

        $refModel = new \ReflectionProperty(ForgotPasswordController::class, 'resetModel');
        $refModel->setAccessible(true);
        $refModel->setValue($spyController, $mockResetModel);

        // On s’attend à ce que handleException soit appelé une fois avec l’exception
        $spyController
            ->expects($this->once())
            ->method('handleException')
            ->with($this->isInstanceOf(\Throwable::class));

        // Lancement
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST                     = ['csrf_token' => $this->session->get('csrf_token'), 'email' => 'user@example.com'];

        $spyController->run();

    }

    /**
     * Vérifie que showForm() intercepte correctement une exception
     * déclenchée lors du rendu de la vue.
     *
     * @return void
     */
    public function testShowFormCatchsRenderingException(): void
    {
        // Spy sur le contrôleur : on intercepte uniquement handleException()
        $spyController = $this->getMockBuilder(\GenshinTeam\Controllers\ForgotPasswordController::class)
            ->setConstructorArgs([$this->renderer, $this->logger, $this->presenter, $this->session])
            ->onlyMethods(['handleException'])
            ->getMock();

        // Simule une exception dans renderer->render()
        $this->renderer
            ->method('render')
            ->willThrowException(new \Exception('Erreur vue simulée'));

        // Attente : handleException doit être appelé avec l’exception
        $spyController
            ->expects($this->once())
            ->method('handleException')
            ->with($this->isInstanceOf(\Throwable::class));

        // Appel direct à showForm() (pas run) pour tester cette méthode isolée
        $reflection = new \ReflectionMethod($spyController, 'showForm');
        $reflection->setAccessible(true);
        $reflection->invoke($spyController);
    }

}
