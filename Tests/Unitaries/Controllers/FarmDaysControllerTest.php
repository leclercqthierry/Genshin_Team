<?php
declare (strict_types = 1);

use GenshinTeam\Controllers\FarmDaysController;
use GenshinTeam\Models\FarmDays;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorPresenterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \GenshinTeam\Controllers\FarmDaysController
 *
 * Classe de tests unitaires pour le contrôleur FarmDaysController.
 */
class FarmDaysControllerTest extends TestCase
{
    /** @var string Chemin temporaire vers les vues de test */
    private string $viewPath;

    /**
     * Prépare l'environnement de test en créant des vues minimales temporaires.
     */
    protected function setUp(): void
    {
        $this->viewPath = sys_get_temp_dir() . '/views_' . uniqid();
        @mkdir($this->viewPath, 0777, true);
        @mkdir($this->viewPath . '/farm-days', 0777, true);
        @mkdir($this->viewPath . '/partials', 0777, true);

        @mkdir($this->viewPath . '/templates', 0777, true);

        // Création de vues minimales pour simuler les rendus attendus
        file_put_contents(
            $this->viewPath . '/farm-days/add-farm-days.php',
            // Affiche le bouton Modifier si mode édition, les erreurs globales et day, puis le formulaire
            '<?php if (isset($mode) && $mode === "edit") { echo "<button>Modifier</button>"; } ?><?php if (!empty($errors["global"])) echo $errors["global"]; ?><?php if (!empty($errors["day"])) echo $errors["day"]; ?><form>add</form>'
        );
        file_put_contents(
            $this->viewPath . '/partials/select-item.php',
            // Affiche les erreurs globales puis le select
            '<?php if (!empty($errors["global"])) echo $errors["global"]; ?><select>select</select>'
        );
        file_put_contents($this->viewPath . '/farm-days/delete-farm-days-confirm.php', '<form>delete</form>');
        file_put_contents($this->viewPath . '/farm-days/farm-days-list.php', '<ul>list</ul>');
        file_put_contents($this->viewPath . '/templates/default.php', '<html><?= $title ?? "" ?><?= $content ?? "" ?></html>');

        // Génère un token CSRF valide pour tous les tests
        $csrf                   = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $csrf;
        $_POST['csrf_token']    = $csrf;

    }

    /**
     * Nettoie l'environnement de test en supprimant les fichiers temporaires.
     */
    protected function tearDown(): void
    {
        @unlink($this->viewPath . '/farm-days/add-farm-days.php');
        @unlink($this->viewPath . '/farm-days/delete-farm-days-confirm.php');
        @unlink($this->viewPath . '/farm-days/farm-days-list.php');
        @unlink($this->viewPath . '/templates/default.php');
        @unlink($this->viewPath . '/partials/select-item.php');
        @rmdir($this->viewPath . '/partials');
        @rmdir($this->viewPath . '/farm-days');
        @rmdir($this->viewPath . '/templates');
        @rmdir($this->viewPath);
    }

    /**
     * Instancie le contrôleur FarmDaysController avec des dépendances mockées.
     *
     * @param FarmDays $model   Modèle mocké FarmDays
     * @param string   $route   Nom de la route à tester
     * @return FarmDaysController
     */
    private function getController(FarmDays $model, string $route): FarmDaysController
    {
        $renderer  = new Renderer($this->viewPath);
        $logger    = $this->createMock(LoggerInterface::class);
        $presenter = $this->createMock(ErrorPresenterInterface::class);
        $session   = new SessionManager();

        $controller = new FarmDaysController($renderer, $logger, $presenter, $session, $model);
        $controller->setCurrentRoute($route);
        return $controller;
    }

    /**
     * Vérifie l'ajout valide d'un jour de farm.
     */
    public function testHandleAddValid(): void
    {
        $model = $this->createMock(FarmDays::class);
        $model->expects($this->once())->method('add')->with('Lundi/Mardi')->willReturn(true);

        $controller                = $this->getController($model, 'add-farm-days');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['days']             = ['Lundi', 'Mardi'];

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
        $model = $this->createMock(FarmDays::class);
        $model->expects($this->once())->method('add')->with('Lundi/Mardi')->willReturn(false);

        $controller                = $this->getController($model, 'add-farm-days');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['days']             = ['Lundi', 'Mardi'];

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('Erreur lors de l\'ajout.', $output);
    }

