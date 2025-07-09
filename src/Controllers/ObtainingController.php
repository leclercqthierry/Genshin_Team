<?php
declare (strict_types = 1);

namespace GenshinTeam\Controllers;

use GenshinTeam\Connexion\Database;
use GenshinTeam\Models\Obtaining;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Traits\ExceptionHandlerTrait;
use GenshinTeam\Utils\ErrorPresenterInterface;
use GenshinTeam\Validation\Validator;
use Psr\Log\LoggerInterface;

/**
 * Contrôleur chargé de la gestion des moyens d'obtention :
 * - ajout
 * - édition
 * - suppression
 * - affichage
 *
 * Utilise un modèle Obtaining pour interagir avec la BDD.
 */

class ObtainingController extends AbstractCrudController
{
    use ExceptionHandlerTrait;
    /**
     * Constructeur principal du contrôleur.
     *
     * @param Renderer $renderer Moteur de rendu de templates.
     * @param LoggerInterface $logger Logger PSR-3 pour journalisation.
     * @param ErrorPresenterInterface $errorPresenter Présentateur d'erreurs.
     * @param SessionManager $session Gestionnaire de session pour l’état utilisateur.
     * @param Obtaining|null $obtainingModel (optionnel) instance du modèle injectée pour testabilité.
     */
    public function __construct(
        Renderer $renderer,
        LoggerInterface $logger,
        ErrorPresenterInterface $errorPresenter,
        SessionManager $session,
        ?Obtaining $obtainingModel = null
    ) {
        parent::__construct(
            $renderer,
            $logger,
            $errorPresenter,
            $session,
            $obtainingModel ?: new Obtaining(Database::getInstance(), $logger)
        );

    }

    /**
     * Retourne le nom de la route pour l'ajout d'un moyen d'obtention.
     *
     * @return string La route d'ajout.
     */
    protected function getAddRoute(): string
    {
        return 'add-obtaining';
    }

    /**
     * Retourne le nom de la route pour la modification d'un moyen d'obtention.
     *
     * @return string La route de modification.
     */
    protected function getEditRoute(): string
    {
        return 'edit-obtaining';
    }

    /**
     * Retourne le nom de la route pour la suppression d'un moyen d'obtention.
     *
     * @return string La route de suppression.
     */
    protected function getDeleteRoute(): string
    {
        return 'delete-obtaining';
    }

    /**
     * Définit la route courante pour le contrôleur.
     *
     * @param string $route
     * @return void
     */
    public function setCurrentRoute(string $route): void
    {}

    /**
     * Valide le champ "moyen d'obtention".
     *
     * @param string $obtaining
     * @return Validator
     */
    private function validateObtaining(string $obtaining): Validator
    {
        $validator = new Validator();

        // Champ requis
        $validator->validateRequired('obtaining', $obtaining, "Le champ moyen d'obtention est obligatoire.");
        if ($validator->hasErrors()) {
            return $validator;
        }

        // Longueur minimale
        $validator->validateMinLength('obtaining', $obtaining, 4, " 4 caractères minimum.");
        if ($validator->hasErrors()) {
            return $validator;
        }

        // Pas de caractères spéciaux sauf % et +
        $validator->validatePattern(
            'obtaining',
            $obtaining,
            '/^[\p{L}\s]+$/u',
            "Lettres (et accent) et espaces uniquement."

        );
        if ($validator->hasErrors()) {
            return $validator;
        }

        // Unicité (si pas d'erreur précédente)
        if ($this->model->existsByName($obtaining)) {
            $validator->setError('obtaining', "Ce moyen d'obtention existe déjà.");
        }

        return $validator;
    }

    // --- AJOUT ---

