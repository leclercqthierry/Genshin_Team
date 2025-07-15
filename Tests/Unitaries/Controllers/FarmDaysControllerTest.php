<?php
declare (strict_types = 1);

use GenshinTeam\Connexion\Database;
use GenshinTeam\Controllers\FarmDaysController;
use GenshinTeam\Models\FarmDaysModel;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorPresenterInterface;
use Psr\Log\LoggerInterface;
use Tests\TestCase\FarmDaysControllerTestCase;

/**
 * @covers \GenshinTeam\Controllers\FarmDaysController
 *
 * Classe de tests unitaires pour le contrôleur FarmDaysController.
 */
class FarmDaysControllerTest extends FarmDaysControllerTestCase
{

    /**
     * Vérifie l'ajout valide d'un jour de farm.
     */
    public function testHandleAddValid(): void
    {
        $model = $this->createMock(FarmDaysModel::class);
        $model->expects($this->once())->method('add')->with('Lundi/Mardi')->willReturn(true);

        $controller = $this->getController($model);
        $controller->setCurrentRoute('add-farm-days');
        $this->preparePostRequest(['days' => ['Lundi', 'Mardi']]);

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('ajouté', $output);
    }

    /**
     * Vérifie la gestion d'un échec lors de l'ajout.
     */
    public function testHandleAddFailure(): void
    {
        $model = $this->createMock(FarmDaysModel::class);
        $model->expects($this->once())->method('add')->with('Lundi/Mardi')->willReturn(false);

        $controller = $this->getController($model);
        $controller->setCurrentRoute('add-farm-days');
        $this->preparePostRequest(['days' => ['Lundi', 'Mardi']]);

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertSame(
            "Erreur lors de l'ajout.",
            $controller->getErrors()['global'] ?? null
        );

        $this->assertStringContainsString('<form>add</form>', $output);
    }

    /**
     * Vérifie que la soumission d'un jour invalide en mode ajout
     * affiche un message d'erreur indiquant le jour incorrect.
     */
    public function testHandleAddInvalidDaySetsFieldError(): void
    {
        $model = $this->createMock(FarmDaysModel::class);
        $model->expects($this->never())->method('add');

        $controller = $this->getController($model);
        $controller->setCurrentRoute('add-farm-days');

        $this->preparePostRequest(['days' => ['Lundi', 'Funday']]);

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        // On vérifie que l'output est bien généré
        $this->assertIsString($output);
        $this->assertStringContainsString('<form>add</form>', $output);

        // Et maintenant, on vérifie que l’erreur a bien été enregistrée sous la clé "days"
        $errors = $controller->getErrors();
        $this->assertArrayHasKey('days', $errors);
        $this->assertStringContainsString('Jour invalide : Funday', $errors['days']);
    }

    /**
     * Teste que le bloc `catch` de la méthode `handleAdd()` intercepte bien les exceptions.
     *
     * Ce test garantit que, lorsqu'une exception est levée depuis `handleCrudAdd()`, elle est
     * correctement capturée par le bloc `catch (\Throwable $e)` de `handleAdd()`.
     * Une sous-classe anonyme de `FarmDaysController` redéfinit `handleCrudAdd()` pour y injecter
     * une exception déclenchée volontairement.
     *
     * Aucune assertion n'est nécessaire ici, car l'objectif est de couvrir le bloc `catch` et
     * de s'assurer que l'exception ne provoque pas d'erreur fatale. Ce test aide à améliorer
     * la couverture du code de gestion des erreurs.
     *
     * @covers ::handleAdd
     * @return void
     */
    public function testHandleAddCoversOuterCatch(): void
    {
        $renderer       = new Renderer($this->viewPath);
        $logger         = $this->createMock(LoggerInterface::class);
        $errorPresenter = $this->createMock(ErrorPresenterInterface::class);
        $session        = new SessionManager();
        $model          = new FarmDaysModel(Database::getInstance(), $logger);

        $controller = new class($renderer, $logger, $errorPresenter, $session, $model) extends FarmDaysController
        {
            public function testableHandleAdd(): void
            {
                $this->handleAdd();
            }

            protected function handleCrudAdd(string $a, callable $b, callable $c): void
            {
                throw new \RuntimeException('Erreur simulée directe dans handleCrudAdd');
            }
        };

        $controller->testableHandleAdd();

        $this->expectNotToPerformAssertions();

    }