    /**
     * Vérifie l'édition valide d'un jour de farm.
     */
    public function testHandleEditValid(): void
    {
        $model = $this->createMock(FarmDays::class);
        $model->method('get')->with(1)->willReturn(['id_farm_days' => 1, 'days' => 'Lundi']);
        $model->expects($this->once())->method('update')->with(1, 'Lundi/Mardi')->willReturn(true);

        $controller                = $this->getController($model, 'edit-farm-days');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['edit_id']          = 1;
        $_POST['days']             = ['Lundi', 'Mardi'];

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('modifié', $output);
    }

    /**
     * Vérifie la suppression valide d'un jour de farm.
     */
    public function testHandleDeleteValid(): void
    {
        $model = $this->createMock(FarmDays::class);
        $model->method('get')->with(1)->willReturn(['id_farm_days' => 1, 'days' => 'Lundi']);
        $model->expects($this->once())->method('delete')->with(1)->willReturn(true);

        $controller                = $this->getController($model, 'delete-farm-days');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['delete_id']        = 1;
        $_POST['confirm_delete']   = 1;

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
        $model = $this->createMock(FarmDays::class);
        $model->method('get')->with(1)->willReturn(['id_farm_days' => 1, 'days' => 'Lundi']);
        $model->expects($this->once())->method('delete')->with(1)->willReturn(false);

        $controller                = $this->getController($model, 'delete-farm-days');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['delete_id']        = 1;
        $_POST['confirm_delete']   = 1;

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('Erreur lors de la suppression.', $output);
    }

    /**
     * Vérifie l'affichage de la liste des jours de farm.
     */
    public function testShowList(): void
    {
        $model = $this->createMock(FarmDays::class);
        $model->method('getAll')->willReturn([['id_farm_days' => 1, 'days' => 'Lundi']]);
        $controller                = $this->getController($model, 'farm-days-list');
        $_SERVER['REQUEST_METHOD'] = 'GET';

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('<ul>list</ul>', $output);
    }

    /**
     * Teste l'affichage du formulaire de sélection pour l’édition.
     *
     * Ce test vérifie que la méthode privée showEditSelectForm :
     * - Est accessible via la réflexion.
     * - Produit une sortie HTML contenant un élément <select>.
     *
     * Le modèle est mocké pour retourner deux entrées fictives représentant des jours de farm.
     *
     * @return void
     */
    public function testShowEditSelectForm(): void
    {
        $model = $this->createMock(FarmDays::class);
        $model->method('getAll')->willReturn([
            ['id_farm_days' => 1, 'days' => 'Lundi'],
            ['id_farm_days' => 2, 'days' => 'Mardi'],
        ]);
        $controller = $this->getController($model, 'edit-farm-days');

        ob_start();
        $reflection = new \ReflectionClass($controller);
        $method     = $reflection->getMethod('showEditSelectForm');
        $method->setAccessible(true);
        $method->invoke($controller);
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('<select>select</select>', $output);
    }

    /**
     * Teste l'affichage du formulaire d'édition pour un jour de farm spécifique.
     *
     * Ce test utilise un mock du modèle FarmDays pour retourner une entrée correspondant à l’ID 1.
     * Il vérifie que :
     * - La méthode privée showEditForm est correctement invoquée via réflexion.
     * - Une sortie HTML est bien générée.
     * - Le bouton "Modifier" est présent dans le rendu.
     *
     * @return void
     */
    public function testShowEditForm(): void
    {
        $model = $this->createMock(FarmDays::class);
        $model->method('get')->with(1)->willReturn(['id_farm_days' => 1, 'days' => 'Lundi/Mardi']);
        $controller = $this->getController($model, 'edit-farm-days');

        ob_start();
        $reflection = new \ReflectionClass($controller);
        $method     = $reflection->getMethod('showEditForm');
        $method->setAccessible(true);
        $method->invoke($controller, 1);
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('<button>Modifier</button>', $output);
    }

