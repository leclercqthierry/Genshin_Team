<?php
declare (strict_types = 1);

use GenshinTeam\Connexion\Database;
use GenshinTeam\Controllers\StatController;
use GenshinTeam\Models\Stat;
use GenshinTeam\Models\StatModel;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorPresenterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tests\TestCase\StatControllerTestCase;

/**
 * Tests unitaires pour le contrôleur StatController.
 *
 * Ces tests couvrent tous les cas d'usage métier du contrôleur :
 * - Ajout, édition, suppression de moyens d'obtention
 * - Affichage des formulaires et des listes
 * - Gestion des erreurs et des cas limites (ID inexistant, échec modèle, etc.)
 *
 * Les mocks sont utilisés pour isoler le contrôleur de ses dépendances (modèle, renderer, session, etc.).
 */
class StatControllerTest extends StatControllerTestCase
{

    /**
     * Exécute handleAdd et vérifie le résultat attendu.
     *
     * @param bool   $expectedSuccess      true si l'ajout doit réussir, false sinon
     */
    private function assertHandleAddOutcome(bool $expectedSuccess): void
    {
        $model = $this->createMock(StatModel::class);
        $model->expects($this->once())
            ->method('add')
            ->with('Nouvelle stat')
            ->willReturn($expectedSuccess);

        $controller = $this->getController($model);
        $controller->setCurrentRoute('add-stat');
        $this->preparePost(['stat' => 'Nouvelle stat']);

        ob_start();
        $ref = new \ReflectionMethod($controller, 'handleAdd');
        $ref->setAccessible(true);
        $ref->invoke($controller);
        $output = ob_get_clean();

        $this->assertIsString($output);

        if ($expectedSuccess) {
            $this->assertStringContainsString('Succès', $output);
            $this->assertStringContainsString("Statistique ajoutée !", $output);
        } else {
            $this->assertSame(
                "Erreur lors de l'ajout.",
                $controller->getErrors()['global'] ?? null
            );
            $this->assertStringContainsString('<form>add</form>', $output);
        }
    }

    /**
     * Vérifie qu'un ajout valide appelle le modèle et affiche le succès.
     */
    public function testHandleAddValid(): void
    {
        $this->assertHandleAddOutcome(true);
    }

    /**
     * Vérifie la gestion d'un échec lors de l'ajout dans StatController.
     */
    public function testHandleAddFailure(): void
    {
        $this->assertHandleAddOutcome(false);

    }

    /**
     * Teste la gestion des erreurs de validation pour différentes entrées invalides.
     *
     * @param string $input  La valeur soumise dans le champ 'obtaining'
     * @param string $expectedErrorMessage  Le message d'erreur attendu
     */
    private function assertInvalidStatInput(string $input, string $expectedErrorMessage): void
    {
        $model      = $this->createMock(StatModel::class);
        $controller = $this->getController($model);
        $controller->setCurrentRoute('add-stat');

        $this->preparePost(['stat' => $input]);

        ob_start();
        $ref = new \ReflectionMethod($controller, 'handleAdd');
        $ref->setAccessible(true);
        $ref->invoke($controller);
        $output = ob_get_clean();

        $this->assertSame($expectedErrorMessage, $controller->getErrors()['stat'] ?? null);
        $this->assertStringContainsString('<form>add</form>', (string) $output);

        $refOld = new \ReflectionMethod($controller, 'getOld');
        $refOld->setAccessible(true);
        $old = $refOld->invoke($controller);

        $this->assertSame(['stat' => $input], $old);
    }

    /**
     * Teste le scénario où une validation échoue lors de l'ajout d'un moyen d'obtention vide.
     *
     * Ce test vérifie que :
     * - Une entrée invalide déclenche une erreur de validation.
     * - L'erreur est bien enregistrée via `getErrors()`.
     * - Le formulaire est réaffiché.
     * - Les données invalides sont conservées via `getOld()`.
     */
    public function testHandleAddValidationFailureWithEmptyStat(): void
    {
        $this->assertInvalidStatInput('', 'Le champ statistique est obligatoire.');
    }

    /**
     * Teste le scénario où une validation échoue lors de l'ajout d'un moyen d'obtention trop court.
     *
     * Ce test vérifie que :
     * - Une entrée invalide déclenche une erreur de validation.
     * - L'erreur est bien enregistrée via `getErrors()`.
     * - Le formulaire est réaffiché.
     * - Les données invalides sont conservées via `getOld()`.
     */
    public function testHandleAddValidationFailureWithStatTooShort(): void
    {
        $this->assertInvalidStatInput('T', 'La statistique doit avoir au moins 2 caractères.');
    }

