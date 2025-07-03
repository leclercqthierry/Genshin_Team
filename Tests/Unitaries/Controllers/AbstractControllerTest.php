<?php
declare (strict_types = 1);

use GenshinTeam\Controllers\AbstractController;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use PHPUnit\Framework\TestCase;

/**
 * Contrôleur factice destiné à tester les fonctionnalités héritées d'AbstractController.
 */
class DummyController extends AbstractController
{
    public function run(): void
    {}

    protected function handleRequest(): void
    {}

    /**
     * Permet d'invoquer renderDefault() et capturer le rendu.
     *
     * @return string
     */
    public function callRenderDefault(): string
    {
        ob_start();
        $this->renderDefault();
        return ob_get_clean() ?: '';
    }

    /**
     * Invoque la redirection.
     *
     * @param string $url
     * @return void
     */
    public function callRedirect(string $url): void
    {
        parent::redirect($url);
    }

    /**
     * Ajoute une erreur de validation (accès à la méthode protected).
     *
     * @param string $key
     * @param string $msg
     * @return void
     */
    public function callAddError(string $key, string $msg): void
    {
        $this->addError($key, $msg);
    }

    /**
     * Récupère les anciennes valeurs soumises ou les valeurs par défaut.
     *
     * @param array<string, mixed> $defaults
     * @return array<string, mixed>
     */

    public function callGetOld(array $defaults = []): array
    {
        return $this->getOld($defaults);
    }

    /**
     * Vérifie la validité du jeton CSRF.
     *
     * @return bool
     */
    public function callIsCsrfTokenValid(): bool
    {
        return $this->isCsrfTokenValid();
    }

    /**
     * Appelle la méthode handleCrudAdd pour gérer l’ajout d’un élément CRUD.
     *
     * @param string   $fieldName Nom du champ principal à vérifier.
     * @param callable $isEmpty Fonction de validation indiquant si le champ est vide.
     * @param callable $processAdd Fonction exécutant le traitement de l’ajout.
     * @param callable $showForm Fonction affichant le formulaire d’ajout.
     * @return void
     */
    public function callHandleCrudAdd(string $fieldName, callable $isEmpty, callable $processAdd, callable $showForm): void
    {
        $this->handleCrudAdd($fieldName, $isEmpty, $processAdd, $showForm);
    }

    /**
     * Appelle la méthode handleCrudEdit pour gérer la modification d’un élément CRUD.
     *
     * @param string   $fieldName Nom du champ principal à vérifier.
     * @param callable $isEmpty Fonction de validation pour vérifier les champs.
     * @param callable $processEdit Fonction exécutant la logique de modification.
     * @param callable $showEditForm Fonction affichant le formulaire d’édition.
     * @param callable $showEditSelectForm Fonction affichant la sélection d’un élément à éditer.
     * @param callable $getEditId Fonction retournant l’identifiant de l’élément à éditer.
     * @return void
     */
    public function callHandleCrudEdit(
        string $fieldName,
        callable $isEmpty,
        callable $processEdit,
        callable $showEditForm,
        callable $showEditSelectForm,
        callable $getEditId
    ): void {
        $this->handleCrudEdit($fieldName, $isEmpty, $processEdit, $showEditForm, $showEditSelectForm, $getEditId);
    }

    /**
     * Appelle la méthode handleCrudDelete pour gérer la suppression d’un élément CRUD.
     *
     * @param callable $getDeleteId Fonction retournant l’identifiant à supprimer.
     * @param callable $processDelete Fonction exécutant la logique de suppression.
     * @param callable $showDeleteSelectForm Fonction affichant la sélection d’un élément à supprimer.
     * @param callable $showDeleteConfirmForm Fonction affichant la confirmation de suppression.
     * @return void
     */
    public function callHandleCrudDelete(
        callable $getDeleteId,
        callable $processDelete,
        callable $showDeleteSelectForm,
        callable $showDeleteConfirmForm
    ): void {
        $this->handleCrudDelete($getDeleteId, $processDelete, $showDeleteSelectForm, $showDeleteConfirmForm);
    }
}

/**
 * Tests unitaires de la classe AbstractController via DummyController.
 *
 * @covers \GenshinTeam\Controllers\AbstractController
 */
class AbstractControllerTest extends TestCase
{
    /** @var string */
    private string $viewPath;

