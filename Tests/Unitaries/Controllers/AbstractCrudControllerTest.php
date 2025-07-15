<?php
declare (strict_types = 1);

use GenshinTeam\Controllers\AbstractCrudController;
use GenshinTeam\Models\CrudModelInterface;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorPresenterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Contrôleur fictif pour tester les fonctionnalités CRUD de AbstractCrudController.
 *
 * Cette classe permet de simuler le comportement d’un CRUD sans logique métier réelle.
 * Elle garde une trace des appels effectués et permet de tester les méthodes protégées
 * du contrôleur parent via des wrappers publics.
 */
class DummyCrudController extends AbstractCrudController
{
    /**
     * Liste des appels effectués sur les méthodes simulées (add, edit, delete, list).
     *
     * @var array<int, string>
     */
    public array $calls = [];

    /**
     * Retourne le nom de la route pour l’ajout.
     *
     * @return string
     */
    protected function getAddRoute(): string
    {return 'add-entity';}

    /**
     * Retourne le nom de la route pour l’édition.
     *
     * @return string
     */
    protected function getEditRoute(): string
    {return 'edit-entity';}

    /**
     * Retourne le nom de la route pour la suppression.
     *
     * @return string
     */
    protected function getDeleteRoute(): string
    {return 'delete-entity';}

    /**
     * Gère le traitement d’ajout d’une entité.
     *
     * @return void
     */
    protected function handleAdd(): void
    {$this->calls[] = 'add';}

    /**
     * Gère le traitement d’édition d’une entité.
     *
     * @return void
     */
    protected function handleEdit(): void
    {$this->calls[] = 'edit';}

    /**
     * Gère le traitement de suppression d’une entité.
     *
     * @return void
     */
    protected function handleDelete(): void
    {$this->calls[] = 'delete';}

    /**
     * Gère l'affichage de la liste des entités.
     *
     * @return void
     */
    protected function showList(): void
    {$this->calls[] = 'list';}

    /**
     * Appelle la méthode handleCrudAdd pour gérer l’ajout d’un élément CRUD.
     *
     * @param string   $fieldName Nom du champ principal à vérifier.
     * @param callable $processAdd Fonction exécutant le traitement de l’ajout.
     * @param callable $showForm Fonction affichant le formulaire d’ajout.
     * @return void
     */
    public function callHandleCrudAdd(string $fieldName, callable $processAdd, callable $showForm): void
    {
        $this->handleCrudAdd($fieldName, $processAdd, $showForm);
    }

    /**
     * Appelle la méthode handleCrudEdit pour gérer la modification d’un élément CRUD.
     *
     * @param string   $fieldName Nom du champ principal à vérifier.
     * @param callable $processEdit Fonction exécutant la logique de modification.
     * @param callable $showEditForm Fonction affichant le formulaire d’édition.
     * @param callable $showEditSelectForm Fonction affichant la sélection d’un élément à éditer.
     * @return void
     */
    public function callHandleCrudEdit(
        string $fieldName,
        callable $processEdit,
        callable $showEditForm,
        callable $showEditSelectForm,
    ): void {
        $this->handleCrudEdit($fieldName, $processEdit, $showEditForm, $showEditSelectForm);
    }

    /**
     * Appelle la méthode handleCrudDelete pour gérer la suppression d’un élément CRUD.
     *
     * @param callable $processDelete Fonction exécutant la logique de suppression.
     * @param callable $showDeleteSelectForm Fonction affichant la sélection d’un élément à supprimer.
     * @param callable $showDeleteConfirmForm Fonction affichant la confirmation de suppression.
     * @return void
     */
    public function callHandleCrudDelete(
        callable $processDelete,
        callable $showDeleteSelectForm,
        callable $showDeleteConfirmForm
    ): void {
        $this->handleCrudDelete($processDelete, $showDeleteSelectForm, $showDeleteConfirmForm);
    }
}

class DummyCrudControllerBis extends DummyCrudController
{
    public ?string $forbiddenHtml = null;

