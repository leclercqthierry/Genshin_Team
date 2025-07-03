<?php
declare (strict_types = 1);

use GenshinTeam\Controllers\ObtainingController;
use GenshinTeam\Models\Obtaining;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorPresenterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitaires pour le contrôleur ObtainingController.
 *
 * Ces tests couvrent tous les cas d'usage métier du contrôleur :
 * - Ajout, édition, suppression de moyens d'obtention
 * - Affichage des formulaires et des listes
 * - Gestion des erreurs et des cas limites (ID inexistant, échec modèle, etc.)
 *
 * Les mocks sont utilisés pour isoler le contrôleur de ses dépendances (modèle, renderer, session, etc.).
 */
class ObtainingControllerTest extends TestCase
{
    /**
     * Instancie un ObtainingController avec des mocks pour tous ses services.
     */
    private function getController(
        ?Obtaining $model = null,
        string $route = 'add-obtaining'
    ): ObtainingController {
        $renderer       = $this->createMock(Renderer::class);
        $logger         = $this->createMock(LoggerInterface::class);
        $errorPresenter = $this->createMock(ErrorPresenterInterface::class);
        $session        = $this->createMock(SessionManager::class);
        $model          = $model ?: $this->createMock(Obtaining::class);

        $controller = new ObtainingController($renderer, $logger, $errorPresenter, $session, $model);
        $controller->setCurrentRoute($route);
        return $controller;
    }

    /**
     * Vérifie que le formulaire d'ajout est affiché et que renderDefault est appelé.
     */
    public function testShowAddForm(): void
    {
        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturn('form');
        $controller = $this->getMockBuilder(ObtainingController::class)
            ->setConstructorArgs([
                $renderer,
                $this->createMock(LoggerInterface::class),
                $this->createMock(ErrorPresenterInterface::class),
                $this->createMock(SessionManager::class),
                $this->createMock(Obtaining::class),
            ])
            ->onlyMethods(['renderDefault'])
            ->getMock();
        $controller->expects($this->once())->method('renderDefault');
        $ref = new ReflectionMethod($controller, 'showAddForm');
        $ref->setAccessible(true);
        ob_start();
        $ref->invoke($controller);
        ob_end_clean();
    }

    /**
     * Vérifie qu'un ajout valide appelle le modèle et affiche le succès.
     */
    public function testHandleAddValid(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['obtaining']        = 'Nouveau moyen';
        $model                     = $this->createMock(Obtaining::class);
        $model->expects($this->once())->method('add')->with('Nouveau moyen')->willReturn(true);
        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturn('success');
        $csrf                   = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $csrf;
        $_POST['csrf_token']    = $csrf;
        $session                = $this->createMock(SessionManager::class);
        $session->method('get')->with('csrf_token')->willReturn($csrf);
        $controller = new ObtainingController(
            $renderer,
            $this->createMock(LoggerInterface::class),
            $this->createMock(ErrorPresenterInterface::class),
            $session,
            $model
        );
        $controller->setCurrentRoute('add-obtaining');
        ob_start();
        $ref = new ReflectionMethod($controller, 'handleAdd');
        $ref->setAccessible(true);
        $ref->invoke($controller);
        $output = ob_get_clean();
        $this->assertStringContainsString('success', (string) $output);
    }

