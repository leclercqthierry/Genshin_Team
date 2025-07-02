<?php
declare (strict_types = 1);

use GenshinTeam\Controllers\StatController;
use GenshinTeam\Models\Stat;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorPresenterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Fournit une instance de StatController entièrement mockée pour les tests.
 *
 * Cette méthode crée des mocks pour toutes les dépendances nécessaires :
 * - Renderer : pour simuler le rendu des vues
 * - LoggerInterface : pour intercepter la journalisation
 * - ErrorPresenterInterface : pour éviter les effets de bord
 * - SessionManager : pour émuler la session utilisateur
 * - Stat : le modèle métier, pouvant être surchargé via l’argument
 *
 * @param Stat|null $model Modèle stat personnalisé (ou mocké par défaut)
 * @param string    $route Nom de la route actuelle (ex : 'add-stat')
 *
 * @return StatController Instance prête à être testée
 */
class StatControllerTest extends TestCase
{
    private function getController(
        ?Stat $model = null,
        string $route = 'add-stat'
    ): StatController {
        $renderer       = $this->createMock(Renderer::class);
        $logger         = $this->createMock(LoggerInterface::class);
        $errorPresenter = $this->createMock(ErrorPresenterInterface::class);
        $session        = $this->createMock(SessionManager::class);
        $model          = $model ?: $this->createMock(Stat::class);

        $controller = new StatController($renderer, $logger, $errorPresenter, $session, $model);
        $controller->setCurrentRoute($route);
        return $controller;
    }

    /**
     * Vérifie que le formulaire d’ajout est affiché correctement via la méthode `showAddForm()`.
     *
     * Ce test :
     * - mocke le renderer pour simuler le rendu de la vue
     * - s'assure que `renderDefault()` est bien appelée comme effet de bord
     *
     * @return void
     */
    public function testShowAddForm(): void
    {
        $renderer       = $this->createMock(Renderer::class);
        $logger         = $this->createMock(LoggerInterface::class);
        $errorPresenter = $this->createMock(ErrorPresenterInterface::class);
        $session        = $this->createMock(SessionManager::class);
        $model          = $this->createMock(Stat::class);

        $renderer->method('render')->willReturn('form');

        $controller = $this->getMockBuilder(StatController::class)
            ->setConstructorArgs([$renderer, $logger, $errorPresenter, $session, $model])
            ->onlyMethods(['renderDefault'])
            ->getMock();
        $controller->expects($this->once())->method('renderDefault');

        $ref = new ReflectionMethod($controller, 'showAddForm');
        $ref->setAccessible(true);

        ob_start();
        $ref->invoke($controller);
        $output = ob_get_clean();

    }

    /**
     * Vérifie le comportement de `handleAdd()` lors d’un ajout réussi.
     *
     * Ce test :
     * - simule une requête POST contenant une statistique valide
     * - mocke le modèle pour retourner true sur `add()`
     * - capture le rendu et vérifie que le message de succès est présent
     *
     * @return void
     */
    public function testHandleAddValid(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['stat']             = 'Nouvelle stat';

        $model = $this->createMock(Stat::class);
        $model->expects($this->once())->method('add')->with('Nouvelle stat')->willReturn(true);

        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturn('success');

        $csrf                   = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $csrf;
        $_POST['csrf_token']    = $csrf;

        $session = $this->createMock(SessionManager::class);
        $session->method('get')->with('csrf_token')->willReturn($csrf);

        $controller = new StatController(
            $renderer,
            $this->createMock(LoggerInterface::class),
            $this->createMock(ErrorPresenterInterface::class),
            $session,
            $model
        );

        $controller->setCurrentRoute('add-stat');

        ob_start();
        $ref = new ReflectionMethod($controller, 'handleAdd');
        $ref->setAccessible(true);
        $ref->invoke($controller);
        $output = ob_get_clean();

        $this->assertStringContainsString('success', (string) $output);
    }

