<?php
declare (strict_types = 1);

namespace GenshinTeam\Controllers;

use GenshinTeam\Connexion\Database;
use GenshinTeam\Models\FarmDays;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Traits\ExceptionHandlerTrait;
use GenshinTeam\Traits\HandleFormValidation;
use GenshinTeam\Utils\ErrorPresenterInterface;
use GenshinTeam\Validation\Validator;
use Psr\Log\LoggerInterface;

/**
 * Contrôleur chargé de la gestion des jours de farm :
 * - ajout
 * - édition
 * - suppression
 * - affichage
 *
 * Utilise un modèle FarmDays pour interagir avec la BDD.
 */
class FarmDaysController extends AbstractCrudController
{

    use HandleFormValidation;
    use ExceptionHandlerTrait;
    /**
     * Constructeur principal du contrôleur.
     *
     * @param Renderer $renderer Moteur de rendu de templates.
     * @param LoggerInterface $logger Logger PSR-3 pour journalisation.
     * @param ErrorPresenterInterface $errorPresenter Présentateur d'erreurs.
     * @param SessionManager $session Gestionnaire de session pour l’état utilisateur.
     * @param FarmDays|null $farmDaysModel (optionnel) instance du modèle injectée pour testabilité.
     */
    public function __construct(
        Renderer $renderer,
        LoggerInterface $logger,
        ErrorPresenterInterface $errorPresenter,
        SessionManager $session,
        ?FarmDays $farmDaysModel = null
    ) {
        parent::__construct(
            $renderer,
            $logger,
            $errorPresenter,
            $session,
            $farmDaysModel ?: new FarmDays(Database::getInstance(), $logger)
        );

    }

    /**
     * Retourne la route permettant d’ajouter des jours de farm.
     *
     * @return string
     */
    protected function getAddRoute(): string
    {
        return 'add-farm-days';
    }

    /**
     * Retourne la route permettant de modifier des jours de farm.
     *
     * @return string
     */
    protected function getEditRoute(): string
    {
        return 'edit-farm-days';
    }

    /**
     * Retourne la route permettant de supprimer des jours de farm.
     *
     * @return string
     */
    protected function getDeleteRoute(): string
    {
        return 'delete-farm-days';
    }

    /**
     * Valide la sélection des jours de farm.
     *
     * @param array<int, string> $days
     * @return Validator
     */
    private function validateFarmDays(array $days): Validator
    {
        $validator = new Validator();

        // Vérifie qu'au moins un jour est sélectionné
        if (empty($days)) {
            $validator->validateRequired('days', null, "Veuillez sélectionner au moins un jour.");
        }

        // Vérifie que chaque jour est valide
        $validDays = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
        foreach ($days as $day) {
            if (! in_array(mb_strtolower($day), $validDays, true)) {
                $validator->validatePattern('days', $day, '/^(lundi|mardi|mercredi|jeudi|vendredi|samedi|dimanche)$/i', "Jour invalide : $day");
            }
        }

        return $validator;
    }