    /**
     * Prépare un environnement de rendu temporaire.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->viewPath = sys_get_temp_dir() . '/views_' . uniqid();
        @mkdir($this->viewPath . '/templates', 0777, true);
        file_put_contents($this->viewPath . '/templates/default.php', '<html><?= $title ?? "" ?><?= $content ?? "" ?></html>');
    }

    /**
     * Nettoie les fichiers temporaires créés.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        @unlink($this->viewPath . '/templates/default.php');
        @rmdir($this->viewPath . '/templates');
        @rmdir($this->viewPath);
    }

    /**
     * Vérifie l’ajout et la récupération de données.
     *
     * @return void
     */
    public function testAddAndGetData(): void
    {
        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());
        $controller->addData('foo', 'bar');

        $this->assertSame('bar', $controller->getData('foo'));
        $this->assertNull($controller->getData('unknown'));
    }

    /**
     * Vérifie l’ajout et la récupération d’erreurs de validation.
     *
     * @return void
     */
    public function testAddAndGetErrors(): void
    {
        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());

        $controller->callAddError('global', 'Erreur globale');
        $controller->callAddError('email', 'Erreur email');

        $this->assertSame(['global' => 'Erreur globale', 'email' => 'Erreur email'], $controller->getErrors());
    }

    /**
     * Vérifie que renderDefault() intègre les variables title et content.
     *
     * @return void
     */
    public function testRenderDefault(): void
    {
        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());
        $controller->addData('title', 'Titre');
        $controller->addData('content', 'Contenu');

        $output = $controller->callRenderDefault();
        $this->assertStringContainsString('Titre', $output);
        $this->assertStringContainsString('Contenu', $output);
    }

    /**
     * Vérifie que getOld() retourne les valeurs par défaut si aucun old n’est défini.
     *
     * @return void
     */
    public function testGetOldReturnsDefaults(): void
    {
        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());
        $defaults   = ['nickname' => 'Jean', 'email' => 'a@b.c'];

        $this->assertSame($defaults, $controller->callGetOld($defaults));
    }

    /**
     * Vérifie que getOld() retourne les anciennes valeurs si elles sont présentes.
     *
     * @return void
     */
    public function testGetOldReturnsOldData(): void
    {
        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());
        $controller->addData('old', ['nickname' => 'Paul']);

        $this->assertSame(['nickname' => 'Paul'], $controller->callGetOld(['nickname' => 'Jean']));
    }

    /**
     * Vérifie la validité du jeton CSRF avec un cas valide puis invalide.
     *
     * @return void
     */
    public function testIsCsrfTokenValid(): void
    {
        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());

        $_SESSION['csrf_token'] = 'abc';
        $_POST['csrf_token']    = 'abc';
        $this->assertTrue($controller->callIsCsrfTokenValid());

        $_POST['csrf_token'] = 'wrong';
        $this->assertFalse($controller->callIsCsrfTokenValid());
    }

    /**
     * Vérifie que setOld() stocke correctement les anciennes valeurs du formulaire.
     *
     * @return void
     */
    public function testSetOldStoresOldData(): void
    {
        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());
        $old        = ['day' => 'Lundi', 'note' => 'Test'];
        // On utilise setOld pour stocker les anciennes valeurs
        $reflection = new \ReflectionClass($controller);
        $method     = $reflection->getMethod('setOld');
        $method->setAccessible(true);
        $method->invoke($controller, $old);

        // On vérifie que getOld retourne bien ces valeurs
        $this->assertSame($old, $controller->callGetOld());
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

        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());

        $called = false;
        $controller->callHandleCrudAdd(
            'foo',
            fn($v) => $v === '',
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
     * Teste le comportement de callHandleCrudAdd lorsque le champ est vide.
     *
     * Ce test simule une requête POST avec un champ 'foo' vide et un token CSRF valide.
     * Il vérifie que :
     * - Le traitement processAdd ne doit PAS être appelé.
     * - Le formulaire doit être affiché via showForm.
     * - Une erreur doit être enregistrée pour le champ concerné.
     *
     * @return void
     */
    public function testHandleCrudAddEmptyField(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token']       = 'token';
        $_POST['foo']              = '';
        $_SESSION['csrf_token']    = 'token';

        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());

        $called = false;
        $controller->callHandleCrudAdd(
            'foo',
            fn($v) => $v === '',
            function () {
                TestCase::fail('processAdd ne doit pas être appelé si champ vide');
            },
            function () use (&$called) {
                $called = true; // showForm doit être appelé
            }
        );
        $this->assertTrue($called, 'showForm doit être appelé');

        $this->assertArrayHasKey('foo', $controller->getErrors());
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

        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());

        $called = false;
        $controller->callHandleCrudAdd(
            'foo',
            fn($v) => false,
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

        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());

        $controller->callHandleCrudEdit(
            'foo',
            fn($v) => $v === '',
            function ($id, $v) {
                TestCase::assertSame(1, $id);
                TestCase::assertSame('bar', $v);
            },
            function () {
                TestCase::fail('showEditForm ne doit pas être appelé en cas de succès');
            },
            function () {
                TestCase::fail('showEditSelectForm ne doit pas être appelé');
            },
            function () {
                return 1;
            }
        );
    }

    /**
     * Teste le comportement de callHandleCrudEdit lorsque le champ est vide.
     *
     * Ce test simule une requête POST avec un champ 'foo' vide et un identifiant d’édition valide.
     * Il vérifie que :
     * - La méthode processEdit ne doit PAS être appelée.
     * - Le formulaire d’édition doit être affiché (showEditForm).
     * - Le formulaire de sélection ne doit PAS être appelé.
     * - Une erreur doit être enregistrée pour le champ vide.
     *
     * @return void
     */
    public function testHandleCrudEditEmptyField(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token']       = 'token';
        $_POST['edit_id']          = 1;
        $_POST['foo']              = '';
        $_SESSION['csrf_token']    = 'token';

        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());

        $controller->callHandleCrudEdit(
            'foo',
            fn($v) => $v === '',
            function () {
                TestCase::fail('processEdit ne doit pas être appelé si champ vide');
            },
            function ($id) {
                TestCase::assertSame(1, $id);
            },
            function () {
                TestCase::fail('showEditSelectForm ne doit pas être appelé');
            },
            function () {
                return 1;
            }
        );
        $this->assertArrayHasKey('foo', $controller->getErrors());
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

        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());

        $controller->callHandleCrudEdit(
            'foo',
            fn($v) => false,
            function () {
                TestCase::fail('processEdit ne doit pas être appelé si CSRF invalide');
            },
            function ($id) {
                TestCase::assertSame(1, $id);
            },
            function () {
                TestCase::fail('showEditSelectForm ne doit pas être appelé');
            },
            function () {
                return 1;
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

        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());

        $called = false;
        $controller->callHandleCrudEdit(
            'foo',
            fn($v) => false,
            function () {
                TestCase::fail('processEdit ne doit pas être appelé');
            },
            function () {
                TestCase::fail('showEditForm ne doit pas être appelé');
            },
            function () use (&$called) {
                $called = true;
            },
            function () {
                return 1;
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

        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());

        $controller->callHandleCrudDelete(
            function () {return 1;},
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

        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());

        $called = false;
        $controller->callHandleCrudDelete(
            function () {return 1;},
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

        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());

        $called = false;
        $controller->callHandleCrudDelete(
            function () {return 1;},
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

        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());

        $called = false;
        $controller->callHandleCrudEdit(
            'foo',
            fn($v) => false,
            function () {
                TestCase::fail('processEdit ne doit pas être appelé');
            },
            function () {
                TestCase::fail('showEditForm ne doit pas être appelé');
            },
            function () use (&$called) {
                $called = true;
            },
            function () {
                return false; // Simule un ID invalide
            }
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

        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());

        $called = false;
        $controller->callHandleCrudAdd(
            'foo',
            fn($v) => false,
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

        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());

        $called = false;
        $controller->callHandleCrudEdit(
            'foo',
            fn($v) => false,
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
            function () {
                return 1;
            }
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

        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());

        $called = false;
        $controller->callHandleCrudDelete(
            function () {return false;}, // Simule un ID invalide
            function () {TestCase::fail('processDelete ne doit pas être appelé');},
            function () use (&$called) {$called = true;},
            function () {TestCase::fail('showDeleteConfirmForm ne doit pas être appelé');}
        );
        $this->assertTrue($called, 'showDeleteSelectForm doit être appelé');
        $this->assertArrayHasKey('global', $controller->getErrors());
    }
}