    /**
     * Teste le scénario où une validation échoue lors de l'ajout d'un moyen d'obtention contenant des caractères spéciaux.
     *
     * Ce test vérifie que :
     * - Une entrée invalide déclenche une erreur de validation.
     * - L'erreur est bien enregistrée via `getErrors()`.
     * - Le formulaire est réaffiché.
     * - Les données invalides sont conservées via `getOld()`.
     */
    public function testHandleAddValidationFailureWithSpecialCharacters(): void
    {
        $this->assertInvalidStatInput('test!', 'Lettres, chiffres, espaces, % ou + uniquement.');
    }

    /**
     * Vérifie que handleAdd() signale une erreur lorsque l'on soumet un nom d'obtention déjà existant.
     *
     * Ce test simule le cas où l'utilisateur soumet un nom ("Doublon") qui existe déjà en base.
     * Il vérifie que :
     * - la méthode existsByName() est appelée et retourne true ;
     * - l'erreur de validation "Ce moyen d'obtention existe déjà." est correctement ajoutée ;
     * - le formulaire de saisie est affiché de nouveau ;
     * - la valeur saisie est conservée via la méthode getOld().
     */
    public function testHandleAddValidationFailureWithNonUniqueStat(): void
    {
        $model = $this->createMock(StatModel::class);
        $model->method('existsByName')->with('Doublon')->willReturn(true); // Simule que "Doublon" existe déjà
        $model->method('add')->willReturn(false);                          // L'ajout ne se fera pas

        $controller = $this->getController($model);
        $controller->setCurrentRoute('add-stat');
        $this->preparePost(['stat' => 'Doublon']);

        ob_start();
        $ref = new \ReflectionMethod($controller, 'handleAdd');
        $ref->setAccessible(true);
        $ref->invoke($controller);
        $output = ob_get_clean();

        $this->assertSame('Cette statistique existe déjà.', $controller->getErrors()['stat'] ?? null);
        $this->assertStringContainsString('<form>add</form>', (string) $output);

        $refOld = new \ReflectionMethod($controller, 'getOld');
        $refOld->setAccessible(true);
        $old = $refOld->invoke($controller);
        $this->assertSame(['stat' => 'Doublon'], $old);
    }

    /**
     * Teste le comportement de la méthode handleEdit du contrôleur
     * selon que la mise à jour du moyen d'obtention réussisse ou échoue.
     *
     * Cette méthode :
     * - Mock un modèle StatModel avec des retours prédéfinis pour les méthodes `get` et `update`
     * - Simule une requête POST pour modifier un moyen d'obtention
     * - Invoque la méthode privée handleEdit via Reflection
     * - Vérifie la sortie et les messages d'erreur ou de succès générés
     *
     * @param bool $expectedSuccess Indique si la mise à jour est censée réussir ou échouer
     */
    private function assertHandleEditOutcome(bool $expectedSuccess): void
    {
        $model = $this->createMock(StatModel::class);
        $model->method('get')
            ->with(1)
            ->willReturn(['id_stat' => 1, 'name' => 'Ancienne stat']);
        $model->method('update')
            ->with(1, 'Nouvelle stat')
            ->willReturn($expectedSuccess);

        $controller = $this->getController($model);
        $controller->setCurrentRoute('edit-stat');

        $this->preparePost([
            'edit_id' => 1,
            'stat'    => 'Nouvelle stat',
        ]);

        ob_start();
        $ref = new \ReflectionMethod($controller, 'handleEdit');
        $ref->setAccessible(true);
        $ref->invoke($controller);
        $output = ob_get_clean();

        $this->assertIsString($output);

        if ($expectedSuccess) {
            $this->assertStringContainsString('Succès', $output);
            $this->assertStringContainsString("Statistique modifiée !", $output);
        } else {
            $this->assertSame(
                "Erreur lors de la modification.",
                $controller->getErrors()['global'] ?? null
            );
            $this->assertStringContainsString('<form>add</form>', $output); // car add.php utilisée
        }
    }

    /**
     * Vérifie qu'une édition valide affiche le message de succès.
     */
    public function testHandleEditValid(): void
    {
        $this->assertHandleEditOutcome(true);
    }