    /**
     * Vérifie l'édition valide d'un jour de farm.
     */
    public function testHandleEditValid(): void
    {
        $model = $this->createMock(FarmDaysModel::class);
        $model->method('get')->with(1)->willReturn(['id_farm_days' => 1, 'days' => 'Lundi']);
        $model->expects($this->once())->method('update')->with(1, 'Lundi/Mardi')->willReturn(true);

        $controller = $this->getController($model);
        $controller->setCurrentRoute('edit-farm-days');

        $this->preparePostRequest([
            'days'    => ['Lundi', 'Mardi'],
            'edit_id' => 1,
        ]);

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('modifié', $output);
    }

    /**
     * Vérifie que l'édition d'une entité avec des jours vides
     * entraîne un message d'erreur demandant au moins un jour sélectionné.
     */
    public function testHandleEditEmptyDaysShowsValidationError(): void
    {
        $model = $this->createMock(FarmDaysModel::class);
        $model->method('get')->with(1)->willReturn(['id_farm_days' => 1, 'days' => '']);

        $controller = $this->getController($model);
        $controller->setCurrentRoute('edit-farm-days');

        $this->preparePostRequest([
            'days'    => [],
            'edit_id' => 1,
        ]);

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertArrayHasKey('days', $controller->getErrors());
        $this->assertSame(
            'Veuillez sélectionner au moins un jour.',
            $controller->getErrors()['days']
        );

    }

    /**
     * Teste que le bloc `catch` de la méthode `handleEdit()` intercepte bien les exceptions.
     *
     * Ce test vérifie que, lorsqu’une exception est levée depuis `handleCrudEdit()`, elle est
     * correctement capturée par le bloc `catch (\Throwable $e)` présent dans `handleEdit()`.
     * Une sous-classe anonyme de `FarmDaysController` redéfinit `handleCrudEdit()` pour y déclencher
     * une exception volontairement, simulant ainsi un scénario d'erreur.
     *
     * Ce test n'effectue aucune assertion fonctionnelle : son objectif est uniquement d'assurer
     * que le bloc `catch` est bien exécuté et que l'exception ne provoque pas d'interruption,
     * afin d'assurer une couverture correcte de la gestion d’erreur dans `handleEdit()`.
     *
     * @covers ::handleEdit
     * @return void
     */
    public function testHandleEditCoversOuterCatch(): void
    {
        $renderer       = new Renderer($this->viewPath);
        $logger         = $this->createMock(LoggerInterface::class);
        $errorPresenter = $this->createMock(ErrorPresenterInterface::class);
        $session        = new SessionManager();
        $model          = new FarmDaysModel(Database::getInstance(), $logger);

        $controller = new class($renderer, $logger, $errorPresenter, $session, $model) extends FarmDaysController
        {
            public function testableHandleEdit(): void
            {
                $this->handleEdit();
            }

            protected function handleCrudEdit(string $e, callable $a, callable $b, callable $c): void
            {
                throw new \RuntimeException('Erreur simulée directe dans handleCrudEdit');
            }
        };

        $controller->testableHandleEdit();

        $this->expectNotToPerformAssertions();
    }

    /**
     * Teste le comportement de processEdit en cas d’échec lors de la mise à jour.
     *
     * Ce test simule une tentative de modification d’un enregistrement pour laquelle
     * la méthode update du modèle retourne false (échec).
     *
     * Il vérifie que :
     * - Une erreur globale est bien enregistrée.
     * - Les anciennes valeurs saisies (jours) sont correctement conservées via getOld()
     *   afin d’assurer un pré-remplissage du formulaire après l’échec.
     *
     * @return void
     */
    public function testProcessEditFailure(): void
    {
        $model = $this->createMock(FarmDaysModel::class);
        $model->method('update')->with(1, 'Lundi/Mardi')->willReturn(false);

        $controller = $this->getController($model);
        $controller->setCurrentRoute('edit-farm-days');

        $days = ['Lundi', 'Mardi'];

        // On utilise la réflexion pour accéder à la méthode privée
        ob_start();
        $reflection = new \ReflectionClass($controller);
        $method     = $reflection->getMethod('processEdit');
        $method->setAccessible(true);

        // Appel de la méthode
        $method->invoke($controller, 1, $days);
        ob_end_clean();

        $this->assertArrayHasKey('global', $controller->getErrors());
        $this->assertEquals(['days' => $days], $controller->getOld());
    }