    /**
     * Vérifie qu'un échec d'ajout affiche le formulaire avec les erreurs et conserve les anciennes valeurs.
     */
    public function testHandleAddFailureCoversElse(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['obtaining']        = 'Nouveau moyen';
        $old                       = [];
        $csrf                      = bin2hex(random_bytes(32));
        $_SESSION['csrf_token']    = $csrf;
        $_POST['csrf_token']       = $csrf;
        $session                   = $this->createMock(SessionManager::class);
        $session->method('get')->willReturnCallback(function ($key) use (&$old, $csrf) {
            if ($key === 'old') {
                return $old;
            }

            if ($key === 'csrf_token') {
                return $csrf;
            }

            return null;
        });
        $session->method('set')->willReturnCallback(function ($key, $value) use (&$old) {
            if ($key === 'old') {
                $old = $value;
            }

        });
        $model = $this->createMock(Obtaining::class);
        $model->expects($this->once())->method('add')->with('Nouveau moyen')->willReturn(false);
        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturnCallback(function ($view, $data = []) {
            if ($view === 'obtaining/add-obtaining') {
                TestCase::assertIsArray($data);
                TestCase::assertArrayHasKey('errors', $data);
                TestCase::assertIsArray($data['errors']);
                TestCase::assertArrayHasKey('old', $data);
                TestCase::assertIsArray($data['old']);
                TestCase::assertSame('Nouveau moyen', $data['old']['obtaining']);
                TestCase::assertArrayHasKey('global', $data['errors']);
                TestCase::assertSame('Erreur lors de l\'ajout.', $data['errors']['global']);
                return 'form';
            }
            return '';
        });
        $controller = new ObtainingController(
            $renderer,
            $this->createMock(LoggerInterface::class),
            $this->createMock(ErrorPresenterInterface::class),
            $session,
            $model
        );
        ob_start();
        $ref = new ReflectionMethod($controller, 'handleAdd');
        $ref->setAccessible(true);
        $ref->invoke($controller);
        ob_end_clean();
    }

    /**
     * Vérifie que le formulaire de sélection d'édition est affiché.
     */
    public function testShowEditSelectForm(): void
    {
        $model = $this->createMock(Obtaining::class);
        $model->method('getAll')->willReturn([['id_obtaining' => 1, 'name' => 'obt1']]);
        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturn('select');
        $controller = $this->getMockBuilder(ObtainingController::class)
            ->setConstructorArgs([
                $renderer,
                $this->createMock(LoggerInterface::class),
                $this->createMock(ErrorPresenterInterface::class),
                $this->createMock(SessionManager::class),
                $model,
            ])
            ->onlyMethods(['renderDefault'])
            ->getMock();
        $controller->expects($this->once())->method('renderDefault');
        $ref = new ReflectionMethod($controller, 'showEditSelectForm');
        $ref->setAccessible(true);
        $ref->invoke($controller);
    }

    /**
     * Vérifie que l'édition avec un ID inexistant appelle showEditSelectForm.
     */
    public function testHandleEditNotFound(): void
    {
        $_POST['edit_id'] = 42;
        $model            = $this->createMock(Obtaining::class);
        $model->method('get')->with(42)->willReturn(null);
        $renderer       = $this->createMock(Renderer::class);
        $logger         = $this->createMock(LoggerInterface::class);
        $errorPresenter = $this->createMock(ErrorPresenterInterface::class);
        $session        = $this->createMock(SessionManager::class);
        $controller     = $this->getMockBuilder(ObtainingController::class)
            ->setConstructorArgs([$renderer, $logger, $errorPresenter, $session, $model])
            ->onlyMethods(['showEditSelectForm'])
            ->getMock();
        $controller->expects($this->once())->method('showEditSelectForm');
        $ref = new ReflectionMethod($controller, 'handleEdit');
        $ref->setAccessible(true);
        $ref->invoke($controller);
    }

    /**
     * Vérifie qu'une édition valide affiche le succès.
     */
    public function testHandleEditValid(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['edit_id']          = 1;
        $_POST['obtaining']        = 'Nouveau moyen';
        $model                     = $this->createMock(Obtaining::class);
        $model->method('get')->with(1)->willReturn(['id_obtaining' => 1, 'name' => 'Ancien moyen']);
        $model->method('update')->with(1, 'Nouveau moyen')->willReturn(true);
        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturn('success');
        $controller = new ObtainingController($renderer, $this->createMock(LoggerInterface::class), $this->createMock(ErrorPresenterInterface::class), $this->createMock(SessionManager::class), $model);
        $ref        = new ReflectionMethod($controller, 'handleEdit');
        $ref->setAccessible(true);
        ob_start();
        $ref->invoke($controller);
        $output = ob_get_clean();
        $this->assertStringContainsString('success', (string) $output);
    }