    /**
     * Vérifie la gestion d’un échec lors de l’édition dans StatController.
     */
    public function testHandleEditFailure(): void
    {
        $this->assertHandleEditOutcome(false);
    }

    /**
     * Vérifie qu'une édition invalide déclenche une erreur de validation.
     */
    public function testHandleEditValidationFailure(): void
    {
        $model = $this->createMock(StatModel::class);
        $model->method('get')
            ->with(1)
            ->willReturn(['id_stat' => 1, 'name' => 'Ancienne stat']);

        $controller = $this->getController($model);
        $controller->setCurrentRoute('edit-stat');

        $this->preparePost([
            'edit_id' => 1,
            'stat'    => 'T', // Trop court
        ]);

        ob_start();
        $ref = new \ReflectionMethod($controller, 'handleEdit');
        $ref->setAccessible(true);
        $ref->invoke($controller);
        $output = ob_get_clean();

        $this->assertSame('La statistique doit avoir au moins 2 caractères.', $controller->getErrors()['stat'] ?? null);
        $this->assertStringContainsString('<form>add</form>', (string) $output);

        $refOld = new \ReflectionMethod($controller, 'getOld');
        $refOld->setAccessible(true);
        $old = $refOld->invoke($controller);
        $this->assertSame(['stat' => 'T'], $old);
    }

    /**
     * Vérifie que le bloc `catch` d'une méthode handleX intercepte bien les exceptions simulées.
     *
     * @param string $methodToTest     Méthode publique à appeler ('handleAdd' ou 'handleEdit')
     * @param string $crudMethodToMock Méthode à surcharger ('handleCrudAdd' ou 'handleCrudEdit')
     */
    private function assertOuterCatchIsTriggered(string $methodToTest, string $crudMethodToMock): void
    {
        $renderer       = new Renderer($this->viewPath);
        $logger         = $this->createMock(LoggerInterface::class);
        $errorPresenter = $this->createMock(ErrorPresenterInterface::class);
        $session        = new SessionManager();
        $model          = new StatModel(Database::getInstance(), $logger);

        $controller = new class($renderer, $logger, $errorPresenter, $session, $model) extends StatController
        {
            public function testable(string $method): void
            {
                $this->$method();
            }

            protected function handleCrudAdd(string $a, callable $b, callable $c): void
            {
                throw new \RuntimeException('Exception simulée depuis handleCrudAdd');
            }

            protected function handleCrudEdit(string $e, callable $a, callable $b, callable $c): void
            {
                throw new \RuntimeException('Exception simulée depuis handleCrudEdit');
            }
            protected function handleCrudDelete(callable $b, callable $c, callable $d): void
            {
                throw new \RuntimeException('Exception simulée depuis handleCrudDelete');
            }
        };

        $controller->testable($methodToTest);
        $this->expectNotToPerformAssertions();
    }

    /**
     * Teste que le bloc `catch` de la méthode `handleAdd()` intercepte bien les exceptions.
     *
     * Ce test garantit que, lorsqu'une exception est levée depuis `handleCrudAdd()`, elle est
     * correctement capturée par le bloc `catch (\Throwable $e)` de `handleAdd()`.
     * Une sous-classe anonyme de `StatController` redéfinit `handleCrudAdd()` pour y injecter
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
        $this->assertOuterCatchIsTriggered('handleAdd', 'handleCrudAdd');
    }

    /**
     * Teste que le bloc `catch` de la méthode `handleEdit()` intercepte bien les exceptions.
     *
     * Ce test vérifie que, lorsqu’une exception est levée depuis `handleCrudEdit()`, elle est
     * correctement capturée par le bloc `catch (\Throwable $e)` présent dans `handleEdit()`.
     * Une sous-classe anonyme de `StatController` redéfinit `handleCrudEdit()` pour y déclencher
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
        $this->assertOuterCatchIsTriggered('handleEdit', 'handleCrudEdit');
    }

    /**
     * Teste que le bloc `catch` de la méthode `handleDelete()` intercepte bien les exceptions.
     *
     * Ce test vérifie que, lorsqu’une exception est levée depuis `handleCrudDelete()`, elle est
     * correctement capturée par le bloc `catch (\Throwable $e)` présent dans `handleDelete()`.
     * Une sous-classe anonyme de `StatController` redéfinit `handleCrudDelete()` pour y déclencher
     * une exception volontairement, simulant ainsi un scénario d'erreur.
     *
     * Ce test n'effectue aucune assertion fonctionnelle : son objectif est uniquement d'assurer
     * que le bloc `catch` est bien exécuté et que l'exception ne provoque pas d'interruption,
     * afin d'assurer une couverture correcte de la gestion d’erreur dans `handleDelete()`.
     *
     * @covers ::handleDelete
     * @return void
     */
    public function testHandleDeleteCoversOuterCatch(): void
    {
        $this->assertOuterCatchIsTriggered('handleDelete', 'handleCrudDelete');
    }

