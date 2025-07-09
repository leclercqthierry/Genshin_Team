<?php
declare (strict_types = 1);

namespace GenshinTeam\Controllers;

use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Traits\ExceptionHandlerTrait;
use GenshinTeam\Utils\ErrorPresenterInterface;
use Psr\Log\LoggerInterface;

/**
 * Contrôleur chargé d'afficher la page 404 - Page non trouvée.
 *
 * Ce contrôleur est invoqué lorsqu'une requête cible une route inexistante.
 * Il prépare les données nécessaires à l'affichage de la page 404, rend la vue correspondante,
 * et assure un fallback en cas d'exception lors du rendu, en affichant une erreur formatée.
 *
 * @package GenshinTeam\Controllers
 */
class NotFoundController extends AbstractController
{
    use ExceptionHandlerTrait;

    /**
     * Gestionnaire de session, redéclaré ici pour manipulation locale.
     *
     * @var SessionManager
     */
    protected SessionManager $session;

    /**
     * Constructeur du contrôleur 404.
     *
     * @param Renderer                $renderer        Moteur de rendu de vues.
     * @param LoggerInterface         $logger          Logger PSR-3.
     * @param ErrorPresenterInterface $errorPresenter  Présentateur des erreurs.
     * @param SessionManager          $session         Gestionnaire de session.
     */
    public function __construct(
        Renderer $renderer,
        LoggerInterface $logger,
        ErrorPresenterInterface $errorPresenter,
        SessionManager $session
    ) {
        parent::__construct($renderer, $session);
        $this->logger         = $logger;
        $this->errorPresenter = $errorPresenter;
        $this->session        = $session;
    }

    /**
     * Point d'entrée du contrôleur 404.
     *
     * Déclenche le rendu de la page "Page non trouvée".
     *
     * @return void
     */
    public function run(): void
    {
        $this->handleRequest();
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
     * Génère et affiche la page 404.
     *
     * Récupère les données nécessaires, démarre la session si besoin,
     * et tente de rendre la vue. Si une erreur survient, elle est traitée
     * via `ErrorHandler` puis affichée grâce à `ErrorPresenterInterface`.
     *
     * @return void
     */
    protected function handleRequest(): void
    {
        $this->session->start();
        $this->addData('title', '404 - Page non trouvée');

        try {
            $this->addData('content', $this->renderer->render('404'));
            $this->renderDefault();
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }
}