    /**
     * Gère l'ajout d'un moyen d'obtention via POST.
     */
    protected function handleAdd(): void
    {
        try {
            $this->handleCrudAdd(
                'obtaining',
                function (string $v) {
                    $validator = $this->validateObtaining($v);
                    if ($validator->hasErrors()) {
                        $this->addError('obtaining', $validator->getErrors()['obtaining'] ?? 'Erreur de validation');
                        $this->setOld(['obtaining' => $v]);
                        $this->showAddForm();
                        return;
                    }
                    $this->processAdd($v);
                },
                fn() => $this->showAddForm()
            );
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * Traite l'ajout du moyen d'obtention en base de données.
     *
     * Si l'ajout réussit, affiche un message de succès.
     * Sinon, affiche une erreur et restitue le formulaire avec les valeurs précédentes.
     *
     * @param string $obtaining Le moyen d'obtention à ajouter
     *
     * @return void
     */
    private function processAdd(string $obtaining): void
    {
        $result = $this->model->add($obtaining);

        if ($result) {
            $this->addData('title', 'Succès');
            $this->addData('content', '<div role="alert">Moyen d\'obtention ajouté !</div>');
            $this->renderDefault();
        } else {
            $this->addError('global', 'Erreur lors de l\'ajout.');
            $this->setOld(['obtaining' => $obtaining]);
            $this->showAddForm();
        }
    }

    /**
     * Affiche le formulaire d'ajout.
     */
    protected function showAddForm(): void
    {
        $this->addData('title', 'Ajouter un moyen d\'obtention');
        $this->addData('content', $this->renderer->render('obtaining/add-obtaining', [
            'errors' => $this->getErrors(),
            'old'    => $this->getOld(),
            'mode'   => 'add',
            'isEdit' => false,
        ]));
        $this->addData('scripts', '
            <script src="' . BASE_URL . '/public/assets/js/arrow.js"></script>
        ');

        $this->renderDefault();
    }

    // --- EDITION ---

    /**
     * Gère la modification d'un moyen d'obtention via POST.
     *
     * Vérifie si l'identifiant est valide, si le moyen d'obtention n'est pas vide,
     * puis traite la modification ou affiche le formulaire de sélection.
     */
    protected function handleEdit(): void
    {
        try {
            $this->handleCrudEdit(
                'obtaining',
                function (int $id, string $v) {
                    $validator = $this->validateObtaining($v);
                    if ($validator->hasErrors()) {
                        $this->addError('obtaining', $validator->getErrors()['obtaining'] ?? 'Erreur de validation');
                        $this->setOld(['obtaining' => $v]);
                        $this->showEditForm($id);
                        return;
                    }
                    $this->processEdit($id, $v);
                },
                fn(int $id) => $this->showEditForm($id),
                fn()        => $this->showEditSelectForm(),
            );
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * Traite la mise à jour du moyen d'obtention spécifié.
     *
     * Tente de modifier le moyen d'obtention avec les données reçues. Affiche un message
     * de succès ou gère l’échec en affichant les erreurs et en rechargeant le formulaire.
     *
     * @param int    $id   L'identifiant du moyen d'obtention.
     * @param string $obtaining La nouvelle valeur du moyen d'obtention.
     *
     * @return void
     */
    private function processEdit(int $id, string $obtaining): void
    {
        $result = $this->model->update($id, $obtaining);

        if ($result) {
            $this->addData('title', 'Succès');
            $this->addData('content', '<div role="alert">Moyen d\'obtention modifié !</div>');
            $this->renderDefault();
        } else {
            $this->addError('global', 'Erreur lors de la modification.');
            $this->setOld(['obtaining' => $obtaining]);
            $this->showEditForm($id);
        }
    }

    /**
     * Affiche un formulaire permettant de sélectionner le moyen d'obtention à modifier.
     *
     * Récupère tous les moyens d'obtention disponibles via le modèle,
     * puis prépare et affiche un formulaire de sélection à l’aide du moteur de rendu.
     *
     * @return void
     */
    protected function showEditSelectForm(): void
    {
        $all = $this->model->getAll();

        $this->addData('title', 'Choisir le moyen d\'obtention à éditer');
        $this->addData('content', $this->renderer->render('partials/select-item', [
            'action'      => 'edit-obtaining',
            'fieldName'   => 'edit_id',
            'buttonLabel' => 'Éditer',
            'title'       => 'Choisir le moyen d\'obtention à éditer',
            'items'       => $all,           // <-- nom générique
            'nameField'   => 'name',         // <-- champ à afficher
            'idField'     => 'id_obtaining', // <-- champ ID
            'errors'      => $this->getErrors(),
        ]));
        $this->renderDefault();
    }

    /**
     * Affiche le formulaire de modification pour un moyen d'obtention donné.
     *
     * Si l'identifiant est invalide (aucun enregistrement trouvé), un message
     * d'erreur est ajouté et le formulaire de sélection est affiché à nouveau.
     * Sinon, les données existantes sont préremplies dans le formulaire.
     *
     * @param int $id L’identifiant du moyen d'obtention à éditer.
     *
     * @return void
     */
    protected function showEditForm(int $id): void
    {
        $record = $this->model->get($id);

        if (! $record) {
            $this->addError('global', 'Moyen d\'obtention introuvable.');
            $this->showEditSelectForm();
            return;
        }

        $old = [
            'obtaining' => $record['name'] ?? '',
        ];

        $this->addData('title', 'Éditer le moyen d\'obtention');
        $this->addData('content', $this->renderer->render('obtaining/add-obtaining', [
            'errors' => $this->getErrors(),
            'old'    => $old,
            'mode'   => 'edit',
            'isEdit' => true,
            'id'     => $id,
        ]));
        $this->addData('scripts', '
            <script src="' . BASE_URL . '/public/assets/js/arrow.js"></script>
        ');

        $this->renderDefault();
    }

    // --- SUPPRESSION ---

    /**
     * Gère la suppression d'un moyen d'obtention.
     *
     * Si l'ID n'est pas soumis, affiche un formulaire de sélection.
     * Si l'ID est invalide, affiche une erreur.
     * Sinon, confirme la suppression ou exécute l’opération si confirmée.
     *
     * @return void
     */
    protected function handleDelete(): void
    {
        try {
            $this->handleCrudDelete(
                fn(int $id) => $this->processDelete($id),
                fn()        => $this->showDeleteSelectForm(),
                fn(int $id) => $this->showDeleteConfirmForm($id)
            );
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * Supprime un moyen d'obtention selon l'identifiant donné.
     *
     * Affiche un message de succès ou une erreur selon le résultat.
     *
     * @param int $id L'identifiant du moyen d'obtention à supprimer.
     *
     * @return void
     */
    private function processDelete(int $id): void
    {
        $result = $this->model->delete($id);

        if ($result) {
            $this->addData('title', 'Succès');
            $this->addData('content', '<div role="alert">Moyen d\'obtention supprimé !</div>');
            $this->renderDefault();
        } else {
            $this->addError('global', 'Erreur lors de la suppression.');
            $this->showDeleteSelectForm();
        }
    }

    /**
     * Affiche un formulaire de sélection pour choisir un moyen d'obtention à supprimer.
     *
     * Récupère tous les moyens d'obtention disponibles et utilise le moteur de rendu
     * pour afficher un formulaire HTML avec liste déroulante.
     *
     * @return void
     */
    protected function showDeleteSelectForm(): void
    {
        $all = $this->model->getAll();

        $this->addData('title', 'Choisir le moyen d\'obtention à supprimer');
        $this->addData('content', $this->renderer->render('partials/select-item', [
            'action'      => 'delete-obtaining',
            'fieldName'   => 'delete_id',
            'buttonLabel' => 'Supprimer',
            'title'       => 'Choisir le moyen d\'obtention à supprimer',
            'items'       => $all,           // <-- nom générique
            'nameField'   => 'name',         // <-- champ à afficher
            'idField'     => 'id_obtaining', // <-- champ ID
            'errors'      => $this->getErrors(),
        ]));
        $this->renderDefault();
    }

    /**
     * Affiche le formulaire de confirmation de suppression pour un moyen d'obtention.
     *
     * Si l’enregistrement est introuvable, affiche un message d’erreur
     * et renvoie vers le formulaire de sélection.
     *
     * @param int $id L’identifiant du moyen d'obtention à confirmer pour suppression.
     *
     * @return void
     */
    private function showDeleteConfirmForm(int $id): void
    {
        $record = $this->model->get($id);

        if (! $record) {
            $this->addError('global', 'Moyen d\'obtention introuvable.');
            $this->showDeleteSelectForm();
            return;
        }

        $this->addData('title', 'Confirmer la suppression');
        $this->addData('content', $this->renderer->render('obtaining/delete-obtaining-confirm', [
            'obtaining' => $record,
            'id'        => $id,
            'errors'    => $this->getErrors(),
        ]));
        $this->renderDefault();
    }

    // --- LISTE ---

    /**
     * Affiche la liste complète des moyens d'obtention enregistrés.
     *
     * Récupère tous les moyens d'obtention via le modèle, prépare les données
     * pour le moteur de rendu, puis affiche la vue correspondante.
     *
     * @return void
     */
    protected function showList(): void
    {
        try {
            $all = $this->model->getAll();

            $this->addData('title', 'Liste des moyens d\'obtention');
            $this->addData('content', $this->renderer->render('obtaining/obtaining-list', [
                'obtainings' => $all,
            ]));
            $this->renderDefault();
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }
}