    /**
     * Teste que le formulaire de sélection d'une statistique à éditer est correctement affiché.
     *
     * Ce test :
     * - Mocke le modèle `Stat` pour retourner une obtention fictive ;
     * - Utilise la méthode privée `showEditSelectForm` via réflexion ;
     * - Capture le rendu HTML et vérifie que la balise <select> générée par la vue est bien présente.
     */
    public function testShowEditSelectForm(): void
    {
        // Création d'un mock de Stat avec une valeur de retour attendue
        $model = $this->createMock(StatModel::class);
        $model->method('getAll')->willReturn([
            ['id_stat' => 1, 'name' => 'obt1'],
        ]);

        // Instanciation du contrôleur avec le modèle mocké
        $controller = $this->getController($model);

        // On rend la méthode privée accessible pour l’invocation directe
        $refMethod = new \ReflectionMethod($controller, 'showEditSelectForm');
        $refMethod->setAccessible(true);

        // Exécution
        ob_start();
        $refMethod->invoke($controller);
        $output = ob_get_clean();

        $this->assertNotFalse($output);
        // Vérification que le contenu HTML attendu est bien présent
        $this->assertStringContainsString('<select>select</select>', $output);
    }

    /**
     * Teste que l'édition avec un ID inexistant appelle showEditSelectForm.
     */
    public function testHandleEditNotFound(): void
    {
        $_POST['edit_id'] = 42;

        // Mock du modèle retournant null pour ID inexistant
        $model = $this->createMock(StatModel::class);
        $model->method('get')->with(42)->willReturn(null);

        // Mock du contrôleur pour intercepter l’appel à showEditSelectForm()
        $controller = $this->getMockBuilder(StatController::class)
            ->setConstructorArgs([
                new Renderer($this->viewPath),
                $this->createMock(LoggerInterface::class),
                $this->createMock(ErrorPresenterInterface::class),
                new SessionManager(),
                $model,
            ])
            ->onlyMethods(['showEditSelectForm'])
            ->getMock();

        // Vérifie que showEditSelectForm est bien appelé
        $controller->expects($this->once())->method('showEditSelectForm');

        // Appelle la méthode protégée handleEdit
        $ref = new \ReflectionMethod($controller, 'handleEdit');
        $ref->setAccessible(true);
        $ref->invoke($controller);
    }

    /**
     * Vérifie que la méthode handleDelete du StatController fonctionne correctement
     * lorsque la suppression de la statistique réussit.
     *
     * Ce test :
     * - Simule une requête POST de suppression avec confirmation
     * - Mock le modèle StatModel pour que la méthode `delete` retourne true
     * - Mock le Renderer pour afficher un message de succès
     * - Instancie le contrôleur avec les dépendances nécessaires
     * - Appelle la méthode privée handleDelete via Reflection
     * - Vérifie que la sortie contient le message de suppression réussie
     */
    public function testHandleDeleteSuccess(): void
    {
        $this->preparePost([
            'delete_id'      => 1,
            'confirm_delete' => 1,
        ]);

        $model = $this->createMock(StatModel::class);
        $model->method('delete')->with(1)->willReturn(true);

        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturn("<div role=\"alert\">Statistique supprimée !</div>");

        $controller = $this->getMockBuilder(StatController::class)
            ->setConstructorArgs([
                $renderer,
                $this->createMock(LoggerInterface::class),
                $this->createMock(ErrorPresenterInterface::class),
                new SessionManager(),
                $model,
            ])
            ->getMock();

        $ref = new \ReflectionMethod($controller, 'handleDelete');
        $ref->setAccessible(true);

        ob_start();
        $ref->invoke($controller);
        $output = ob_get_clean();

        $this->assertNotFalse($output);
        $this->assertStringContainsString("<div role=\"alert\">Statistique supprimée !</div>", $output);

    }

