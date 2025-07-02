<?php
declare (strict_types = 1);

namespace GenshinTeam\Controllers;

use GenshinTeam\Connexion\Database;
use GenshinTeam\Models\FarmDays;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorPresenterInterface;
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

    protected function getAddRoute(): string
    {return 'add-farm-days';}
    protected function getEditRoute(): string
    {return 'edit-farm-days';}
    protected function getDeleteRoute(): string
    {return 'delete-farm-days';}

    /**
     * Gère l'ajout d'un ou plusieurs jours de farm via POST.
     */
    protected function handleAdd(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Protection CSRF
            if (! $this->isCsrfTokenValid()) {
                $this->addError('global', "Requête invalide ! Veuillez réessayer.");
                $this->showAddForm();
                return;
            }

            // Récupération des jours postés depuis le formulaire
            $days = $this->getPostedDays();
            if ($this->isDaysEmpty($days)) {
                $this->handleAddEmptyDays($days);
                return;
            }
            $this->processAdd($days);
        } else {
            $this->showAddForm();
        }
    }

    /**
     * Récupère les jours postés depuis le formulaire.
     *
     * @return array<int, string>
     */
    private function getPostedDays(): array
    {
        $days = $_POST['days'] ?? [];
        // On filtre pour ne garder que les chaînes
        /** @var array<int, string> $days */
        return array_values(array_filter($days, 'is_string'));

    }

    /**
     * Vérifie si la liste des jours est vide.
     *
     * @param array<int, string> $days
     */
    private function isDaysEmpty(array $days): bool
    {
        return empty($days);
    }

    /**
     * Gère le cas où aucun jour de farm n’a été sélectionné par l'utilisateur.
     *
     * Affiche le formulaire avec un message d’erreur et conserve les données précédemment envoyées.
     *
     * @param array<int, string> $days Liste des jours envoyés, même si vide ou invalide
     *
     * @return void
     */
    private function handleAddEmptyDays(array $days): void
    {
        $this->addError('day', 'Veuillez sélectionner au moins un jour.');
        $this->setOld(['days' => $days]);
        $this->showAddForm();
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
        $this->addData('content', $this->renderer->render('farm-days/add-farm-days', [
            'errors' => $this->getErrors(),
            'old'    => $this->getOld(),
            'mode'   => 'add',
        ]));
        $this->renderDefault();
    }

    /**
     * Gère l’édition d’un jour de farm sélectionné.
     */
    protected function handleEdit(): void
    {
        if (! isset($_POST['edit_id'])) {
            $this->showEditSelectForm();
            return;
        }

        $id = $this->getEditId();
        if ($id === false) {
            $this->addError('global', 'ID invalide.');
            $this->showEditSelectForm();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['days'])) {
            // Protection CSRF
            // Vérifie si le token CSRF est valide avant de traiter la requête
            if (! $this->isCsrfTokenValid()) {
                $this->addError('global', "Requête invalide ! Veuillez réessayer.");
                $this->showEditSelectForm();
                return;
            }
            $days = $_POST['days'];

            /** @var array<int, string> $days */
            if ($this->isDaysEmpty($days)) {
                $this->handleEditEmptyDays($id, $days);
                return;
            }
            $this->processEdit($id, $days);
        } else {
            $this->showEditForm($id);
        }
    }

    private function getEditId(): int | false
    {
        $rawId = $_POST['edit_id'];
        return filter_var($rawId, FILTER_VALIDATE_INT);
    }

    /**
     * Gère le cas où aucun jour n’a été sélectionné lors de la modification.
     *
     * Affiche à nouveau le formulaire d’édition avec un message d’erreur
     * et conserve les valeurs précédentes saisies.
     *
     * @param int                $id   Identifiant de l’entrée à modifier
     * @param array<int, string> $days Liste des jours (possiblement vide ou incorrecte)
     *
     * @return void
     */
    private function handleEditEmptyDays(int $id, array $days): void
    {
        // Remplissage ancien et message d’erreur
        $this->addError('day', 'Veuillez sélectionner au moins un jour.');
        $this->setOld(['days' => $days]);

        // Retour au formulaire d’édition
        $this->showEditForm($id);

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
    private function showEditSelectForm(): void
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
    private function showEditForm(int $id): void
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
        $this->addData('content', $this->renderer->render('farm-days/add-farm-days', [
            'errors' => $this->getErrors(),
            'old'    => $old,
            'mode'   => 'edit',
            'id'     => $id,
        ]));
        $this->renderDefault();
    }

    /**
     * Gère la suppression d’un jour sélectionné (confirmée ou pas).
     */
    protected function handleDelete(): void
    {
        if (! isset($_POST['delete_id'])) {
            $this->showDeleteSelectForm();
            return;
        }

        $id = $this->getDeleteId();
        if ($id === false) {
            $this->addError('global', 'ID invalide.');
            $this->showDeleteSelectForm();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
            // Protection CSRF
            // Vérifie si le token CSRF est valide avant de traiter la requête
            if (! $this->isCsrfTokenValid()) {
                $this->addError('global', "Requête invalide ! Veuillez réessayer.");
                $this->showDeleteSelectForm();
                return;
            }
            $this->processDelete($id);
        } else {
            $this->showDeleteConfirmForm($id);
        }
    }

    private function getDeleteId(): int | false
    {
        $rawId = $_POST['delete_id'];
        return filter_var($rawId, FILTER_VALIDATE_INT);
    }

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
    private function showDeleteSelectForm(): void
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
    private function showDeleteConfirmForm(int $id): void
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
        /** @var list<array{id_farm_days: int, days: string}> $all */
        $all = $this->model->getAll();

        $this->addData('title', 'Liste des jours de farm');

        $this->addData('content', $this->renderer->render('farm-days/farm-days-list', [
            'farmDays' => $all,
        ]));

        $this->renderDefault();
    }
}