    /**
     * Gère l'ajout d'un ou plusieurs jours de farm via POST.
     */
    protected function handleAdd(): void
    {
        try {
            $this->handleCrudAdd(
                'days',
                function ($v) {
                    $days = is_array($v) ? array_values($v) : [];

                    // Validation métier
                    /** @var array<int, string> $days */
                    $validator = $this->validateFarmDays($days);
                    if ($validator->hasErrors()) {
                        $this->showValidationError('days', $v, $validator, 'days', fn() => $this->showAddForm());
                        return;
                    }
                    // Si la validation est OK, on traite l'ajout
                    $this->processAdd($days);
                },
                fn() => $this->showAddForm()
            );
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * Traite l'ajout des jours de farm sélectionnés en base de données.
     *
     * Si l'ajout réussit, affiche un message de succès.
     * Sinon, affiche une erreur et restitue le formulaire avec les valeurs précédentes.
     *
     * @param array<int, string> $days Liste des jours valides sélectionnés par l'utilisateur
     *
     * @return void
     */
    private function processAdd(array $days): void
    {
        $daysString = implode('/', $days);
        $result     = $this->model->add($daysString);

        if ($result) {
            $this->addData('title', 'Succès');
            $this->addData('content', '<div role="alert">Jour(s) de farm ajouté(s) !</div>');
            $this->renderDefault();
        } else {
            $this->addError('global', 'Erreur lors de l\'ajout.');
            $this->setOld(['days' => $days]);
            $this->showAddForm();
        }
    }

    /**
     * Affiche le formulaire d'ajout.
     */
    private function showAddForm(): void
    {
        $this->addData('title', 'Ajouter des jours de farm');
        $this->addData('scripts', '
            <script type="module" src="' . BASE_URL . '/public/assets/js/farm-days-validator.js"></script>
        ');

        $this->addData('content', $this->renderer->render('farm-days/add-farm-days', [
            'errors' => $this->getErrors(),
            'old'    => $this->getOld(),
            'mode'   => 'add',
            'isEdit' => false,
        ]));
        $this->renderDefault();
    }

    /**
     * Gère l’édition d’un jour de farm sélectionné.
     */
    protected function handleEdit(): void
    {
        try {
            $this->handleCrudEdit(
                'days',
                function ($id, $v) {
                    $days = is_array($v) ? array_values($v) : [];

                    // Validation métier
                    /** @var array<int, string> $days */
                    $validator = $this->validateFarmDays($days);

                    if ($validator->hasErrors()) {
                        $this->showValidationError('days', $v, $validator, 'days', fn() => $this->showEditForm(Validator::sanitizeValue($id)));
                        return;
                    }

                    // Si la validation est OK, on traite l'édition
                    $this->processEdit(
                        Validator::sanitizeValue($id),
                        $days
                    );
                },
                fn($id) => $this->showEditForm(Validator::sanitizeValue($id)),
                fn()    => $this->showEditSelectForm(),
            );
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * Traite la mise à jour des jours de farm en base.
     *
     * Concatène les jours en une chaîne normalisée, appelle le modèle pour
     * effectuer la mise à jour, puis affiche le résultat ou retourne au formulaire
     * en cas d’échec.
     *
     * @param int                $id   Identifiant de l’entrée à mettre à jour
     * @param array<int, string> $days Liste des jours sélectionnés (valide)
     *
     * @return void
     */
    private function processEdit(int $id, array $days): void
    {
        // Construction du format attendu : ex. "lundi/mercredi/vendredi"
        $daysString = implode('/', $days);

        // Tentative de mise à jour via le modèle
        $result = $this->model->update($id, $daysString);

        if ($result) {
            // Succès : feedback utilisateur
            $this->addData('title', 'Succès');
            $this->addData('content', '<div role="alert">Jour(s) de farm modifié(s) !</div>');
            $this->renderDefault();
        } else {
            // Échec : message d’erreur et restitution du formulaire
            $this->addError('global', 'Erreur lors de la modification.');
            $this->setOld(['days' => $days]);
            $this->showEditForm($id);
        }
    }

    /**
     * Affiche le formulaire de sélection d’un jour à éditer.
     */
    protected function showEditSelectForm(): void
    {
        /** @var list<array{id_farm_days: int, days: string}> $all */
        $all = $this->model->getAll();

        $this->addData('title', 'Choisir les jours à éditer');
        $this->addData('content', $this->renderer->render('partials/select-item', [
            'action'      => 'edit-farm-days',
            'fieldName'   => 'edit_id',
            'buttonLabel' => 'Éditer',
            'title'       => 'Choisir les jours de farm à éditer',
            'items'       => $all,           // <-- nom générique
            'nameField'   => 'days',         // <-- champ à afficher
            'idField'     => 'id_farm_days', // <-- champ ID
            'errors'      => $this->getErrors(),
        ]));
        $this->renderDefault();
    }

    /**
     * Affiche le formulaire d’édition pour un jour donné.
     *
     * @param int $id Identifiant du jour à éditer.
     */
    protected function showEditForm(int $id): void
    {
        /** @var array{id_farm_days: int, days: string}|null $record */
        $record = $this->model->get($id);

        if (! $record) {
            $this->addError('global', 'Jour(s) de farm introuvable(s).');
            $this->showEditSelectForm();
            return;
        }

        /** @var array{days: list<string>} $old */
        $old = [
            'days' => explode('/', $record['days']),
        ];

        $this->addData('title', 'Éditer les jours de farm');
        $this->addData('scripts', '
            <script type="module" src="' . BASE_URL . '/public/assets/js/farm-days-validator.js"></script>
        ');

        $this->addData('content', $this->renderer->render('farm-days/add-farm-days', [
            'errors' => $this->getErrors(),
            'old'    => $old,
            'mode'   => 'edit',
            'isEdit' => true,
            'id'     => $id,
        ]));
        $this->renderDefault();
    }

    /**
     * Gère la suppression d’un jour sélectionné (confirmée ou pas).
     */
    protected function handleDelete(): void
    {
        try {
            $this->handleCrudDelete(
                fn($id) => $this->processDelete(is_numeric($id) ? (int) $id : 0),
                fn()    => $this->showDeleteSelectForm(),
                fn($id) => $this->showDeleteConfirmForm(is_numeric($id) ? (int) $id : 0)
            );
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * Traite la suppression d’un jour de farm selon l’identifiant fourni.
     *
     * @param int $id L’identifiant du jour de farm à supprimer.
     * @return void
     */
    private function processDelete(int $id): void
    {
        $result = $this->model->delete($id);

        if ($result) {
            $this->addData('title', 'Succès');
            $this->addData('content', '<div role="alert">Jour(s) de farm supprimé(s) !</div>');
            $this->renderDefault();
        } else {
            $this->addError('global', 'Erreur lors de la suppression.');
            $this->showDeleteSelectForm();
        }
    }

    /**
     * Affiche le formulaire permettant de choisir un jour de farm à supprimer.
     */
    protected function showDeleteSelectForm(): void
    {
        /** @var list<array{id_farm_days: int, days: string}> $all */
        $all = $this->model->getAll();

        $this->addData('title', 'Choisir les jours à supprimer');

        $this->addData('content', $this->renderer->render('partials/select-item', [
            'action'      => 'delete-farm-days',
            'fieldName'   => 'delete_id',
            'buttonLabel' => 'Supprimer',
            'title'       => 'Choisir les jours de farm à supprimer',
            'items'       => $all,           // <-- nom générique
            'nameField'   => 'days',         // <-- champ à afficher
            'idField'     => 'id_farm_days', // <-- champ ID
            'errors'      => $this->getErrors(),
        ]));

        $this->renderDefault();
    }

    /**
     * Affiche le formulaire de confirmation avant suppression d’un jour de farm donné.
     *
     * @param int $id L’identifiant du jour à supprimer.
     */
    protected function showDeleteConfirmForm(int $id): void
    {
        /** @var array{id_farm_days: int, days: string}|null $record */
        $record = $this->model->get($id);

        if (! $record) {
            $this->addError('global', 'Jour(s) de farm introuvable(s).');
            $this->showDeleteSelectForm();
            return;
        }

        $this->addData('title', 'Confirmer la suppression');

        $this->addData('content', $this->renderer->render('farm-days/delete-farm-days-confirm', [
            'farmDay' => $record,
            'id'      => $id,
            'errors'  => $this->getErrors(),
        ]));

        $this->renderDefault();
    }

    /**
     * Affiche la liste complète des jours de farm existants.
     */
    protected function showList(): void
    {
        try {
            /** @var list<array{id_farm_days: int, days: string}> $all */
            $all = $this->model->getAll();

            $this->addData('title', 'Liste des jours de farm');
            $this->addData('scripts', '
            <script src="' . BASE_URL . '/public/assets/js/animation/list.js"></script>
        ');

            $this->addData('content', $this->renderer->render('farm-days/farm-days-list', [
                'farmDays' => $all,
            ]));
            $this->renderDefault();
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * Récupère les anciennes valeurs saisies par l'utilisateur, ou les valeurs par défaut.
     *
     * @param array<string, mixed> $defaults Tableau associatif de valeurs par défaut si aucune ancienne valeur n'est disponible.
     * @return array<string, mixed> Tableau des valeurs précédemment saisies ou des valeurs par défaut.
     */
    public function getOld(array $defaults = []): array
    {
        return parent::getOld($defaults);
    }
}