    protected function renderDefault(): void
    {
        // Simule un rendu minimal comme dans default.php
        // echo '<html>' . ($this->data['title'] ?? '') . ($this->data['content'] ?? '') . '</html>';
        echo '<html>';
        echo is_string($this->data['title'] ?? null) ? $this->data['title'] : '';
        echo is_string($this->data['content'] ?? null) ? $this->data['content'] : '';
        echo '</html>';

    }

    protected function renderForbidden(string $message = "Vous n'avez pas accès à cette page."): void
    {
        http_response_code(403);
        $this->addData('title', 'Accès interdit');
        $this->addData('content', '<div role="alert">' . htmlspecialchars($message) . '</div>');

        ob_start();
        parent::renderDefault();
        $this->forbiddenHtml = ob_get_clean() ?: null;
    }

    public function triggerAdminCheck(): bool
    {
        return $this->checkAdminAccess();
    }

    public function getRenderedForbidden(): ?string
    {
        return $this->forbiddenHtml;
    }
}

/**
 * Classe de test pour DummyCrudController.
 *
 * Cette classe vérifie l'initialisation correcte du contrôleur CRUD et
 * assure que les ressources temporaires sont bien gérées entre les tests.
 *
 * @covers DummyCrudController
 */
class AbstractCrudControllerTest extends TestCase
{
    /**
     * Instance du contrôleur de test.
     *
     * @var DummyCrudController
     */
    private DummyCrudController $controller;

    /**
     * Chemin temporaire vers le dossier de vues.
     *
     * @var string
     */
    private string $viewPath;

    /**
     * Prépare l'environnement de test.
     *
     * - Crée le répertoire de vues temporaires.
     * - Initialise les dépendances simulées (mock).
     * - Instancie le contrôleur à tester.
     */
    protected function setUp(): void
    {
        $this->viewPath = sys_get_temp_dir() . '/views_' . uniqid();
        @mkdir($this->viewPath . '/templates', 0777, true);
        file_put_contents($this->viewPath . '/templates/default.php', '<html><?= $title ?? "" ?><?= $content ?? "" ?></html>');

        $renderer       = new Renderer($this->viewPath);
        $logger         = $this->createMock(LoggerInterface::class);
        $errorPresenter = $this->createMock(ErrorPresenterInterface::class);
        $session        = new SessionManager();
        $session->set('user', ['id_role' => 1]);
        $model = $this->createMock(CrudModelInterface::class);

        $this->controller = new DummyCrudController(
            $renderer,
            $logger,
            $errorPresenter,
            $session,
            $model
        );

    }

    /**
     * Nettoie l'environnement de test.
     *
     * Supprime les fichiers et répertoires temporaires créés lors du setUp.
     */
    protected function tearDown(): void
    {
        @unlink($this->viewPath . '/templates/default.php');
        @rmdir($this->viewPath . '/templates');
        @rmdir($this->viewPath);
    }

    /**
     * Vérifie que la route 'add-entity' déclenche bien la méthode handleAdd().
     */
    public function testDispatchAddRouteCallsHandleAdd(): void
    {
        $this->controller->setCurrentRoute('add-entity');
        $this->controller->run();
        $this->assertContains('add', $this->controller->calls);
    }

    /**
     * Vérifie que la route 'edit-entity' déclenche bien la méthode handleEdit().
     */
    public function testDispatchEditRouteCallsHandleEdit(): void
    {
        $this->controller->setCurrentRoute('edit-entity');
        $this->controller->run();
        $this->assertContains('edit', $this->controller->calls);
    }

    /**
     * Vérifie que la route 'delete-entity' déclenche bien la méthode handleDelete().
     */
    public function testDispatchDeleteRouteCallsHandleDelete(): void
    {
        $this->controller->setCurrentRoute('delete-entity');
        $this->controller->run();
        $this->assertContains('delete', $this->controller->calls);
    }