    /**
     * Vérifie qu'un échec d'édition affiche le formulaire avec les erreurs.
     */
    public function testHandleEditFailure(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['edit_id']          = 1;
        $_POST['obtaining']        = 'Nouveau moyen';
        $model                     = $this->createMock(Obtaining::class);
        $model->method('get')->with(1)->willReturn(['id_obtaining' => 1, 'name' => 'Ancien moyen']);
        $model->method('update')->with(1, 'Nouveau moyen')->willReturn(false);
        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturn('form');
        $controller = new ObtainingController($renderer, $this->createMock(LoggerInterface::class), $this->createMock(ErrorPresenterInterface::class), $this->createMock(SessionManager::class), $model);
        $ref        = new ReflectionMethod($controller, 'handleEdit');
        $ref->setAccessible(true);
        ob_start();
        $ref->invoke($controller);
        $output = ob_get_clean();
        $this->assertStringContainsString('form', (string) $output);
    }

    /**
     * Vérifie que le formulaire de sélection de suppression est affiché.
     */
    public function testShowDeleteSelectForm(): void
    {
        $model = $this->createMock(Obtaining::class);
        $model->method('getAll')->willReturn([['id_obtaining' => 1, 'name' => 'obt1']]);
        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturn('select');
        $controller = $this->getMockBuilder(ObtainingController::class)
            ->setConstructorArgs([
                $renderer,
                $this->createMock(LoggerInterface::class),
                $this->createMock(ErrorPresenterInterface::class),
                $this->createMock(SessionManager::class),
                $model,
            ])
            ->onlyMethods(['renderDefault'])
            ->getMock();
        $controller->expects($this->once())->method('renderDefault');
        $ref = new ReflectionMethod($controller, 'showDeleteSelectForm');
        $ref->setAccessible(true);
        $ref->invoke($controller);
    }

    /**
     * Vérifie que la suppression avec un ID inexistant appelle showDeleteSelectForm.
     */
    public function testHandleDeleteNotFound(): void
    {
        $_POST['delete_id'] = 42;
        $model              = $this->createMock(Obtaining::class);
        $model->method('get')->with(42)->willReturn(null);
        $renderer       = $this->createMock(Renderer::class);
        $logger         = $this->createMock(LoggerInterface::class);
        $errorPresenter = $this->createMock(ErrorPresenterInterface::class);
        $session        = $this->createMock(SessionManager::class);
        $controller     = $this->getMockBuilder(ObtainingController::class)
            ->setConstructorArgs([$renderer, $logger, $errorPresenter, $session, $model])
            ->onlyMethods(['showDeleteSelectForm'])
            ->getMock();
        $controller->expects($this->once())->method('showDeleteSelectForm');
        $ref = new ReflectionMethod($controller, 'handleDelete');
        $ref->setAccessible(true);
        $ref->invoke($controller);
    }

    /**
     * Vérifie qu'une suppression confirmée et réussie affiche le succès.
     */
    public function testHandleDeleteValid(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['delete_id']        = 1;
        $_POST['confirm_delete']   = 1;
        $model                     = $this->createMock(Obtaining::class);
        $model->method('delete')->with(1)->willReturn(true);
        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturn('success');
        $controller = new ObtainingController($renderer, $this->createMock(LoggerInterface::class), $this->createMock(ErrorPresenterInterface::class), $this->createMock(SessionManager::class), $model);
        $ref        = new ReflectionMethod($controller, 'handleDelete');
        $ref->setAccessible(true);
        ob_start();
        $ref->invoke($controller);
        $output = ob_get_clean();
        $this->assertStringContainsString('success', (string) $output);
    }

    /**
     * Vérifie qu'un échec de suppression affiche le formulaire de fallback.
     */
    public function testHandleDeleteFailure(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['delete_id']        = 1;
        $_POST['confirm_delete']   = 1;
        $model                     = $this->createMock(Obtaining::class);
        $model->method('delete')->with(1)->willReturn(false);
        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturn('form');
        $controller = new ObtainingController($renderer, $this->createMock(LoggerInterface::class), $this->createMock(ErrorPresenterInterface::class), $this->createMock(SessionManager::class), $model);
        $ref        = new ReflectionMethod($controller, 'handleDelete');
        $ref->setAccessible(true);
        ob_start();
        $ref->invoke($controller);
        $output = ob_get_clean();
        $this->assertStringContainsString('form', (string) $output);
    }