    /**
     * Vérifie la suppression valide d'un jour de farm.
     */
    public function testHandleDeleteValid(): void
    {
        $model = $this->createMock(FarmDaysModel::class);
        $model->method('get')->with(1)->willReturn(['id_farm_days' => 1, 'days' => 'Lundi']);
        $model->expects($this->once())->method('delete')->with(1)->willReturn(true);

        $controller = $this->getController($model);
        $controller->setCurrentRoute('delete-farm-days');

        $this->preparePostRequest([
            'delete_id'      => 1,
            'confirm_delete' => 1,
        ]);

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('supprimé', $output);
    }

    /**
     * Vérifie la gestion d'un échec lors de la suppression.
     */
    public function testHandleDeleteFailure(): void
    {
        $model = $this->createMock(FarmDaysModel::class);
        $model->method('get')->with(1)->willReturn(['id_farm_days' => 1, 'days' => 'Lundi']);
        $model->expects($this->once())->method('delete')->with(1)->willReturn(false);

        $controller = $this->getController($model);
        $controller->setCurrentRoute('delete-farm-days');

        $this->preparePostRequest([
            'delete_id'      => 1,
            'confirm_delete' => 1,
        ]);

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);

        $this->assertArrayHasKey('global', $controller->getErrors());
        $this->assertSame(
            'Erreur lors de la suppression.',
            $controller->getErrors()['global'] ?? null
        );

    }

    /**
     * Vérifie que le bloc `catch` de la méthode `handleDelete()` est effectivement exécuté
     * lorsqu'une exception est levée depuis `handleCrudDelete()`.
     *
     * Ce test instancie une sous-classe anonyme de `FarmDaysController` dans laquelle
     * la méthode `handleCrudDelete()` est volontairement redéfinie pour lancer une
     * exception. Cela permet de s'assurer que le `catch (\Throwable $e)` dans
     * `handleDelete()` est bien activé et donc couvert par l'outil de couverture de code.
     *
     * Ce test ne contient pas d'assertion sur le comportement de gestion d'erreur,
     * mais garantit que l’exception est absorbée et que l’exécution ne plante pas.
     *
     * @covers ::handleDelete
     */
    public function testHandleDeleteCoversOuterCatch(): void
    {
        $renderer       = new Renderer($this->viewPath);
        $logger         = $this->createMock(LoggerInterface::class);
        $errorPresenter = $this->createMock(ErrorPresenterInterface::class);
        $session        = new SessionManager();
        $model          = new FarmDaysModel(Database::getInstance(), $logger);

        $controller = new class($renderer, $logger, $errorPresenter, $session, $model) extends FarmDaysController
        {
            public function testableHandleDelete(): void
            {
                $this->handleDelete();
            }

            protected function handleCrudDelete(callable $b, callable $c, callable $d): void
            {
                throw new \RuntimeException('Erreur simulée directe dans handleCrudDelete');
            }
        };

        $controller->testableHandleDelete();

        $this->expectNotToPerformAssertions();
    }

    /**
     * Teste l'affichage du formulaire de confirmation de suppression.
     *
     * Ce test utilise un mock du modèle FarmDaysModel pour simuler le retour d’un enregistrement
     * avec l’identifiant 1. Il vérifie que :
     * - La méthode privée showDeleteConfirmForm peut être invoquée via réflexion.
     * - Une sortie HTML est générée.
     * - Cette sortie contient un formulaire de suppression comportant les balises attendues.
     *
     * @return void
     */
    public function testShowDeleteConfirmForm(): void
    {
        $model = $this->createMock(FarmDaysModel::class);
        $model->method('get')->with(1)->willReturn(['id_farm_days' => 1, 'days' => 'Lundi/Mardi']);
        $controller = $this->getController($model);
        $controller->setCurrentRoute('delete-farm-days');

        ob_start();
        $reflection = new \ReflectionClass($controller);
        $method     = $reflection->getMethod('showDeleteConfirmForm');
        $method->setAccessible(true);
        $method->invoke($controller, 1);
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('<form>confirm</form>', $output);
    }

    /**
     * Teste que la méthode showDeleteConfirmForm appelle showDeleteSelectForm
     * lorsqu'un enregistrement avec l'ID fourni est introuvable.
     *
     * Ce test utilise un mock de FarmDaysModel retournant `null` pour simuler
     * un ID inexistant. Il vérifie ensuite que showDeleteSelectForm est
     * bien invoqué et qu'une erreur globale est enregistrée.
     *
     * @return void
     */
    public function testShowDeleteConfirmFormNotFound(): void
    {
        $model = $this->createMock(FarmDaysModel::class);
        $model->method('get')->with(99)->willReturn(null); // Simule un ID inexistant
        $controller = $this->getController($model);
        $controller->setCurrentRoute('delete-farm-days');

        $wasCalled       = false;
        $controllerProxy = new class($controller, $wasCalled) extends FarmDaysController
        {
            public bool $wasCalled;
            public function __construct(object $base, bool &$wasCalled)
            {
                foreach (get_object_vars($base) as $k => $v) {
                    $this->$k = $v;
                }
                $this->wasCalled = &$wasCalled;
            }
            protected function showDeleteSelectForm(): void
            {
                $this->wasCalled = true;
            }
            protected function renderDefault(): void
            {
                // Empêche l'affichage HTML parasite
            }
        };

        $reflection = new \ReflectionClass($controllerProxy);
        $method     = $reflection->getMethod('showDeleteConfirmForm');
        $method->setAccessible(true);
        $method->invoke($controllerProxy, 99);

        $this->assertTrue($controllerProxy->wasCalled, 'showDeleteSelectForm doit être appelé si le record est introuvable');
        $this->assertArrayHasKey('global', $controllerProxy->getErrors());
    }

    /**
     * Vérifie l'affichage de la liste des jours de farm.
     */
    public function testShowList(): void
    {
        $model = $this->createMock(FarmDaysModel::class);
        $model->method('getAll')->willReturn([['id_farm_days' => 1, 'days' => 'Lundi']]);
        $controller = $this->getController($model);
        $controller->setCurrentRoute('farm-days-list');

        $_SERVER['REQUEST_METHOD'] = 'GET';

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('<ul>list</ul>', $output);
    }

    /**
     * Teste que la méthode protégée `showList()` capture correctement les exceptions via son bloc `catch`.
     *
     * Ce test simule une situation où `model->getAll()` déclenche une exception. Une sous-classe anonyme
     * du contrôleur est utilisée pour redéfinir le comportement du modèle ou injecter une dépendance factice
     * qui lance une exception lors de l’appel à `getAll()`.
     *
     * L'objectif est de s'assurer que le bloc `catch (\Throwable $e)` est bien exécuté, que l'exception
     * est absorbée sans remonter, et que la méthode `handleException()` est effectivement invoquée.
     * Ce test vise principalement la couverture de la gestion d'erreur dans `showList()`.
     *
     * @covers ::showList
     * @return void
     */
    public function testShowListCatchesThrowable(): void
    {
        $renderer       = new Renderer($this->viewPath);
        $logger         = $this->createMock(LoggerInterface::class);
        $errorPresenter = $this->createMock(ErrorPresenterInterface::class);
        $session        = new SessionManager();

        // Faux modèle qui lève une exception sur getAll()
        $model = $this->createMock(FarmDaysModel::class);
        $model->method('getAll')
            ->willThrowException(new \RuntimeException('Erreur simulée'));

        // Contrôleur redéfini pour capturer handleException()
        $controller = new class($renderer, $logger, $errorPresenter, $session, $model) extends FarmDaysController
        {
            public bool $caught = false;

            public function testableShowList(): void
            {
                $this->showList();
            }

            protected function handleException(\Throwable $e): void
            {
                $this->caught = true;
            }
        };

        // Appel via méthode publique wrapper
        $controller->testableShowList();

        // Vérifie que le catch a été exécuté
        $this->assertTrue($controller->caught, 'Le bloc catch de showList() n’a pas été déclenché comme prévu');
    }
}