    /**
     * Vérifie qu'une route inconnue déclenche la méthode showList() par défaut.
     */
    public function testDispatchUnknownRouteCallsShowList(): void
    {
        $this->controller->setCurrentRoute('unknown-route');
        $this->controller->run();
        $this->assertContains('list', $this->controller->calls);
    }

    /**
     * Teste le succès du processus d’ajout via la méthode callHandleCrudAdd.
     *
     * Ce test simule une requête POST valide avec un token CSRF correct
     * et une valeur non vide pour le champ 'foo'. Il vérifie que :
     * - La méthode processAdd est bien appelée avec la bonne valeur.
     * - Le formulaire ne doit pas être affiché (pas d'appel à showForm).
     *
     * @return void
     */
    public function testHandleCrudAddSuccess(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token']       = 'token';
        $_POST['foo']              = 'bar';
        $_SESSION['csrf_token']    = 'token';

        $controller = $this->controller;

        $called = false;
        $controller->callHandleCrudAdd(
            'foo',
            function ($v) use (&$called) {
                TestCase::assertSame('bar', $v);
                $called = true;
            },
            function () {
                TestCase::fail('showForm ne doit pas être appelé en cas de succès');
            }
        );
        $this->assertTrue($called, 'processAdd doit être appelé');
    }

    /**
     * Teste le comportement de callHandleCrudAdd avec un token CSRF invalide.
     *
     * Ce test simule une requête POST dans laquelle le token CSRF soumis est incorrect.
     * Il vérifie que :
     * - La méthode processAdd ne doit PAS être appelée.
     * - La méthode showForm DOIT être appelée.
     * - Une erreur globale doit être enregistrée pour signaler l’échec de la protection CSRF.
     *
     * @return void
     */
    public function testHandleCrudAddInvalidCsrf(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token']       = 'bad';
        $_POST['foo']              = 'bar';
        $_SESSION['csrf_token']    = 'token';

        $controller = $this->controller;

        $called = false;
        $controller->callHandleCrudAdd(
            'foo',
            function () {
                TestCase::fail('processAdd ne doit pas être appelé si CSRF invalide');
            },
            function () use (&$called) {
                $called = true;
            }
        );
        $this->assertTrue($called, 'showForm doit être appelé');
        $this->assertArrayHasKey('global', $controller->getErrors());
    }

    /**
     * Teste le succès du traitement de modification via la méthode callHandleCrudEdit.
     *
     * Ce test simule une requête POST avec un identifiant valide, un token CSRF correct,
     * et une valeur non vide pour le champ 'foo'. Il vérifie que :
     * - La méthode processEdit est bien appelée avec les bonnes valeurs.
     * - Ni le formulaire d'édition ni celui de sélection ne doivent être appelés.
     *
     * @return void
     */
    public function testHandleCrudEditSuccess(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token']       = 'token';
        $_POST['edit_id']          = 1;
        $_POST['foo']              = 'bar';
        $_SESSION['csrf_token']    = 'token';

        $controller = $this->controller;

        $controller->callHandleCrudEdit(
            'foo',
            function ($id, $v) {
                TestCase::assertSame(1, $id);
                TestCase::assertSame('bar', $v);
            },
            function () {
                TestCase::fail('showEditForm ne doit pas être appelé en cas de succès');
            },
            function () {
                TestCase::fail('showEditSelectForm ne doit pas être appelé');
            }
        );
    }

    /**
     * Teste le comportement de callHandleCrudEdit avec un token CSRF invalide.
     *
     * Ce test simule une requête POST dans laquelle le token CSRF envoyé ne correspond pas à celui en session.
     * Il vérifie que :
     * - La méthode processEdit ne doit PAS être appelée.
     * - La méthode showEditForm DOIT être appelée avec le bon identifiant.
     * - La méthode showEditSelectForm ne doit PAS être appelée.
     * - Une erreur globale doit être ajoutée (protection CSRF échouée).
     *
     * @return void
     */
    public function testHandleCrudEditInvalidCsrf(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token']       = 'bad';
        $_POST['edit_id']          = 1;
        $_POST['foo']              = 'bar';
        $_SESSION['csrf_token']    = 'token';

        $controller = $this->controller;

        $controller->callHandleCrudEdit(
            'foo',
            function () {
                TestCase::fail('processEdit ne doit pas être appelé si CSRF invalide');
            },
            function ($id) {
                TestCase::assertSame(1, $id);
            },
            function () {
                TestCase::fail('showEditSelectForm ne doit pas être appelé');
            }
        );
        $this->assertArrayHasKey('global', $controller->getErrors());
    }