    /**
     * Vérifie que la liste des moyens d'obtention est affichée.
     */
    public function testShowList(): void
    {
        $model = $this->createMock(Obtaining::class);
        $model->method('getAll')->willReturn([['id_obtaining' => 1, 'name' => 'obt1']]);
        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturnCallback(function ($view, $data = []) {
            if ($view === 'obtaining/obtaining-list') {
                return 'liste';
            }
            return '';
        });
        $controller = $this->getMockBuilder(ObtainingController::class)
            ->setConstructorArgs([
                $renderer,
                $this->createMock(LoggerInterface::class),
                $this->createMock(ErrorPresenterInterface::class),
                $this->createMock(SessionManager::class),
                $model,
            ])
            ->onlyMethods(['renderDefault'])
            ->getMock();
        $controller->expects($this->once())->method('renderDefault');
        $ref = new ReflectionMethod($controller, 'showList');
        $ref->setAccessible(true);
        $ref->invoke($controller);
    }

    /**
     * Vérifie que la route d'ajout est correcte.
     */
    public function testGetAddRoute(): void
    {
        $controller = $this->getController();
        $ref        = new ReflectionMethod($controller, 'getAddRoute');
        $ref->setAccessible(true);
        $this->assertSame('add-obtaining', $ref->invoke($controller));
    }

    /**
     * Vérifie que la route d'édition est correcte.
     */
    public function testGetEditRoute(): void
    {
        $controller = $this->getController();
        $ref        = new ReflectionMethod($controller, 'getEditRoute');
        $ref->setAccessible(true);
        $this->assertSame('edit-obtaining', $ref->invoke($controller));
    }

    /**
     * Vérifie que la route de suppression est correcte.
     */
    public function testGetDeleteRoute(): void
    {
        $controller = $this->getController();
        $ref        = new ReflectionMethod($controller, 'getDeleteRoute');
        $ref->setAccessible(true);
        $this->assertSame('delete-obtaining', $ref->invoke($controller));
    }

    /**
     * Vérifie que processEdit affiche le succès si la mise à jour réussit.
     */
    public function testProcessEditSuccess(): void
    {
        $model = $this->createMock(Obtaining::class);
        $model->method('update')->with(1, 'Nouveau moyen')->willReturn(true);
        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturn('success');
        $controller = new ObtainingController(
            $renderer,
            $this->createMock(LoggerInterface::class),
            $this->createMock(ErrorPresenterInterface::class),
            $this->createMock(SessionManager::class),
            $model
        );
        $ref = new ReflectionMethod($controller, 'processEdit');
        $ref->setAccessible(true);
        ob_start();
        $ref->invoke($controller, 1, 'Nouveau moyen');
        $output = ob_get_clean();
        $this->assertStringContainsString('success', (string) $output);
    }

    /**
     * Vérifie que processEdit appelle showEditForm en cas d'échec.
     */
    public function testProcessEditFailure(): void
    {
        $model = $this->createMock(Obtaining::class);
        $model->method('update')->with(1, 'Nouveau moyen')->willReturn(false);
        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturn('form');
        $controller = $this->getMockBuilder(ObtainingController::class)
            ->setConstructorArgs([
                $renderer,
                $this->createMock(LoggerInterface::class),
                $this->createMock(ErrorPresenterInterface::class),
                $this->createMock(SessionManager::class),
                $model,
            ])
            ->onlyMethods(['showEditForm'])
            ->getMock();
        $controller->expects($this->once())->method('showEditForm')->with(1);
        $ref = new ReflectionMethod($controller, 'processEdit');
        $ref->setAccessible(true);
        $ref->invoke($controller, 1, 'Nouveau moyen');
    }