    /**
     * Exécute le test de processDelete avec un scénario donné.
     *
     * @param bool   $deletionSuccess   Résultat attendu de $model->delete().
     * @param string|null $expectedOutput Chaîne attendue dans le rendu HTML (ou null si showDeleteSelectForm() est attendu).
     * @param bool   $expectFallback   Si true, attend un appel à showDeleteSelectForm().
     */
    protected function assertProcessDeleteOutcome(bool $deletionSuccess, ?string $expectedOutput = null, bool $expectFallback = false): void
    {
        $model = $this->createMock(StatModel::class);
        $model->method('delete')->with(1)->willReturn($deletionSuccess);

        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')->willReturn($expectedOutput ?? '');

        $controllerBuilder = $this->getMockBuilder(StatController::class)
            ->setConstructorArgs([
                $renderer,
                $this->createMock(LoggerInterface::class),
                $this->createMock(ErrorPresenterInterface::class),
                new SessionManager(),
                $model,
            ]);

        if ($expectFallback) {
            $controllerBuilder->onlyMethods(['showDeleteSelectForm']);
        }

        $controller = $controllerBuilder->getMock();

        if ($expectFallback) {
            $controller->expects($this->once())->method('showDeleteSelectForm');
        }

        $ref = new \ReflectionMethod($controller, 'processDelete');
        $ref->setAccessible(true);

        ob_start();
        $ref->invoke($controller, 1);
        $output = ob_get_clean();

        if (! $expectFallback && $expectedOutput !== null) {
            $this->assertStringContainsString($expectedOutput, (string) $output);
        }
    }

    /**
     * Vérifie que l'appel à assertProcessDeleteOutcome produit un résultat conforme
     * en cas de suppression réussie du moyen d'obtention.
     *
     * Ce test attend :
     * - Un retour HTML contenant un message de succès
     * - Une suppression réussie simulée
     */
    public function testProcessDeleteSuccess(): void
    {
        $this->assertProcessDeleteOutcome(true, "<div role=\"alert\">Statistique supprimée !</div>");
    }

    /**
     * Vérifie que l'appel à assertProcessDeleteOutcome se comporte correctement
     * en cas d'échec de suppression de la statistique.
     *
     * Ce test attend :
     * - Aucun message HTML de succès
     * - Le déclenchement du mécanisme de repli (fallback) si la suppression échoue
     */
    public function testProcessDeleteFailure(): void
    {
        $this->assertProcessDeleteOutcome(false, null, expectFallback: true);
    }

    /**
     * Teste la méthode showDeleteConfirmForm pour les cas succès et échec.
     *
     * @param int $id ID à tester
     * @param array<string,int|string>|null $record Données à retourner depuis le modèle (null si non trouvé)
     * @param bool $expectFallback Indique si showDeleteSelectForm() doit être appelée
     * @param string|null $expectedRender Résultat attendu du renderer si succès
     */
    protected function assertShowDeleteConfirmFormOutcome(
        int $id,
        ?array $record,
        bool $expectFallback = false,
        ?string $expectedRender = null
    ): void {
        $model = $this->createMock(StatModel::class);
        $model->method('get')->with($id)->willReturn($record);

        $renderer = $this->createMock(Renderer::class);

        if (! $expectFallback && $expectedRender !== null) {
            $renderer->expects($this->once())
                ->method('render')
                ->with(
                    'stats/delete-stat-confirm',
                    $this->callback(function ($data) use ($record, $id) {
                        TestCase::assertIsArray($data);
                        TestCase::assertArrayHasKey('stat', $data);
                        TestCase::assertArrayHasKey('id', $data);
                        TestCase::assertArrayHasKey('errors', $data);
                        return $data['stat'] === $record && $data['id'] === $id;
                    })
                )
                ->willReturn($expectedRender);
        }

        $builder = $this->getMockBuilder(StatController::class)
            ->setConstructorArgs([
                $renderer,
                $this->createMock(LoggerInterface::class),
                $this->createMock(ErrorPresenterInterface::class),
                new SessionManager(),
                $model,
            ]);

        $methods = [];
        if ($expectFallback) {
            $methods[] = 'showDeleteSelectForm';
        } else {
            $methods[] = 'renderDefault';
        }

        $builder->onlyMethods($methods);
        $controller = $builder->getMock();

        $controller->expects($this->once())->method($expectFallback ? 'showDeleteSelectForm' : 'renderDefault');

        $ref = new \ReflectionMethod($controller, 'showDeleteConfirmForm');
        $ref->setAccessible(true);
        $ref->invoke($controller, $id);
    }

