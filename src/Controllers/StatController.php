<?php
declare (strict_types = 1);

namespace GenshinTeam\Controllers;

use GenshinTeam\Connexion\Database;
use GenshinTeam\Models\Stat;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorPresenterInterface;
use Psr\Log\LoggerInterface;

/**
 * Contrôleur chargé de la gestion des statistiques :
 * - ajout
 * - édition
 * - suppression
 * - affichage
 *
 * Utilise un modèle Stats pour interagir avec la BDD.
 */

class StatController extends AbstractCrudController
{
    /**
     * Constructeur principal du contrôleur.
     *
     * @param Renderer $renderer Moteur de rendu de templates.
     * @param LoggerInterface $logger Logger PSR-3 pour journalisation.
     * @param ErrorPresenterInterface $errorPresenter Présentateur d'erreurs.
     * @param SessionManager $session Gestionnaire de session pour l’état utilisateur.
     * @param Stat|null $statModel (optionnel) instance du modèle injectée pour testabilité.
     */
    public function __construct(
        Renderer $renderer,
        LoggerInterface $logger,
        ErrorPresenterInterface $errorPresenter,
        SessionManager $session,
        ?Stat $statModel = null
    ) {
        parent::__construct(
            $renderer,
            $logger,
            $errorPresenter,
            $session,
            $statModel ?: new Stat(Database::getInstance(), $logger)
        );

    }

    /**
     * Retourne le nom de la route pour l'ajout d'une statistique.
     *
     * @return string La route d'ajout.
     */
    protected function getAddRoute(): string
    {
        return 'add-stat';
    }

/**
 * Retourne le nom de la route pour la modification d'une statistique.
 *
 * @return string La route de modification.
 */
    protected function getEditRoute(): string
    {
        return 'edit-stat';
    }

/**
 * Retourne le nom de la route pour la suppression d'une statistique.
 *
 * @return string La route de suppression.
 */
    protected function getDeleteRoute(): string
    {
        return 'delete-stat';
    }

    // --- AJOUT ---

