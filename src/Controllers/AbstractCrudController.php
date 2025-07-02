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

    // Méthodes abstraites à implémenter dans les enfants
    abstract protected function getAddRoute(): string;
    abstract protected function getEditRoute(): string;
    abstract protected function getDeleteRoute(): string;
    abstract protected function handleAdd(): void;
    abstract protected function handleEdit(): void;
    abstract protected function handleDelete(): void;
    abstract protected function showList(): void;
}