    /**
     * Teste le comportement de callHandleCrudEdit lorsque l’identifiant d’édition est absent.
     *
     * Ce test simule une requête POST où le champ 'edit_id' est manquant.
     * Il vérifie que :
     * - La méthode processEdit ne doit PAS être appelée.
     * - Le formulaire d’édition (showEditForm) ne doit PAS être affiché.
     * - Le formulaire de sélection d’un élément à éditer (showEditSelectForm) DOIT être appelé.
     *
     * @return void
     */
    public function testHandleCrudEditNoEditId(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        unset($_POST['edit_id']);

        $controller = $this->controller;

        $called = false;
        $controller->callHandleCrudEdit(
            'foo',
            function () {
                TestCase::fail('processEdit ne doit pas être appelé');
            },
            function () {
                TestCase::fail('showEditForm ne doit pas être appelé');
            },
            function () use (&$called) {
                $called = true;
            }
        );
        $this->assertTrue($called, 'showEditSelectForm doit être appelé');
    }

    /**
     * Teste le succès de la suppression via la méthode callHandleCrudDelete.
     *
     * Ce test simule une requête POST valide avec :
     * - Un identifiant de suppression correct
     * - Une confirmation explicite de suppression
     * - Un token CSRF valide
     *
     * Il vérifie que :
     * - La méthode processDelete est bien appelée avec le bon identifiant
     * - Les formulaires de sélection ou de confirmation ne sont pas invoqués
     *
     * @return void
     */
    public function testHandleCrudDeleteSuccess(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token']       = 'token';
        $_POST['delete_id']        = 1;
        $_POST['confirm_delete']   = 1;
        $_SESSION['csrf_token']    = 'token';

        $controller = $this->controller;

        $controller->callHandleCrudDelete(
            function ($id) {TestCase::assertSame(1, $id);},
            function () {TestCase::fail('showDeleteSelectForm ne doit pas être appelé');},
            function () {TestCase::fail('showDeleteConfirmForm ne doit pas être appelé');}
        );
    }

    /**
     * Teste le comportement de callHandleCrudDelete avec un token CSRF invalide.
     *
     * Ce test simule une requête POST dans laquelle le token CSRF fourni ne correspond pas
     * à celui enregistré en session. Il vérifie que :
     * - La méthode processDelete ne doit PAS être appelée.
     * - Le formulaire de confirmation ne doit PAS être affiché.
     * - Le formulaire de sélection DOIT être affiché.
     * - Une erreur globale doit être enregistrée pour indiquer l’échec de la protection CSRF.
     *
     * @return void
     */
    public function testHandleCrudDeleteInvalidCsrf(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token']       = 'bad';
        $_POST['delete_id']        = 1;
        $_POST['confirm_delete']   = 1;
        $_SESSION['csrf_token']    = 'token';

        $controller = $this->controller;

        $called = false;
        $controller->callHandleCrudDelete(
            function () {TestCase::fail('processDelete ne doit pas être appelé');},
            function () use (&$called) {$called = true;},
            function () {TestCase::fail('showDeleteConfirmForm ne doit pas être appelé');}
        );
        $this->assertTrue($called, 'showDeleteSelectForm doit être appelé');
        $this->assertArrayHasKey('global', $controller->getErrors());
    }