    /**
     * Vérifie que `handleAdd()` gère correctement une statistique vide.
     *
     * Ce test :
     * - envoie une requête POST avec une statistique vide
     * - vérifie que les erreurs sont passées à la vue
     * - s'assure que le formulaire est affiché avec les anciennes valeurs
     *
     * @return void
     */
    public function testHandleAddEmptyStat(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['stat']             = '';

        $errors = [];
        $old    = [];

        // Ajout d'un token CSRF valide
        $csrf                   = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $csrf;
        $_POST['csrf_token']    = $csrf;

        $session = $this->createMock(SessionManager::class);
        $session->method('get')->willReturnCallback(function ($key) use (&$errors, &$old, $csrf) {
            if ($key === 'errors') {
                return $errors;
            }

            if ($key === 'old') {
                return $old;
            }

            if ($key === 'csrf_token') {
                return $csrf;
            }

            return null;
        });
        $session->method('set')->willReturnCallback(function ($key, $value) use (&$errors, &$old) {
            if ($key === 'errors') {
                // Fusionne les erreurs pour simuler addError()
                $errors = array_merge((array) $errors, (array) $value);
            }

            if ($key === 'old') {
                $old = $value;
            }

        });

        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturnCallback(function ($view, $data = []) {
            if ($view === 'stats/add-stat') {
                TestCase::assertIsArray($data);
                TestCase::assertArrayHasKey('errors', $data);
                TestCase::assertIsArray($data['errors']);
                TestCase::assertArrayHasKey('stat', $data['errors']);
                TestCase::assertSame('Veuillez ajouter une statistique.', $data['errors']['stat']);
                TestCase::assertArrayHasKey('old', $data);
                TestCase::assertIsArray($data['old']);
                TestCase::assertSame('', $data['old']['stat']);
            }
            return 'form';
        });

        $logger         = $this->createMock(LoggerInterface::class);
        $errorPresenter = $this->createMock(ErrorPresenterInterface::class);
        $model          = $this->createMock(Stat::class);

        $controller = $this->getMockBuilder(StatController::class)
            ->setConstructorArgs([$renderer, $logger, $errorPresenter, $session, $model])
            ->onlyMethods(['renderDefault'])
            ->getMock();
        $controller->expects($this->once())->method('renderDefault');

        $ref = new ReflectionMethod($controller, 'handleAdd');
        $ref->setAccessible(true);

        $ref->invoke($controller);
    }

    /**
     * Vérifie que `handleAdd()` affiche le formulaire si la requête n’est pas de type POST.
     *
     * Ce test :
     * - simule l’absence de `$_SERVER['REQUEST_METHOD']`
     * - vérifie que le formulaire d’ajout est correctement affiché par défaut
     *
     * @return void
     */
    public function testHandleAddShowsFormOnGet(): void
    {
        unset($_SERVER['REQUEST_METHOD']);
        $_POST = [];

        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturn('form');

        $controller = $this->getMockBuilder(StatController::class)
            ->setConstructorArgs([
                $renderer,
                $this->createMock(LoggerInterface::class),
                $this->createMock(ErrorPresenterInterface::class),
                $this->createMock(SessionManager::class),
                $this->createMock(Stat::class),
            ])
            ->onlyMethods(['renderDefault'])
            ->getMock();

        $controller->expects($this->once())->method('renderDefault');

        $ref = new ReflectionMethod($controller, 'handleAdd');
        $ref->setAccessible(true);

        $ref->invoke($controller);
    }