    /**
     * Vérifie que le formulaire de confirmation de suppression n'est pas affiché
     * lorsque la statistique recherchée est introuvable.
     *
     * Ce test :
     * - Utilise un identifiant invalide (99) sans enregistrement associé
     * - Attend que le système déclenche une stratégie de repli (fallback)
     */
    public function testShowDeleteConfirmFormNotFound(): void
    {
        $this->assertShowDeleteConfirmFormOutcome(id: 99, record: null, expectFallback: true);
    }

    /**
     * Vérifie que le formulaire de confirmation de suppression est bien affiché
     * lorsque la statistique existe.
     *
     * Ce test :
     * - Utilise un identifiant valide et un enregistrement associé
     * - Vérifie que le rendu correspond à la vue de confirmation attendue
     * - Ne déclenche pas de fallback
     */
    public function testShowDeleteConfirmFormDisplaysConfirmation(): void
    {
        $this->assertShowDeleteConfirmFormOutcome(
            id: 1,
            record: ['id_stat' => 1, 'name' => 'obt1'],
            expectFallback: false,
            expectedRender: 'confirm'
        );
    }

    /**
     * Vérifie que la méthode de route retournée par le contrôleur est correcte.
     *
     * @param string $methodName Nom de la méthode à tester (ex: 'getAddRoute').
     * @param string $expectedRoute Nom de la route attendue (ex: 'add-obtaining').
     */
    protected function assertRouteMethodReturns(string $methodName, string $expectedRoute): void
    {
        $controller = $this->getController();
        $ref        = new \ReflectionMethod($controller, $methodName);
        $ref->setAccessible(true);

        $this->assertSame($expectedRoute, $ref->invoke($controller));
    }

    /**
     * Vérifie que la méthode getAddRoute retourne bien l'identifiant de la route "add-stat".
     *
     * Ce test :
     * - Appelle la méthode getAddRoute
     * - Vérifie que le nom de la route retournée est correct
     */
    public function testGetAddRoute(): void
    {
        $this->assertRouteMethodReturns('getAddRoute', 'add-stat');
    }

    /**
     * Vérifie que la méthode getEditRoute retourne bien l'identifiant de la route "edit-stat".
     *
     * Ce test :
     * - Appelle la méthode getEditRoute
     * - Vérifie que le nom de la route retournée est correct
     */
    public function testGetEditRoute(): void
    {
        $this->assertRouteMethodReturns('getEditRoute', 'edit-stat');
    }

    /**
     * Vérifie que la méthode getDeleteRoute retourne bien l'identifiant de la route "delete-stat".
     *
     * Ce test :
     * - Appelle la méthode getDeleteRoute
     * - Vérifie que le nom de la route retournée est correct
     */
    public function testGetDeleteRoute(): void
    {
        $this->assertRouteMethodReturns('getDeleteRoute', 'delete-stat');
    }

    /**
     * Construit un mock de contrôleur pour showList avec configuration selon le scénario.
     */
    private function buildControllerForShowList(
        StatModel $model,
        ?Renderer $renderer = null,
        bool $expectRenderDefault = false,
        ? \Throwable $expectHandleException = null
    ) : StatController {
        $methods = [];
        if ($expectRenderDefault) {
            $methods[] = 'renderDefault';
        }
        if ($expectHandleException) {
            $methods[] = 'handleException';
        }

        $controllerBuilder = $this->getMockBuilder(StatController::class)
            ->setConstructorArgs([
                $renderer ?? new Renderer($this->viewPath),
                $this->createMock(LoggerInterface::class),
                $this->createMock(ErrorPresenterInterface::class),
                new SessionManager(),
                $model,
            ]);

        if ($methods) {
            $controllerBuilder->onlyMethods($methods);
        }

        $controller = $controllerBuilder->getMock();

        if ($expectRenderDefault) {
            $controller->expects($this->once())->method('renderDefault');
        }
        if ($expectHandleException) {
            $controller->expects($this->once())
                ->method('handleException')
                ->with($expectHandleException);
        }

        return $controller;
    }