    /**
     * Teste le comportement de callHandleCrudDelete lorsque l'identifiant de suppression est absent.
     *
     * Ce test simule une requête POST sans champ 'delete_id'.
     * Il vérifie que :
     * - La méthode processDelete ne doit PAS être appelée.
     * - Le formulaire de confirmation de suppression ne doit PAS être affiché.
     * - Le formulaire de sélection pour la suppression DOIT être appelé.
     *
     * @return void
     */
    public function testHandleCrudDeleteNoDeleteId(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        unset($_POST['delete_id']);

        $controller = $this->controller;

        $called = false;
        $controller->callHandleCrudDelete(
            function () {TestCase::fail('processDelete ne doit pas être appelé');},
            function () use (&$called) {$called = true;},
            function () {TestCase::fail('showDeleteConfirmForm ne doit pas être appelé');}
        );
        $this->assertTrue($called, 'showDeleteSelectForm doit être appelé');
    }

    /**
     * Teste le comportement de callHandleCrudEdit avec un identifiant d’édition invalide.
     *
     * Ce test simule une requête POST dans laquelle le champ 'edit_id' est une chaîne non entière.
     * Il vérifie que :
     * - La méthode processEdit ne doit PAS être appelée.
     * - Le formulaire d’édition (showEditForm) ne doit PAS être affiché.
     * - Le formulaire de sélection (showEditSelectForm) DOIT être appelé.
     * - Une erreur globale doit être enregistrée.
     *
     * @return void
     */
    public function testHandleCrudEditInvalidId(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['edit_id']          = 'not-an-int';

        $controller = $this->controller;

        $called = false;
        $controller->callHandleCrudEdit(
            'foo',
            function () {
                TestCase::fail('processEdit ne doit pas être appelé');
            },
            function () {
                TestCase::fail('showEditForm ne doit pas être appelé');
            },
            function () use (&$called) {
                $called = true;
            },
        );
        $this->assertTrue($called, 'showEditSelectForm doit être appelé');
        $this->assertArrayHasKey('global', $controller->getErrors());
    }

    /**
     * Teste le comportement de callHandleCrudAdd lorsqu’une requête GET est utilisée.
     *
     * Ce test simule une requête HTTP GET (au lieu d’un POST classique).
     * Il vérifie que :
     * - Le traitement processAdd ne doit PAS être appelé.
     * - Le formulaire doit être affiché via showForm.
     *
     * @return void
     */
    public function testHandleCrudAddShowsFormOnGet(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $controller = $this->controller;

        $called = false;
        $controller->callHandleCrudAdd(
            'foo',
            function () {
                TestCase::fail('processAdd ne doit pas être appelé en GET');
            },
            function () use (&$called) {
                $called = true;
            }
        );
        $this->assertTrue($called, 'showForm doit être appelé en GET');
    }

    /**
     * Teste le comportement de callHandleCrudEdit lors d'une requête GET.
     *
     * Ce test simule une requête HTTP GET avec un identifiant d'édition.
     * Il vérifie que :
     * - La méthode processEdit ne doit PAS être appelée.
     * - Le formulaire d'édition (showEditForm) DOIT être affiché avec l'identifiant approprié.
     * - Le formulaire de sélection (showEditSelectForm) ne doit PAS être appelé.
     *
     * @return void
     */
    public function testHandleCrudEditShowsFormOnGet(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_POST['edit_id']          = 1;

        $controller = $this->controller;

        $called = false;
        $controller->callHandleCrudEdit(
            'foo',
            function () {
                TestCase::fail('processEdit ne doit pas être appelé en GET');
            },
            function ($id) use (&$called) {
                TestCase::assertSame(1, $id);
                $called = true;
            },
            function () {
                TestCase::fail('showEditSelectForm ne doit pas être appelé');
            },
        );
        $this->assertTrue($called, 'showEditForm doit être appelé en GET');
    }

