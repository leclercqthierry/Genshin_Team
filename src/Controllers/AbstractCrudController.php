<?php
declare (strict_types = 1);

namespace GenshinTeam\Controllers;

use GenshinTeam\Models\CrudModelInterface;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorPresenterInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractCrudController extends AbstractController
{

    /** @var LoggerInterface Logger pour journaliser les erreurs ou événements métier.
     */
    protected LoggerInterface $logger;

    /** @var ErrorPresenterInterface Utilitaire pour formater les erreurs affichées.
     */
    protected ErrorPresenterInterface $errorPresenter;
    protected CrudModelInterface $model;
    protected string $currentRoute = '';

    public function __construct(
        Renderer $renderer,
        LoggerInterface $logger,
        ErrorPresenterInterface $errorPresenter,
        SessionManager $session,
        CrudModelInterface $model
    ) {
        parent::__construct($renderer, $session);
        $this->logger         = $logger;
        $this->errorPresenter = $errorPresenter;
        $this->model          = $model;
    }

    /**
     * Déclare le nom de route courante.
     *
     * @param string $route Route courante appelée.
     */
    public function setCurrentRoute(string $route): void
    {
        $this->currentRoute = $route;
    }

    /**
     * Dispatch vers l'action correspondant à la route.
     */
    public function handleRequest(): void
    {
        if (! $this->checkAdminAccess()) {
            return;
        }

        switch ($this->currentRoute) {
            case $this->getAddRoute():
                $this->handleAdd();
                break;
            case $this->getEditRoute():
                $this->handleEdit();
                break;
            case $this->getDeleteRoute():
                $this->handleDelete();
                break;
            default:
                $this->showList();
        }
    }

    /**
     * Point d’entrée du contrôleur — méthode publique appelée par le routeur.
     */
    public function run(): void
    {
        $this->handleRequest();
    }

    /**
     * Vérifie le token CSRF et exécute une fonction de fallback en cas d'échec.
     *
     * @param callable      $onError Fonction à appeler en cas d'échec.
     * @param int|null      $id      Paramètre optionnel à transmettre.
     * @return bool
     */
    private function verifyCsrfOrShowError(callable $onError, ?int $id = null): bool
    {
        if (! $this->isCsrfTokenValid()) {
            $this->addError('global', "Requête invalide ! Veuillez réessayer.");
            $id !== null ? $onError($id) : $onError();
            return false;
        }
        return true;
    }

    /**
     * Gère l'ajout d'un champ dans un formulaire CRUD.
     *
     * Cette méthode est utilisée pour traiter les requêtes POST lors de l'ajout d'un champ.
     * Elle vérifie si le champ est vide, valide le token CSRF, et appelle les fonctions de traitement
     * appropriées pour ajouter le champ ou afficher le formulaire.
     *
     * @param string   $fieldName  Le nom du champ à ajouter.
     * @param callable $processAdd Fonction qui traite l'ajout du champ.
     * @param callable $showForm   Fonction qui affiche le formulaire d'ajout.
     *
     * @return void
     */
    protected function handleCrudAdd(string $fieldName, callable $processAdd, callable $showForm)
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            if (! $this->verifyCsrfOrShowError($showForm)) {
                return;
            }

            $value = $_POST[$fieldName] ?? '';
            try {
                $processAdd($value);
            } catch (\Throwable $e) {
                $this->handleException($e);
            }

        } else {
            $showForm();
        }
    }

    /**
     * Gère le processus de modification d'un champ dans un formulaire CRUD.
     *
     * @param string   $fieldName  Le nom du champ à modifier.
     * @param callable $processEdit Fonction qui traite la modification du champ.
     * @param callable $showEditForm Fonction qui affiche le formulaire de modification.
     *
     * @return void
     */
    protected function handleCrudEdit(
        string $fieldName,
        callable $processEdit,
        callable $showEditForm,
        callable $showEditSelectForm
    ): void {
        if (! isset($_POST['edit_id'])) {
            $showEditSelectForm();
            return;
        }

        /** @var int|false $id */
        $id = filter_var($_POST['edit_id'], FILTER_VALIDATE_INT);

        if ($id === false) {
            $this->addError('global', 'ID invalide.');
            $showEditSelectForm();
            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST[$fieldName])) {
            if (! $this->verifyCsrfOrShowError($showEditForm, $id)) {
                return;
            }

            $value = $_POST[$fieldName];
            try {
                $processEdit($id, $value);
            } catch (\Throwable $e) {
                $this->handleException($e);
            }

        } else {
            $showEditForm($id);
        }
    }

    /**
     * Gère la suppression d'un champ dans un formulaire CRUD.
     *
     * @param callable $processDelete Fonction qui traite la suppression.
     * @param callable $showDeleteSelectForm Fonction qui affiche le formulaire de sélection.
     * @param callable $showDeleteConfirmForm Fonction qui affiche le formulaire de confirmation.
     *
     * @return void
     */
    protected function handleCrudDelete(
        callable $processDelete,
        callable $showDeleteSelectForm,
        callable $showDeleteConfirmForm
    ): void {
        if (! isset($_POST['delete_id'])) {
            $showDeleteSelectForm();
            return;
        }

        /** @var int|false $id */
        $id = filter_var($_POST['delete_id'], FILTER_VALIDATE_INT);

        if ($id === false) {
            $this->addError('global', 'ID invalide.');
            $showDeleteSelectForm();
            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['confirm_delete'])) {
            /** @var int|null $id */
            if (! $this->verifyCsrfOrShowError($showDeleteSelectForm, $id)) {
                return;
            }

            try {
                $processDelete($id);
            } catch (\Throwable $e) {
                $this->handleException($e);
            }

        } else {
            $showDeleteConfirmForm($id);
        }
    }

    // Méthodes abstraites à implémenter dans les enfants
    abstract protected function getAddRoute(): string;
    abstract protected function getEditRoute(): string;
    abstract protected function getDeleteRoute(): string;
    abstract protected function handleAdd(): void;
    abstract protected function handleEdit(): void;
    abstract protected function handleDelete(): void;
    abstract protected function showList(): void;
}