    /**
     * Utilitaire pour invoquer une méthode protégée ou privée.
     */
    private function invokeProtectedMethod(object $object, string $methodName, mixed ...$args): mixed
    {
        $ref = new \ReflectionMethod($object, $methodName);
        $ref->setAccessible(true);
        return $ref->invoke($object, ...$args);
    }

    /**
     * Vérifie que la méthode showList du contrôleur obtient et affiche correctement
     * la liste des statistiques.
     *
     * Ce test :
     * - Mock le modèle StatModel pour retourner une liste prédéfinie
     * - Mock le Renderer pour capturer le rendu avec les bonnes données
     * - Assure que le rendu contient bien un élément avec le nom attendu
     */
    public function testShowList(): void
    {
        $model = $this->createMock(StatModel::class);
        $model->method('getAll')->willReturn([
            ['id_stat' => 1, 'name' => 'obt1'],
        ]);

        $renderer = $this->createMock(Renderer::class);
        $renderer->expects($this->once())
            ->method('render')
            ->with(
                'stats/stats-list',
                $this->callback(function (array $data): bool {
                    if (! isset($data['stat']) || ! is_array($data['stat'])) {
                        return false; // ou lancer une exception personnalisée si besoin
                    }

                    /** @var list<array{id_stat: int, name: string}> $stat */
                    $stat = $data['stat'];

                    $this->assertCount(1, $stat);
                    $this->assertSame('obt1', $stat[0]['name']);

                    return true;
                })
            )
            ->willReturn('liste');

        $controller = $this->buildControllerForShowList($model, $renderer, expectRenderDefault: true);

        $this->invokeProtectedMethod($controller, 'showList');
    }

    /**
     * Vérifie que la méthode showList gère correctement une exception levée
     * lors de la récupération des statistiques.
     *
     * Ce test :
     * - Simule une exception provenant du modèle lors de l'appel à getAll
     * - Vérifie que le contrôleur traite l'exception via le mécanisme prévu
     */
    public function testShowListCatchesException(): void
    {
        $exception = new \RuntimeException('BOOM');

        $model = $this->createMock(StatModel::class);
        $model->method('getAll')->willThrowException($exception);

        $controller = $this->buildControllerForShowList($model, null, expectHandleException: $exception);

        $this->invokeProtectedMethod($controller, 'showList');
    }

    /**
     * Vérifie que la méthode showDeleteSelectForm() du StatController
     * transmet correctement la liste des statistiques au template de sélection.
     *
     * Ce test :
     * - Injecte une fausse liste de statistiques via getAll()
     * - Simule le rendu via un Renderer mocké avec un callback personnalisé
     * - Inspecte les données passées au template 'partials/select-item'
     * - Autorise l'appel à 'templates/default' sans perturber le test
     * - Garantit que le comportement est complet et bien typé
     */
    public function testShowDeleteSelectFormRendersStatSelection(): void
    {
        $model = $this->createMock(StatModel::class);
        $model->method('getAll')->willReturn([
            ['id_stat' => 1, 'name' => 'attaque'],
            ['id_stat' => 2, 'name' => 'défense'],
        ]);

        $renderer = $this->createMock(Renderer::class);
        $renderer->method('render')
            ->willReturnCallback(function (string $template, array $data): string {
                if ($template === 'partials/select-item') {
                    // Teste les données envoyées à ce template
                    $this->assertSame('delete-stat', $data['action']);
                    $this->assertSame('delete_id', $data['fieldName']);
                    $this->assertSame('Supprimer', $data['buttonLabel']);
                    $this->assertSame('Choisir la statistique à supprimer', $data['title']);
                    $this->assertSame('name', $data['nameField']);
                    $this->assertSame('id_stat', $data['idField']);
                    $this->assertSame([], $data['errors']);

                    /** @var list<array{id_stat: int, name: string}> $items */
                    $items = $data['items'];
                    $this->assertCount(2, $items);
                    $this->assertSame('attaque', $items[0]['name']);
                    $this->assertSame(1, $items[0]['id_stat']);
                    $this->assertSame('défense', $items[1]['name']);
                    $this->assertSame(2, $items[1]['id_stat']);

                    return '<form>HTML</form>';
                }

                if ($template === 'templates/default') {
                    return '<html>Page par défaut</html>';
                }

                $this->fail("Appel inattendu à render() avec le template : {$template}");
            });

        $controller = $this->getController($model, $renderer);
        ob_start();
        $this->invokeProtectedMethod($controller, 'showDeleteSelectForm');
        ob_end_clean();
    }

}