    /**
     * Teste le comportement de callHandleCrudDelete avec un identifiant invalide.
     *
     * Ce test simule une requête POST dans laquelle le champ 'delete_id' contient une valeur non entière.
     * Il vérifie que :
     * - La méthode processDelete ne doit PAS être appelée.
     * - Le formulaire de confirmation (showDeleteConfirmForm) ne doit PAS être affiché.
     * - Le formulaire de sélection (showDeleteSelectForm) DOIT être appelé.
     * - Une erreur globale doit être enregistrée.
     *
     * @return void
     */
    public function testHandleCrudDeleteInvalidId(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['delete_id']        = 'not-an-int';

        $controller = $this->controller;

        $called = false;
        $controller->callHandleCrudDelete(
            function () {TestCase::fail('processDelete ne doit pas être appelé');},
            function () use (&$called) {$called = true;},
            function () {TestCase::fail('showDeleteConfirmForm ne doit pas être appelé');}
        );
        $this->assertTrue($called, 'showDeleteSelectForm doit être appelé');
        $this->assertArrayHasKey('global', $controller->getErrors());
    }

    /**
     * Vérifie que handleCrudAdd intercepte correctement une exception levée
     * par le callback $processAdd, sans laisser l’exception remonter.
     *
     * Le scénario simule une requête POST avec un champ et un token CSRF valides.
     * Le callable $processAdd lève une RuntimeException, et le test valide que
     * l'exécution continue sans échec (via l'appel à handleException).
     *
     * Note : on utilise un contrôleur réel, sans espionner handleException().
     * Ce test valide donc la capture de l’exception, mais pas son traitement.
     *
     * @return void
     *
     * @covers \GenshinTeam\Controllers\AbstractCrudController::handleCrudAdd
     */
    public function testHandleCrudAddCatchesException(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['csrf_token']    = 'secure';
        $_POST                     = [
            'csrf_token' => 'secure',
            'username'   => 'Alice',
        ];

        $this->controller->callHandleCrudAdd('username', function () {
            throw new RuntimeException('Boom test');
        }, function () {
            throw new LogicException('Formulaire ne doit pas être affiché');
        });

        $this->expectNotToPerformAssertions();

    }

    /**
     * Vérifie que handleCrudEdit capture correctement une exception levée par le callback $processEdit,
     * sans la propager hors de la méthode (grâce au bloc try/catch).
     *
     * Le test simule une requête POST dans laquelle :
     * - Un champ éditable (ici 'username') et un ID de modification (edit_id) sont fournis dans $_POST
     * - Le jeton CSRF est valide
     * - Le callback $processEdit déclenche volontairement une RuntimeException
     *
     * Le contrôleur doit intercepter cette exception via handleException() — on ne l'espionne pas ici,
     * mais on vérifie simplement que l'exécution se poursuit sans plantage du test.
     *
     * @return void
     *
     * @covers \GenshinTeam\Controllers\AbstractCrudController::handleCrudEdit
     */
    public function testHandleCrudEditCatchesException(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['csrf_token']    = 'secure';
        $_POST                     = [
            'csrf_token' => 'secure',
            'username'   => 'Alice',
            'edit_id'    => 42,
        ];

        $this->controller->callHandleCrudEdit(
            'username',
            function () {
                throw new RuntimeException('Boom test');
            },
            function () {
                throw new LogicException('Formulaire d’édition ne doit pas être affiché');
            },
            function () {
                throw new LogicException('Formulaire de sélection ne doit pas être affiché');
            },
        );

        $this->expectNotToPerformAssertions();

    }

    /**
     * Vérifie que handleCrudDelete capture correctement une exception levée par le callback $processDelete.
     *
     * Ce test simule une requête POST dans laquelle :
     * - L’ID de suppression (delete_id) est présent dans $_POST
     * - La confirmation explicite (confirm_delete) est également fournie
     * - Le CSRF est valide
     *
     * Dans ce scénario, le contrôleur appelle $processDelete() — lequel lance volontairement une exception.
     * L’objectif est de valider que cette exception est interceptée par le bloc try/catch
     * et traitée par handleException(), sans propagation ni plantage du test.
     *
     * On n'espionne pas ici handleException(), on vérifie uniquement la stabilité de l'exécution.
     *
     * @return void
     *
     * @covers \GenshinTeam\Controllers\AbstractCrudController::handleCrudDelete
     */
    public function testHandleCrudDeleteCatchesException(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['csrf_token']    = 'secure';
        $_POST                     = [
            'csrf_token'     => 'secure',
            'delete_id'      => 42,
            'confirm_delete' => 'yes',
        ];

        $this->controller->callHandleCrudDelete(
            function () {
                throw new RuntimeException('Boom test'); // 👈 Exception déclenchée pour tester le catch
            },
            function () {
                throw new LogicException('Formulaire de sélection ne doit pas être affiché');
            },
            function ($id) {
                throw new LogicException('Formulaire de confirmation ne doit pas être affiché');
            }
        );

        $this->expectNotToPerformAssertions();

    }