    /**
     * Vérifie que `handleAdd()` gère correctement un échec de création en base.
     *
     * Ce test :
     * - envoie une requête POST avec une statistique valide
     * - force l’échec de la méthode `add()`
     * - vérifie que les erreurs et anciennes valeurs sont passées à la vue
     *
     * @return void
     */
    public function testHandleAddFailureCoversElse(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['stat']             = 'Nouvelle stat';

        $old                    = [];
        $csrf                   = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $csrf;
        $_POST['csrf_token']    = $csrf;

        $session = $this->createMock(SessionManager::class);
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

        $model = $this->createMock(Stat::class);
        $model->expects($this->once())->method('add')->with('Nouvelle stat')->willReturn(false);

        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturnCallback(function ($view, $data = []) {
            if ($view === 'stats/add-stat') {
                TestCase::assertIsArray($data);
                TestCase::assertArrayHasKey('errors', $data);
                TestCase::assertIsArray($data['errors']);
                TestCase::assertArrayHasKey('old', $data);
                TestCase::assertIsArray($data['old']);
                TestCase::assertSame('Nouvelle stat', $data['old']['stat']);
                TestCase::assertArrayHasKey('global', $data['errors']);
                TestCase::assertSame('Erreur lors de l\'ajout.', $data['errors']['global']);
                return 'form';
            }
            return '';
        });

        $controller = new StatController(
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
     * Vérifie que `handleAdd()` gère correctement un token CSRF invalide.
     *
     * Ce test :
     * - simule une requête POST avec un token CSRF invalide
     * - vérifie que les erreurs sont passées à la vue
     * - s'assure que le formulaire est affiché avec les anciennes valeurs
     *
     * @return void
     */
    public function testHandleAddCsrfInvalid(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['stat']             = 'Nouvelle stat';

        // Simule un token CSRF invalide
        $_SESSION['csrf_token'] = 'token_session';
        $_POST['csrf_token']    = 'token_post_different';

        $errors = [];
        $old    = [];

        $session = $this->createMock(SessionManager::class);
        $session->method('get')->willReturnCallback(function ($key) use (&$errors, &$old) {
            if ($key === 'errors') {
                return $errors;
            }

            if ($key === 'old') {
                return $old;
            }

            if ($key === 'csrf_token') {
                return 'token_session';
            }

            return null;
        });
        $session->method('set')->willReturnCallback(function ($key, $value) use (&$errors, &$old) {
            if ($key === 'errors') {
                $errors = $value;
            }

            if ($key === 'old') {
                $old = $value;
            }

        });

        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturnCallback(function ($view, $data = []) {
            if ($view === 'stats/add-stat') {
                TestCase::assertIsArray($data);
                TestCase::assertArrayHasKey('errors', $data);
                TestCase::assertIsArray($data['errors']);
                TestCase::assertSame('Requête invalide ! Veuillez réessayer.', $data['errors']['global']);
            }
            return 'form';
        });

        $logger         = $this->createMock(LoggerInterface::class);
        $errorPresenter = $this->createMock(ErrorPresenterInterface::class);
        $model          = $this->createMock(Stat::class);

        $controller = $this->getMockBuilder(StatController::class)
            ->setConstructorArgs([$renderer, $logger, $errorPresenter, $session, $model])
            ->onlyMethods(['renderDefault'])
            ->getMock();
        $controller->expects($this->once())->method('renderDefault');

        $ref = new ReflectionMethod($controller, 'handleAdd');
        $ref->setAccessible(true);

        $ref->invoke($controller);
    }

    /**
     * Vérifie que `showEditSelectForm()` utilise bien le modèle pour récupérer les éléments
     * et que `renderDefault()` est appelé pour afficher la sélection.
     *
     * Ce test :
     * - simule la méthode `getAll()` du modèle
     * - vérifie que la vue 'select' est rendue et que le fallback est déclenché
     *
     * @return void
     */
    public function testShowEditSelectForm(): void
    {
        $model = $this->createMock(Stat::class);
        $model->method('getAll')->willReturn([['id_stat' => 1, 'name' => 'stat1']]);

        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturn('select');

        $controller = $this->getMockBuilder(StatController::class)
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
     * Vérifie que handleEdit() appelle `showEditSelectForm()` si aucun edit_id n’est fourni.
     *
     * Ce test :
     * - simule une requête POST vide
     * - utilise un mock de contrôleur avec une méthode protégée
     * - s’assure que le fallback `showEditSelectForm()` est bien déclenché
     *
     * @return void
     */
    public function testHandleEditNoEditId(): void
    {
        $_POST      = [];
        $controller = $this->getController();
        $ref        = new ReflectionMethod($controller, 'handleEdit');
        $ref->setAccessible(true);

        // On mocke showEditSelectForm pour vérifier qu'elle est appelée
        $mock = $this->getMockBuilder(StatController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['showEditSelectForm'])
            ->getMock();
        $mock->expects($this->once())->method('showEditSelectForm');

        $ref = new ReflectionMethod($mock, 'handleEdit');
        $ref->setAccessible(true);

        $ref->invoke($mock);
    }

    /**
     * Vérifie que handleEdit() appelle `showEditSelectForm()` si l’ID fourni n’est pas un entier valide.
     *
     * @return void
     */
    public function testHandleEditInvalidId(): void
    {
        $_POST['edit_id'] = 'abc'; // id invalide

        $controller = $this->getController();
        $mock       = $this->getMockBuilder(StatController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['showEditSelectForm'])
            ->getMock();
        $mock->expects($this->once())->method('showEditSelectForm');

        $ref = new ReflectionMethod($mock, 'handleEdit');
        $ref->setAccessible(true);

        $ref->invoke($mock);
    }

    /**
     * Vérifie que handleEdit() appelle `showEditSelectForm()` si l’enregistrement est introuvable.
     *
     * Ce test :
     * - fournit un ID inexistant
     * - configure le modèle pour retourner null sur `get()`
     * - s’assure que la méthode fallback est bien appelée
     *
     * @return void
     */
    public function testHandleEditNotFound(): void
    {
        $_POST['edit_id'] = 42;
        $model            = $this->createMock(Stat::class);
        $model->method('get')->with(42)->willReturn(null);

        $renderer       = $this->createMock(Renderer::class);
        $logger         = $this->createMock(LoggerInterface::class);
        $errorPresenter = $this->createMock(ErrorPresenterInterface::class);
        $session        = $this->createMock(SessionManager::class);

        $controller = $this->getMockBuilder(StatController::class)
            ->setConstructorArgs([$renderer, $logger, $errorPresenter, $session, $model])
            ->onlyMethods(['showEditSelectForm'])
            ->getMock();

        $controller->expects($this->once())->method('showEditSelectForm');

        $ref = new ReflectionMethod($controller, 'handleEdit');
        $ref->setAccessible(true);

        $ref->invoke($controller);
    }

    /**
     * Vérifie qu’une édition valide déclenche le rendu de confirmation (ex: "success").
     *
     * Ce test :
     * - fournit un edit_id et une nouvelle valeur de stat
     * - configure le modèle pour réussir la mise à jour
     * - capture la sortie rendue
     * - vérifie que le mot-clé "success" est présent dans le rendu
     *
     * @return void
     */
    public function testHandleEditValid(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['edit_id']          = 1;
        $_POST['stat']             = 'Nouvelle stat';

        $model = $this->createMock(Stat::class);
        $model->method('get')->with(1)->willReturn(['id_stat' => 1, 'name' => 'Ancienne stat']);
        $model->method('update')->with(1, 'Nouvelle stat')->willReturn(true);

        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturn('success');

        $controller = new StatController($renderer, $this->createMock(LoggerInterface::class), $this->createMock(ErrorPresenterInterface::class), $this->createMock(SessionManager::class), $model);

        $ref = new ReflectionMethod($controller, 'handleEdit');
        $ref->setAccessible(true);

        ob_start();
        $ref->invoke($controller);
        $output = ob_get_clean();

        $this->assertStringContainsString('success', (string) $output);
    }

    /**
     * Vérifie que handleEdit() gère correctement une statistique vide.
     *
     * Ce test :
     * - simule une requête POST avec un champ `stat` vide
     * - vérifie que les erreurs sont passées à la vue
     * - s'assure que le formulaire est affiché avec les anciennes valeurs
     *
     * @return void
     */
    public function testHandleEditEmptyStat(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['edit_id']          = 1;
        $_POST['stat']             = '';

        $errors = [];
        $old    = [];

        $csrf                   = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $csrf;
        $_POST['csrf_token']    = $csrf;

        $session = $this->createMock(SessionManager::class);
        $session->method('get')->willReturnCallback(function ($key) use (&$errors, &$old, $csrf) {
            if ($key === 'errors') {
                return $errors;
            }

            if ($key === 'old') {
                return $old;
            }

            if ($key === 'csrf_token') {
                return $csrf;
            }

            return null;
        });
        $session->method('set')->willReturnCallback(function ($key, $value) use (&$errors, &$old) {
            if ($key === 'errors') {
                $errors = array_merge((array) $errors, (array) $value);
            }

            if ($key === 'old') {
                $old = $value;
            }

        });

        $model = $this->createMock(Stat::class);
        $model->method('get')->with(1)->willReturn(['id_stat' => 1, 'name' => 'Ancienne stat']);

        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturnCallback(function ($view, $data = []) {
            if ($view === 'stats/add-stat') {
                TestCase::assertIsArray($data);
                TestCase::assertArrayHasKey('errors', $data);
                TestCase::assertIsArray($data['errors']);
                TestCase::assertArrayHasKey('stat', $data['errors']);
                TestCase::assertSame('Veuillez ajouter une statistique.', $data['errors']['stat']);
                TestCase::assertArrayHasKey('old', $data);
                TestCase::assertIsArray($data['old']);
                TestCase::assertSame('Ancienne stat', $data['old']['stat']);

            }
            return 'form';
        });

        $logger         = $this->createMock(LoggerInterface::class);
        $errorPresenter = $this->createMock(ErrorPresenterInterface::class);

        $controller = $this->getMockBuilder(StatController::class)
            ->setConstructorArgs([$renderer, $logger, $errorPresenter, $session, $model])
            ->onlyMethods(['renderDefault'])
            ->getMock();
        $controller->expects($this->once())->method('renderDefault');

        $ref = new ReflectionMethod($controller, 'handleEdit');
        $ref->setAccessible(true);

        $ref->invoke($controller);
    }

    /**
     * Vérifie que le contrôleur affiche à nouveau le formulaire si la mise à jour échoue.
     *
     * Ce test :
     * - simule une requête POST avec un champ `stat` et un `edit_id` existant
     * - configure le modèle pour renvoyer un enregistrement existant
     * - force l’échec de la méthode `update()`
     * - vérifie que le rendu contient bien le mot-clé `form`
     *
     * @return void
     */
    public function testHandleEditFailure(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['edit_id']          = 1;
        $_POST['stat']             = 'Nouvelle stat';

        $model = $this->createMock(Stat::class);
        $model->method('get')->with(1)->willReturn(['id_stat' => 1, 'name' => 'Ancienne stat']);
        $model->method('update')->with(1, 'Nouvelle stat')->willReturn(false);

        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturn('form');

        $controller = new StatController($renderer, $this->createMock(LoggerInterface::class), $this->createMock(ErrorPresenterInterface::class), $this->createMock(SessionManager::class), $model);

        $ref = new ReflectionMethod($controller, 'handleEdit');
        $ref->setAccessible(true);

        ob_start();
        $ref->invoke($controller);
        $output = ob_get_clean();

        $this->assertStringContainsString('form', (string) $output);
    }
    /**
     * Vérifie que handleEdit() traite correctement une mise à jour réussie.
     *
     * Ce test :
     * - simule une requête POST avec un champ `stat` et un `edit_id` existant
     * - configure le modèle pour renvoyer un enregistrement existant
     * - force la réussite de la méthode `update()`
     * - vérifie que le rendu contient bien le mot-clé `success`
     *
     * @return void
     */
    public function testProcessEditSuccess(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['edit_id']          = 1;
        $_POST['stat']             = 'Nouvelle stat';

        $csrf                   = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $csrf;
        $_POST['csrf_token']    = $csrf;

        $session = $this->createMock(SessionManager::class);
        $session->method('get')->willReturnCallback(function ($key) use ($csrf) {
            if ($key === 'csrf_token') {
                return $csrf;
            }

            return null;
        });

        $model = $this->createMock(Stat::class);
        $model->method('get')->with(1)->willReturn(['id_stat' => 1, 'name' => 'Ancienne stat']);
        $model->method('update')->with(1, 'Nouvelle stat')->willReturn(true);

        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturn('success');

        $logger         = $this->createMock(LoggerInterface::class);
        $errorPresenter = $this->createMock(ErrorPresenterInterface::class);

        $controller = new StatController($renderer, $logger, $errorPresenter, $session, $model);

        $ref = new ReflectionMethod($controller, 'handleEdit');
        $ref->setAccessible(true);

        ob_start();
        $ref->invoke($controller);
        $output = ob_get_clean();

        TestCase::assertStringContainsString('success', (string) $output);
    }

    /**
     * Vérifie que handleEdit() gère correctement un échec de mise à jour.
     *
     * Ce test :
     * - simule une requête POST avec un champ `stat` et un `edit_id` existant
     * - configure le modèle pour renvoyer un enregistrement existant
     * - force l’échec de la méthode `update()`
     * - vérifie que les erreurs sont passées à la vue
     *
     * @return void
     */
    public function testHandleEditFailureCoversElse(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['edit_id']          = 1;
        $_POST['stat']             = 'Nouvelle stat';

        $errors = [];
        $old    = [];

        $csrf                   = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $csrf;
        $_POST['csrf_token']    = $csrf;

        $session = $this->createMock(SessionManager::class);
        $session->method('get')->willReturnCallback(function ($key) use (&$errors, &$old, $csrf) {
            if ($key === 'errors') {
                return $errors;
            }

            if ($key === 'old') {
                return $old;
            }

            if ($key === 'csrf_token') {
                return $csrf;
            }

            return null;
        });
        $session->method('set')->willReturnCallback(function ($key, $value) use (&$errors, &$old) {
            if ($key === 'errors') {
                $errors = array_merge((array) $errors, (array) $value);
            }

            if ($key === 'old') {
                $old = $value;
            }

        });

        $model = $this->createMock(Stat::class);
        $model->method('get')->with(1)->willReturn(['id_stat' => 1, 'name' => 'Ancienne stat']);
        $model->method('update')->with(1, 'Nouvelle stat')->willReturn(false);

        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturnCallback(function ($view, $data = []) {
            if ($view === 'stats/add-stat') {
                TestCase::assertIsArray($data);
                TestCase::assertArrayHasKey('errors', $data);
                TestCase::assertIsArray($data['errors']);
                TestCase::assertArrayHasKey('global', $data['errors']);
                TestCase::assertSame('Erreur lors de la modification.', $data['errors']['global']);
                TestCase::assertArrayHasKey('old', $data);
                TestCase::assertIsArray($data['old']);
                TestCase::assertSame('Ancienne stat', $data['old']['stat']);

            }
            return 'form';
        });

        $logger         = $this->createMock(LoggerInterface::class);
        $errorPresenter = $this->createMock(ErrorPresenterInterface::class);

        $controller = $this->getMockBuilder(StatController::class)
            ->setConstructorArgs([$renderer, $logger, $errorPresenter, $session, $model])
            ->onlyMethods(['renderDefault'])
            ->getMock();
        $controller->expects($this->once())->method('renderDefault');

        $ref = new ReflectionMethod($controller, 'handleEdit');
        $ref->setAccessible(true);

        $ref->invoke($controller);
    }

    /**
     * Vérifie que la méthode `showDeleteSelectForm()` utilise correctement `getAll()`
     * et déclenche `renderDefault()` avec la vue attendue.
     *
     * @return void
     */
    public function testShowDeleteSelectForm(): void
    {
        $model = $this->createMock(Stat::class);
        $model->method('getAll')->willReturn([['id_stat' => 1, 'name' => 'stat1']]);

        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')
            ->willReturnCallback(function ($view, $data = []) {
                if ($view === 'stats/stat-select') {
                    return 'select';
                }
                return '';
            });

        $controller = $this->getMockBuilder(StatController::class)
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
     * Vérifie que le contrôleur gère le cas où aucun `delete_id` n'est fourni dans `$_POST`.
     *
     * Ce test :
     * - laisse `$_POST` vide
     * - attend que `showDeleteSelectForm()` soit appelée en fallback
     *
     * @return void
     */
    public function testHandleDeleteNoDeleteId(): void
    {
        $_POST      = [];
        $controller = $this->getController();
        $mock       = $this->getMockBuilder(StatController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['showDeleteSelectForm'])
            ->getMock();
        $mock->expects($this->once())->method('showDeleteSelectForm');

        $ref = new ReflectionMethod($mock, 'handleDelete');
        $ref->setAccessible(true);

        $ref->invoke($mock);
    }

    /**
     * Vérifie que le contrôleur gère un `delete_id` invalide (non numérique).
     *
     * Ce test :
     * - fournit une chaîne non castable en int
     * - attend que `showDeleteSelectForm()` soit déclenchée en réponse sécurisée
     *
     * @return void
     */
    public function testHandleDeleteInvalidId(): void
    {
        $_POST['delete_id'] = 'abc'; // id invalide

        $controller = $this->getController();
        $mock       = $this->getMockBuilder(StatController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['showDeleteSelectForm'])
            ->getMock();
        $mock->expects($this->once())->method('showDeleteSelectForm');

        $ref = new ReflectionMethod($mock, 'handleDelete');
        $ref->setAccessible(true);

        $ref->invoke($mock);
    }

    /**
     * Vérifie que le contrôleur affiche la sélection si l’ID à supprimer n’existe pas.
     *
     * Ce test :
     * - simule une requête POST avec un `delete_id` inexistant
     * - configure le modèle pour retourner `null`
     * - s’assure que `showDeleteSelectForm()` est appelée
     *
     * @return void
     */
    public function testHandleDeleteNotFound(): void
    {
        $_POST['delete_id'] = 42;
        $model              = $this->createMock(Stat::class);
        $model->method('get')->with(42)->willReturn(null);

        $renderer       = $this->createMock(Renderer::class);
        $logger         = $this->createMock(LoggerInterface::class);
        $errorPresenter = $this->createMock(ErrorPresenterInterface::class);
        $session        = $this->createMock(SessionManager::class);

        $controller = $this->getMockBuilder(StatController::class)
            ->setConstructorArgs([$renderer, $logger, $errorPresenter, $session, $model])
            ->onlyMethods(['showDeleteSelectForm'])
            ->getMock();

        $controller->expects($this->once())->method('showDeleteSelectForm');

        $ref = new ReflectionMethod($controller, 'handleDelete');
        $ref->setAccessible(true);

        $ref->invoke($controller);
    }

    /**
     * Vérifie le comportement en cas de suppression validée et réussie.
     *
     * Ce test :
     * - simule une requête POST avec `delete_id` + confirmation
     * - configure le modèle pour que `delete()` retourne true
     * - capture le rendu HTML et vérifie que 'success' est présent
     *
     * @return void
     */
    public function testHandleDeleteValid(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['delete_id']        = 1;
        $_POST['confirm_delete']   = 1;

        $model = $this->createMock(Stat::class);
        $model->method('delete')->with(1)->willReturn(true);

        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturn('success');

        $controller = new StatController($renderer, $this->createMock(LoggerInterface::class), $this->createMock(ErrorPresenterInterface::class), $this->createMock(SessionManager::class), $model);

        $ref = new ReflectionMethod($controller, 'handleDelete');
        $ref->setAccessible(true);

        ob_start();
        $ref->invoke($controller);
        $output = ob_get_clean();

        $this->assertStringContainsString('success', (string) $output);
    }

    /**
     * Vérifie que le contrôleur affiche le formulaire si la suppression échoue.
     *
     * Ce test :
     * - simule une requête de suppression confirmée
     * - force `delete()` à retourner false
     * - vérifie que le message de fallback est rendu (ex: formulaire)
     *
     * @return void
     */
    public function testHandleDeleteFailure(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['delete_id']        = 1;
        $_POST['confirm_delete']   = 1;

        $model = $this->createMock(Stat::class);
        $model->method('delete')->with(1)->willReturn(false);

        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturn('form');

        $controller = new StatController($renderer, $this->createMock(LoggerInterface::class), $this->createMock(ErrorPresenterInterface::class), $this->createMock(SessionManager::class), $model);

        $ref = new ReflectionMethod($controller, 'handleDelete');
        $ref->setAccessible(true);

        ob_start();
        $ref->invoke($controller);
        $output = ob_get_clean();

        $this->assertStringContainsString('form', (string) $output);
    }

    /**
     * Vérifie que l’action `showList()` récupère les données et appelle `renderDefault()`.
     *
     * Ce test :
     * - simule `getAll()` avec une liste de stats
     * - vérifie que la vue 'stats/stats-list' est rendue via le renderer
     * - s'assure que `renderDefault()` est déclenchée dans le contrôleur
     *
     * @return void
     */
    public function testShowList(): void
    {
        $model = $this->createMock(Stat::class);
        $model->method('getAll')->willReturn([['id_stat' => 1, 'name' => 'stat1']]);

        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')
            ->willReturnCallback(function ($view, $data = []) {
                if ($view === 'stats/stats-list') {
                    return 'liste';
                }
                return '';
            });

        $controller = $this->getMockBuilder(StatController::class)
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
     * Teste que le formulaire de confirmation de suppression est bien affiché pour un ID donné.
     *
     * Ce test :
     * - mocke le modèle `Stat` pour retourner un enregistrement connu
     * - vérifie que le renderer affiche la vue 'stats/delete-stat-confirm' avec les bonnes données
     * - s'assure que la méthode protégée `renderDefault()` est bien appelée à la fin du processus
     *
     * @return void
     */
    public function testShowDeleteConfirmFormDisplaysConfirmation(): void
    {
        $id     = 1;
        $record = ['id_stat' => $id, 'name' => 'stat1'];

        $model = $this->createMock(Stat::class);
        $model->method('get')->with($id)->willReturn($record);

        $renderer = $this->createMock(Renderer::class);
        $renderer->expects($this->once())
            ->method('render')
            ->with(
                'stats/delete-stat-confirm',
                $this->callback(function ($data) use ($record, $id) {
                    // Ajoute ces assertions de type :
                    TestCase::assertIsArray($data);
                    TestCase::assertArrayHasKey('stat', $data);
                    TestCase::assertArrayHasKey('id', $data);
                    TestCase::assertArrayHasKey('errors', $data);
                    return $data['stat'] === $record && $data['id'] === $id && isset($data['errors']);
                })
            )
            ->willReturn('confirm');

        $controller = $this->getMockBuilder(StatController::class)
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

    /**
     * Vérifie que la méthode processDelete() traite correctement une suppression réussie.
     *
     * Ce test :
     * - simule une requête POST avec un ID de statistique à supprimer
     * - configure le modèle pour que delete() retourne true
     * - capture le rendu HTML et vérifie qu'il contient 'success'
     *
     * @return void
     */
    public function testProcessDeleteSuccess(): void
    {
        $model = $this->createMock(Stat::class);
        $model->method('delete')->with(1)->willReturn(true);

        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturn('success');

        $logger         = $this->createMock(LoggerInterface::class);
        $errorPresenter = $this->createMock(ErrorPresenterInterface::class);
        $session        = $this->createMock(SessionManager::class);

        $controller = new StatController($renderer, $logger, $errorPresenter, $session, $model);

        $ref = new ReflectionMethod($controller, 'processDelete');
        $ref->setAccessible(true);

        ob_start();
        $ref->invoke($controller, 1);
        $output = ob_get_clean();

        $this->assertStringContainsString('success', (string) $output);
    }

    /**
     * Vérifie que la méthode processDelete() gère correctement une suppression échouée.
     *
     * Ce test :
     * - simule une requête POST avec un ID de statistique à supprimer
     * - configure le modèle pour que delete() retourne false
     * - s'assure que le formulaire de sélection de suppression est affiché
     *
     * @return void
     */
    public function testProcessDeleteFailure(): void
    {
        $model = $this->createMock(Stat::class);
        $model->method('delete')->with(1)->willReturn(false);

        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturn('form');

        $logger         = $this->createMock(LoggerInterface::class);
        $errorPresenter = $this->createMock(ErrorPresenterInterface::class);
        $session        = $this->createMock(SessionManager::class);

        $controller = $this->getMockBuilder(StatController::class)
            ->setConstructorArgs([$renderer, $logger, $errorPresenter, $session, $model])
            ->onlyMethods(['showDeleteSelectForm'])
            ->getMock();

        $controller->expects($this->once())->method('showDeleteSelectForm');

        $ref = new ReflectionMethod($controller, 'processDelete');
        $ref->setAccessible(true);

        $ref->invoke($controller, 1);
    }

    /**
     * Vérifie que handleDelete() traite correctement une suppression réussie.
     *
     * Ce test :
     * - simule une requête POST avec un ID de statistique à supprimer
     * - configure le modèle pour que delete() retourne true
     * - capture le rendu HTML et vérifie qu'il contient 'success'
     *
     * @return void
     */
    public function testHandleDeleteProcessDeleteSuccess(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['delete_id']        = 1;
        $_POST['confirm_delete']   = 1;

        // CSRF valide
        $csrf                   = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $csrf;
        $_POST['csrf_token']    = $csrf;

        $model = $this->createMock(Stat::class);
        $model->method('delete')->with(1)->willReturn(true);

        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturn('success');

        $logger         = $this->createMock(LoggerInterface::class);
        $errorPresenter = $this->createMock(ErrorPresenterInterface::class);
        $session        = $this->createMock(SessionManager::class);
        $session->method('get')->willReturnCallback(function ($key) use ($csrf) {
            if ($key === 'csrf_token') {
                return $csrf;
            }

            return null;
        });

        $controller = new StatController($renderer, $logger, $errorPresenter, $session, $model);

        $ref = new ReflectionMethod($controller, 'handleDelete');
        $ref->setAccessible(true);

        ob_start();
        $ref->invoke($controller);
        $output = ob_get_clean();

        $this->assertStringContainsString('success', (string) $output);
    }

    /**
     * Vérifie que la méthode protégée getAddRoute() retourne bien la route 'add-stat'.
     *
     * @return void
     */
    public function testGetAddRoute(): void
    {
        $controller = $this->getController();
        $ref        = new ReflectionMethod($controller, 'getAddRoute');
        $ref->setAccessible(true);
        $this->assertSame('add-stat', $ref->invoke($controller));
    }

    /**
     * Vérifie que la méthode protégée getEditRoute() retourne bien la route 'edit-stat'.
     *
     * @return void
     */
    public function testGetEditRoute(): void
    {
        $controller = $this->getController();
        $ref        = new ReflectionMethod($controller, 'getEditRoute');
        $ref->setAccessible(true);
        $this->assertSame('edit-stat', $ref->invoke($controller));
    }

    /**
     * Vérifie que la méthode protégée getDeleteRoute() retourne bien la route 'delete-stat'.
     *
     * @return void
     */
    public function testGetDeleteRoute(): void
    {
        $controller = $this->getController();
        $ref        = new ReflectionMethod($controller, 'getDeleteRoute');
        $ref->setAccessible(true);
        $this->assertSame('delete-stat', $ref->invoke($controller));
    }
}