    /**
     * Gère l'ajout d'une statistique via POST.
     */
    protected function handleAdd(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            // Protection CSRF
            if (! $this->isCsrfTokenValid()) {
                $this->addError('global', "Requête invalide ! Veuillez réessayer.");
                $this->showAddForm();
                return;
            }
            // Récupération de la statistique postée
            $stat = $this->getPostedStat();
            if ($this->isStatEmpty($stat)) {
                $this->handleAddEmptyStat($stat);
                return;
            }
            $this->processAdd($stat);
        } else {
            $this->showAddForm();
        }
    }

    /**
     * Récupère la statistique postée depuis le formulaire.
     *
     * @return string
     */
    private function getPostedStat(): string
    {
        $stat = $_POST['stat'] ?? '';
        return is_string($stat) ? $stat : '';

    }

    /**
     * Vérifie si la statistique est vide.
     *
     * @param string $stat La statistique à vérifier
     */
    private function isStatEmpty(string $stat): bool
    {
        return empty($stat);
    }

    /**
     * Gère le cas où aucune statistique n’a été sélectionnée par l'utilisateur.
     *
     * Affiche le formulaire avec un message d’erreur et conserve les données précédemment envoyées.
     *
     * @param string $stat Statistique envoyée, même si vide ou invalide
     *
     * @return void
     */
    private function handleAddEmptyStat(string $stat): void
    {
        $this->addError('stat', 'Veuillez ajouter une statistique.');
        $this->setOld(['stat' => $stat]);
        $this->showAddForm();
    }

    /**
     * Traite l'ajout de la statistique en base de données.
     *
     * Si l'ajout réussit, affiche un message de succès.
     * Sinon, affiche une erreur et restitue le formulaire avec les valeurs précédentes.
     *
     * @param string $stat La statistique à ajouter
     *
     * @return void
     */
    private function processAdd(string $stat): void
    {
        $result = $this->model->add($stat);

        if ($result) {
            $this->addData('title', 'Succès');
            $this->addData('content', '<div role="alert">Statistique ajoutée !</div>');
            $this->renderDefault();
        } else {
            $this->addError('global', 'Erreur lors de l\'ajout.');
            $this->setOld(['stat' => $stat]);
            $this->showAddForm();
        }
    }

    /**
     * Affiche le formulaire d'ajout.
     */
    protected function showAddForm(): void
    {
        $this->addData('title', 'Ajouter une statistique');
        $this->addData('content', $this->renderer->render('stats/add-stat', [
            'errors' => $this->getErrors(),
            'old'    => $this->getOld(),
            'mode'   => 'add',
        ]));
        $this->renderDefault();
    }

    // --- EDITION ---

    /**
     * Gère le processus de modification d'une statistique.
     *
     * Cette méthode vérifie d'abord si un identifiant de statistique a été soumis
     * via POST. Si ce n'est pas le cas, elle affiche le formulaire de sélection.
     * Ensuite, elle tente de récupérer et valider l'identifiant.
     * Si une statistique est soumise via POST et est non vide, elle est traitée ;
     * sinon, le formulaire de modification est affiché ou une gestion spécifique
     * est déclenchée pour une entrée vide.
     *
     * @return void
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

        if (! empty($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stat'])) {
            // Protection CSRF
            // Vérifie si le token CSRF est valide avant de traiter la requête
            if (! $this->isCsrfTokenValid()) {
                $this->addError('global', "Requête invalide ! Veuillez réessayer.");
                $this->showEditForm($id);
                return;
            }

            // Récupération de la statistique postée
            $stat = $this->getPostedStat();
            if ($this->isStatEmpty($stat)) {
                $this->handleEditEmptyStat($id, $stat);
                return;
            }
            $this->processEdit($id, $stat);
        } else {
            $this->showEditForm($id);
        }
    }

    /**
     * Récupère l'identifiant de la statistique à modifier depuis le formulaire POST.
     *
     * Utilise FILTER_VALIDATE_INT pour s'assurer que l'identifiant est un entier valide.
     *
     * @return int|false L'identifiant validé ou false si invalide.
     */
    private function getEditId(): int | false
    {
        $rawId = $_POST['edit_id'];
        return filter_var($rawId, FILTER_VALIDATE_INT);
    }

    /**
     * Gère le cas où la statistique soumise pour modification est vide.
     *
     * Ajoute un message d'erreur, restaure les anciennes données du formulaire,
     * puis affiche à nouveau le formulaire de modification.
     *
     * @param int    $id   L'identifiant de la statistique.
     * @param string $stat La valeur vide ou invalide de la statistique soumise.
     *
     * @return void
     */
    private function handleEditEmptyStat(int $id, string $stat): void
    {
        $this->addError('stat', 'Veuillez ajouter une statistique.');
        $this->setOld(['stat' => $stat]);
        $this->showEditForm($id);
    }

    /**
     * Traite la mise à jour de la statistique spécifiée.
     *
     * Tente de modifier la statistique avec les données reçues. Affiche un message
     * de succès ou gère l’échec en affichant les erreurs et en rechargeant le formulaire.
     *
     * @param int    $id   L'identifiant de la statistique.
     * @param string $stat La nouvelle valeur de la statistique.
     *
     * @return void
     */
    private function processEdit(int $id, string $stat): void
    {
        $result = $this->model->update($id, $stat);

        if ($result) {
            $this->addData('title', 'Succès');
            $this->addData('content', '<div role="alert">Statistique modifiée !</div>');
            $this->renderDefault();
        } else {
            $this->addError('global', 'Erreur lors de la modification.');
            $this->setOld(['stat' => $stat]);
            $this->showEditForm($id);
        }
    }

    /**
     * Affiche un formulaire permettant de sélectionner la statistique à modifier.
     *
     * Récupère toutes les statistiques disponibles via le modèle,
     * puis prépare et affiche un formulaire de sélection à l’aide du moteur de rendu.
     *
     * @return void
     */
    protected function showEditSelectForm(): void
    {
        $all = $this->model->getAll();

        $this->addData('title', 'Choisir la statistique à éditer');
        $this->addData('content', $this->renderer->render('partials/select-item', [
            'action'      => 'edit-stat',
            'fieldName'   => 'edit_id',
            'buttonLabel' => 'Éditer',
            'title'       => 'Choisir la statistique à éditer',
            'items'       => $all,      // <-- nom générique
            'nameField'   => 'name',    // <-- champ à afficher
            'idField'     => 'id_stat', // <-- champ ID
            'errors'      => $this->getErrors(),
        ]));
        $this->renderDefault();
    }

    /**
     * Affiche le formulaire de modification pour une statistique donnée.
     *
     * Si l'identifiant est invalide (aucun enregistrement trouvé), un message
     * d'erreur est ajouté et le formulaire de sélection est affiché à nouveau.
     * Sinon, les données existantes sont préremplies dans le formulaire.
     *
     * @param int $id L’identifiant de la statistique à éditer.
     *
     * @return void
     */
    private function showEditForm(int $id): void
    {
        $record = $this->model->get($id);

        if (! $record) {
            $this->addError('global', 'Statistique introuvable.');
            $this->showEditSelectForm();
            return;
        }

        $old = [
            'stat' => $record['name'] ?? '',
        ];

        $this->addData('title', 'Éditer la statistique');
        $this->addData('content', $this->renderer->render('stats/add-stat', [
            'errors' => $this->getErrors(),
            'old'    => $old,
            'mode'   => 'edit',
            'id'     => $id,
        ]));
        $this->renderDefault();
    }

    // --- SUPPRESSION ---

    /**
     * Gère la suppression d'une statistique.
     *
     * Si l'ID n'est pas soumis, affiche un formulaire de sélection.
     * Si l'ID est invalide, affiche une erreur.
     * Sinon, confirme la suppression ou exécute l’opération si confirmée.
     *
     * @return void
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

        if (! empty($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {

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

    /**
     * Récupère l'identifiant de la statistique à supprimer depuis $_POST.
     *
     * @return int|false L'identifiant valide ou false s'il est invalide.
     */
    private function getDeleteId(): int | false
    {
        $rawId = $_POST['delete_id'];
        return filter_var($rawId, FILTER_VALIDATE_INT);
    }

    /**
     * Supprime une statistique selon l'identifiant donné.
     *
     * Affiche un message de succès ou une erreur selon le résultat.
     *
     * @param int $id L'identifiant de la statistique à supprimer.
     *
     * @return void
     */
    private function processDelete(int $id): void
    {
        $result = $this->model->delete($id);

        if ($result) {
            $this->addData('title', 'Succès');
            $this->addData('content', '<div role="alert">Statistique supprimée !</div>');
            $this->renderDefault();
        } else {
            $this->addError('global', 'Erreur lors de la suppression.');
            $this->showDeleteSelectForm();
        }
    }

    /**
     * Affiche un formulaire de sélection pour choisir une statistique à supprimer.
     *
     * Récupère toutes les statistiques disponibles et utilise le moteur de rendu
     * pour afficher un formulaire HTML avec liste déroulante.
     *
     * @return void
     */
    protected function showDeleteSelectForm(): void
    {
        $all = $this->model->getAll();

        $this->addData('title', 'Choisir la statistique à supprimer');
        $this->addData('content', $this->renderer->render('partials/select-item', [
            'action'      => 'delete-stat',
            'fieldName'   => 'delete_id',
            'buttonLabel' => 'Supprimer',
            'title'       => 'Choisir la statistique à supprimer',
            'items'       => $all,      // <-- nom générique
            'nameField'   => 'name',    // <-- champ à afficher
            'idField'     => 'id_stat', // <-- champ ID
            'errors'      => $this->getErrors(),
        ]));
        $this->renderDefault();
    }

    /**
     * Affiche le formulaire de confirmation de suppression pour une statistique.
     *
     * Si l’enregistrement est introuvable, affiche un message d’erreur
     * et renvoie vers le formulaire de sélection.
     *
     * @param int $id L’identifiant de la statistique à confirmer pour suppression.
     *
     * @return void
     */
    private function showDeleteConfirmForm(int $id): void
    {
        $record = $this->model->get($id);

        if (! $record) {
            $this->addError('global', 'Statistique introuvable.');
            $this->showDeleteSelectForm();
            return;
        }

        $this->addData('title', 'Confirmer la suppression');
        $this->addData('content', $this->renderer->render('stats/delete-stat-confirm', [
            'stat'   => $record,
            'id'     => $id,
            'errors' => $this->getErrors(),
        ]));
        $this->renderDefault();
    }

    // --- LISTE ---

    /**
     * Affiche la liste complète des statistiques enregistrées.
     *
     * Récupère toutes les statistiques via le modèle, prépare les données
     * pour le moteur de rendu, puis affiche la vue correspondante.
     *
     * @return void
     */
    protected function showList(): void
    {
        $all = $this->model->getAll();

        $this->addData('title', 'Liste des statistiques');
        $this->addData('content', $this->renderer->render('stats/stats-list', [
            'stats' => $all,
        ]));
        $this->renderDefault();
    }
}