    /**
     * Vérifie que handleCrudDelete appelle le formulaire de confirmation lorsque la suppression
     * n’est pas encore validée (absence de champ confirm_delete dans la requête POST).
     *
     * Le test simule un scénario dans lequel :
     * - L'utilisateur a soumis un ID de suppression (delete_id)
     * - L'ID est valide
     * - Mais la confirmation explicite de suppression est absente
     *
     * On s’attend alors à ce que le contrôleur appelle $showDeleteConfirmForm($id),
     * et surtout à ce que $processDelete() ne soit pas encore déclenché.
     *
     * @return void
     *
     * @covers \GenshinTeam\Controllers\AbstractCrudController::handleCrudDelete
     */
    public function testHandleCrudDeleteCallsConfirmFormWhenNoConfirmation(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['csrf_token']    = 'secure';
        $_POST                     = [
            'csrf_token' => 'secure',
            'delete_id'  => 42,
            // Pas de confirm_delete ici !
        ];
        $called = false;

        $this->controller->callHandleCrudDelete(
            fn() => throw new \LogicException('processDelete ne doit pas être appelé'),
            fn() => throw new \LogicException('Formulaire de sélection ne doit pas être affiché'),
            function ($id) use (&$called) {
                $called = $id === 42;
            }
        );

        $this->assertTrue($called, 'Le formulaire de confirmation a bien été affiché');
    }

    /**
     * Vérifie que checkAdminAccess() refuse l’accès avec un rôle invalide et que handleRequest() est interrompu.
     *
     * Ce test initialise un contrôleur avec une session contenant un utilisateur non administrateur (id_role = 2).
     * Il appelle checkAdminAccess() pour valider le retour false, puis handleRequest() pour s'assurer que la logique métier
     * n'est pas exécutée suite à ce refus. Le test capture également le HTML généré par renderForbidden().
     *
     * Contrôles effectués :
     * - Retour false de checkAdminAccess()
     * - Code HTTP 403 correctement défini
     * - Affichage du message d'accès interdit
     * - Aucune méthode CRUD appelée dans le contrôleur (calls vide)
     *
     * @return void
     */
    public function testCheckAdminAccessTriggersForbiddenRender(): void
    {
        $renderer       = new Renderer($this->viewPath);
        $logger         = $this->createMock(LoggerInterface::class);
        $errorPresenter = $this->createMock(ErrorPresenterInterface::class);
        $session        = new SessionManager();
        $session->set('user', ['id_role' => 2]);
        $model = $this->createMock(CrudModelInterface::class);

        $controller = new DummyCrudControllerBis(
            $renderer,
            $logger,
            $errorPresenter,
            $session,
            $model
        );

        $result = $controller->triggerAdminCheck();
        $html   = $controller->getRenderedForbidden();

        $this->assertFalse($result);
        $this->assertSame(403, http_response_code());

        $this->assertNotNull($html);
        $this->assertStringContainsString('Accès interdit', $html);
        $this->assertStringContainsString('<div role="alert">Vous n&#039;avez pas accès à cette page.</div>', $html);

        $controller->setCurrentRoute('add-entity'); // Simule une route valide

        ob_start(); // Capture pour éviter l’affichage parasite
        $controller->handleRequest();
        ob_end_clean();

        // Aucune méthode CRUD ne doit avoir été appelée
        $this->assertEmpty($controller->calls);

    }

}
