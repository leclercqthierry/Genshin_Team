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
        @mkdir($this->viewPath . '/templates', 0777, true);

        // Création de vues minimales pour simuler les rendus attendus
        file_put_contents(
            $this->viewPath . '/farm-days/add-farm-days.php',
            // Affiche le bouton Modifier si mode édition, les erreurs globales et day, puis le formulaire
            '<?php if (isset($mode) && $mode === "edit") { echo "<button>Modifier</button>"; } ?><?php if (!empty($errors["global"])) echo $errors["global"]; ?><?php if (!empty($errors["day"])) echo $errors["day"]; ?><form>add</form>'
        );
        file_put_contents(
            $this->viewPath . '/farm-days/farm-days-select.php',
            // Affiche les erreurs globales puis le select
            '<?php if (!empty($errors["global"])) echo $errors["global"]; ?><select>select</select>'
        );
        file_put_contents($this->viewPath . '/farm-days/delete-farm-days-confirm.php', '<form>delete</form>');
        file_put_contents($this->viewPath . '/farm-days/farm-days-list.php', '<ul>list</ul>');
        file_put_contents($this->viewPath . '/templates/default.php', '<html><?= $title ?? "" ?><?= $content ?? "" ?></html>');
    }

    /**
     * Nettoie l'environnement de test en supprimant les fichiers temporaires.
     */
    protected function tearDown(): void
    {
        @unlink($this->viewPath . '/farm-days/add-farm-days.php');
        @unlink($this->viewPath . '/farm-days/farm-days-select.php');
        @unlink($this->viewPath . '/farm-days/delete-farm-days-confirm.php');
        @unlink($this->viewPath . '/farm-days/farm-days-list.php');
        @unlink($this->viewPath . '/templates/default.php');
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
     * Vérifie que le formulaire d'ajout s'affiche en GET.
     */
    public function testHandleAddShowsFormOnGet(): void
    {
        $model                     = $this->createMock(FarmDays::class);
        $controller                = $this->getController($model, 'add-farm-days');
        $_SERVER['REQUEST_METHOD'] = 'GET';

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('<form>add</form>', $output);
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
     * Vérifie la gestion d'un ajout invalide (aucun jour sélectionné).
     */
    public function testHandleAddInvalid(): void
    {
        $model                     = $this->createMock(FarmDays::class);
        $controller                = $this->getController($model, 'add-farm-days');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['days']             = [];

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('sélectionner au moins un jour', $output);
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
     * Vérifie l'affichage du formulaire de sélection pour l'édition.
     */
    public function testShowEditSelectForm(): void
    {
        $model = $this->createMock(FarmDays::class);
        $model->method('getAll')->willReturn([['id_farm_days' => 1, 'days' => 'Lundi']]);
        $controller                = $this->getController($model, 'edit-farm-days');
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_POST['edit_id']);

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('<select>select</select>', $output);
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
     * Vérifie la gestion d'une édition invalide (aucun jour sélectionné).
     */
    public function testHandleEditInvalidDays(): void
    {
        $model = $this->createMock(FarmDays::class);
        $model->method('get')->with(1)->willReturn(['id_farm_days' => 1, 'days' => 'Lundi']);

        $controller                = $this->getController($model, 'edit-farm-days');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['edit_id']          = 1;
        $_POST['days']             = []; // Simule aucune case cochée

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('sélectionner au moins un jour', $output);
    }

    /**
     * Vérifie la gestion d'une édition sur un ID inexistant.
     */
    public function testHandleEditNotFound(): void
    {
        $model = $this->createMock(FarmDays::class);
        $model->method('get')->with(99)->willReturn(null);

        $controller                = $this->getController($model, 'edit-farm-days');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['edit_id']          = 99;
        $_POST['days']             = ['Lundi'];

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('introuvable', $output);
    }

    /**
     * Vérifie l'affichage du formulaire d'édition si aucun jour n'est passé.
     */
    public function testHandleEditShowEditFormIfNoDays(): void
    {
        $model = $this->createMock(FarmDays::class);
        $model->method('get')->with(1)->willReturn(['id_farm_days' => 1, 'days' => 'Lundi']);

        $controller                = $this->getController($model, 'edit-farm-days');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['edit_id']          = 1;
        unset($_POST['days']); // Simule un POST sans 'days'

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);
        // Vérifie que le formulaire d'édition est affiché (présence du bouton "Modifier")
        $this->assertStringContainsString('Modifier', $output);
    }

    /**
     * Teste le comportement du contrôleur lors d'une requête d'édition avec un identifiant invalide.
     *
     * Ce test simule une requête POST envoyant un identifiant non entier pour l'édition de jours de farm.
     * Il vérifie que le contrôleur :
     * - détecte l'ID invalide
     * - affiche le message d'erreur approprié
     * - réaffiche le formulaire de sélection
     *
     * @return void
     */
    public function testHandleEditWithInvalidId(): void
    {
        $model = $this->createMock(FarmDays::class);

        $controller                = $this->getController($model, 'edit-farm-days');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['edit_id']          = 'not-an-int'; // ID non valide
        $_POST['days']             = ['Lundi'];

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('ID invalide', $output);
        $this->assertStringContainsString('<select>select</select>', $output); // formulaire de sélection affiché
    }

    /**
     * Vérifie l'affichage du formulaire de sélection pour la suppression.
     */
    public function testShowDeleteSelectForm(): void
    {
        $model = $this->createMock(FarmDays::class);
        $model->method('getAll')->willReturn([['id_farm_days' => 1, 'days' => 'Lundi']]);
        $controller                = $this->getController($model, 'delete-farm-days');
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_POST['delete_id']);

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('<select>select</select>', $output);
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
     * Teste le comportement du contrôleur lors d'une tentative de suppression avec un identifiant invalide.
     *
     * Ce test simule une requête POST contenant un identifiant de suppression non entier.
     * Il vérifie que le contrôleur :
     * - identifie correctement l'ID comme invalide
     * - affiche un message d’erreur explicite
     * - réaffiche le formulaire de sélection des éléments à supprimer
     *
     * @return void
     */
    public function testHandleDeleteWithInvalidId(): void
    {
        $model = $this->createMock(FarmDays::class);

        $controller                = $this->getController($model, 'delete-farm-days');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['delete_id']        = 'not-an-int'; // ID non valide
        $_POST['confirm_delete']   = 1;

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('ID invalide', $output);
        $this->assertStringContainsString('<select>select</select>', $output); // formulaire de sélection affiché
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
     * Vérifie l'affichage du formulaire de confirmation de suppression si non confirmé.
     */
    public function testHandleDeleteShowConfirmFormIfNoConfirm(): void
    {
        $model = $this->createMock(FarmDays::class);
        $model->method('get')->with(1)->willReturn(['id_farm_days' => 1, 'days' => 'Lundi']);

        $controller                = $this->getController($model, 'delete-farm-days');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['delete_id']        = 1;
        unset($_POST['confirm_delete']); // Simule un POST sans 'confirm_delete'

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);
        // Vérifie que le formulaire de confirmation est affiché
        $this->assertStringContainsString('<form>delete</form>', $output);
    }

    /**
     * Vérifie la gestion d'une demande de suppression sur un ID inexistant.
     */
    public function testShowDeleteConfirmFormNotFound(): void
    {
        $model = $this->createMock(FarmDays::class);
        $model->method('get')->with(99)->willReturn(null);

        $controller                = $this->getController($model, 'delete-farm-days');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['delete_id']        = 99;
        unset($_POST['confirm_delete']);

        ob_start();
        $controller->run();
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('introuvable', $output);
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
}