    /**
     * Teste l'affichage du formulaire de confirmation de suppression.
     *
     * Ce test utilise un mock du modèle FarmDays pour simuler le retour d’un enregistrement
     * avec l’identifiant 1. Il vérifie que :
     * - La méthode privée showDeleteConfirmForm peut être invoquée via réflexion.
     * - Une sortie HTML est générée.
     * - Cette sortie contient un formulaire de suppression comportant les balises attendues.
     *
     * @return void
     */
    public function testShowDeleteConfirmForm(): void
    {
        $model = $this->createMock(FarmDays::class);
        $model->method('get')->with(1)->willReturn(['id_farm_days' => 1, 'days' => 'Lundi/Mardi']);
        $controller = $this->getController($model, 'delete-farm-days');

        ob_start();
        $reflection = new \ReflectionClass($controller);
        $method     = $reflection->getMethod('showDeleteConfirmForm');
        $method->setAccessible(true);
        $method->invoke($controller, 1);
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('<form>delete</form>', $output);
    }

    /**
     * Teste le comportement de showEditForm lorsque l'enregistrement est introuvable.
     *
     * Ce test simule une tentative d'édition avec un identifiant inexistant (42).
     * Il vérifie que :
     * - La méthode privée showEditForm est bien invoquée via la réflexion.
     * - Le modèle retourne `null`, simulant l’absence de l’enregistrement.
     * - La méthode showEditSelectForm est appelée à la place, via un proxy anonyme.
     * - Une erreur globale est bien enregistrée pour signaler le problème.
     *
     * @return void
     */
    public function testShowEditFormNotFound(): void
    {
        $model = $this->createMock(FarmDays::class);
        $model->method('get')->with(42)->willReturn(null); // Simule un ID inexistant
        $controller = $this->getController($model, 'edit-farm-days');

        // On va mocker showEditSelectForm pour vérifier qu'il est bien appelé
        $wasCalled       = false;
        $mock            = $this;
        $controllerProxy = new class($controller, $wasCalled, $mock) extends FarmDaysController
        {
            public bool $wasCalled;
            private TestCase $mock;

            public function __construct(object $base, bool &$wasCalled, TestCase $mock)
            {
                foreach (get_object_vars($base) as $k => $v) {
                    $this->$k = $v;
                }
                $this->wasCalled = &$wasCalled;
                $this->mock      = $mock;
            }

            protected function showEditSelectForm(): void
            {
                $this->wasCalled = true;
            }

            protected function renderDefault(): void
            {
                // Empêche l'affichage HTML parasite
            }
        };

        $reflection = new \ReflectionClass($controllerProxy);
        $method     = $reflection->getMethod('showEditForm');
        $method->setAccessible(true);
        $method->invoke($controllerProxy, 42);

        $this->assertTrue($controllerProxy->wasCalled, 'showEditSelectForm doit être appelé si le record est introuvable');
        $this->assertArrayHasKey('global', $controllerProxy->getErrors());
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
        $model = $this->createMock(FarmDays::class);
        $model->method('update')->with(1, 'Lundi/Mardi')->willReturn(false);

        $controller = $this->getController($model, 'edit-farm-days');

        // On prépare les jours à éditer
        $days = ['Lundi', 'Mardi'];

        // On utilise la réflexion pour accéder à la méthode privée
        ob_start();
        $reflection = new \ReflectionClass($controller);
        $method     = $reflection->getMethod('processEdit');
        $method->setAccessible(true);

        // Appel de la méthode
        $method->invoke($controller, 1, $days);
        ob_end_clean();

        // Vérifie qu'une erreur globale a été ajoutée
        $this->assertArrayHasKey('global', $controller->getErrors());
        // Vérifie que les anciens jours sont bien conservés pour le pré-remplissage
        $this->assertEquals(['days' => $days], $controller->getOld());
    }

    /**
     * Teste que la méthode showDeleteConfirmForm appelle showDeleteSelectForm
     * lorsqu'un enregistrement avec l'ID fourni est introuvable.
     *
     * Ce test utilise un mock de FarmDays retournant `null` pour simuler
     * un ID inexistant. Il vérifie ensuite que showDeleteSelectForm est
     * bien invoqué et qu'une erreur globale est enregistrée.
     *
     * @return void
     */
    public function testShowDeleteConfirmFormNotFound(): void
    {
        $model = $this->createMock(FarmDays::class);
        $model->method('get')->with(99)->willReturn(null); // Simule un ID inexistant
        $controller = $this->getController($model, 'delete-farm-days');

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
}