    /**
     * Vérifie que processDelete affiche le succès si la suppression réussit.
     */
    public function testProcessDeleteSuccess(): void
    {
        $model = $this->createMock(Obtaining::class);
        $model->method('delete')->with(1)->willReturn(true);
        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturn('success');
        $controller = new ObtainingController(
            $renderer,
            $this->createMock(LoggerInterface::class),
            $this->createMock(ErrorPresenterInterface::class),
            $this->createMock(SessionManager::class),
            $model
        );
        $ref = new ReflectionMethod($controller, 'processDelete');
        $ref->setAccessible(true);
        ob_start();
        $ref->invoke($controller, 1);
        $output = ob_get_clean();
        $this->assertStringContainsString('success', (string) $output);
    }

    /**
     * Vérifie que processDelete appelle showDeleteSelectForm en cas d'échec.
     */
    public function testProcessDeleteFailure(): void
    {
        $model = $this->createMock(Obtaining::class);
        $model->method('delete')->with(1)->willReturn(false);
        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturn('form');
        $controller = $this->getMockBuilder(ObtainingController::class)
            ->setConstructorArgs([
                $renderer,
                $this->createMock(LoggerInterface::class),
                $this->createMock(ErrorPresenterInterface::class),
                $this->createMock(SessionManager::class),
                $model,
            ])
            ->onlyMethods(['showDeleteSelectForm'])
            ->getMock();
        $controller->expects($this->once())->method('showDeleteSelectForm');
        $ref = new ReflectionMethod($controller, 'processDelete');
        $ref->setAccessible(true);
        $ref->invoke($controller, 1);
    }

    /**
     * Vérifie que showDeleteConfirmForm appelle showDeleteSelectForm si l'ID n'existe pas.
     */
    public function testShowDeleteConfirmFormNotFound(): void
    {
        $model = $this->createMock(Obtaining::class);
        $model->method('get')->with(99)->willReturn(null);
        $renderer   = $this->createMock(Renderer::class);
        $controller = $this->getMockBuilder(ObtainingController::class)
            ->setConstructorArgs([
                $renderer,
                $this->createMock(LoggerInterface::class),
                $this->createMock(ErrorPresenterInterface::class),
                $this->createMock(SessionManager::class),
                $model,
            ])
            ->onlyMethods(['showDeleteSelectForm'])
            ->getMock();
        $controller->expects($this->once())->method('showDeleteSelectForm');
        $ref = new ReflectionMethod($controller, 'showDeleteConfirmForm');
        $ref->setAccessible(true);
        $ref->invoke($controller, 99);
    }

    /**
     * Vérifie que showDeleteConfirmForm affiche la confirmation si l'ID existe.
     */
    public function testShowDeleteConfirmFormDisplaysConfirmation(): void
    {
        $id     = 1;
        $record = ['id_obtaining' => $id, 'name' => 'obt1'];

        $model = $this->createMock(Obtaining::class);
        $model->method('get')->with($id)->willReturn($record);

        $renderer = $this->createMock(Renderer::class);
        $renderer->expects($this->once())
            ->method('render')
            ->with(
                'obtaining/delete-obtaining-confirm',
                $this->callback(function ($data) use ($record, $id) {
                    TestCase::assertIsArray($data);
                    TestCase::assertArrayHasKey('obtaining', $data);
                    TestCase::assertArrayHasKey('id', $data);
                    TestCase::assertArrayHasKey('errors', $data);
                    return $data['obtaining'] === $record && $data['id'] === $id && isset($data['errors']);
                })
            )
            ->willReturn('confirm');

        $controller = $this->getMockBuilder(ObtainingController::class)
            ->setConstructorArgs([
                $renderer,
                $this->createMock(LoggerInterface::class),
                $this->createMock(ErrorPresenterInterface::class),
                $this->createMock(SessionManager::class),
                $model,
            ])
            ->onlyMethods(['renderDefault'])
            ->getMock();

        $controller->expects($this->once())->method('renderDefault');

        $ref = new ReflectionMethod($controller, 'showDeleteConfirmForm');
        $ref->setAccessible(true);

        $ref->invoke($controller, $id);
    }
}
