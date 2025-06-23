<?php
declare (strict_types = 1);

namespace GenshinTeam\Controllers;

use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorHandler;
use GenshinTeam\Utils\ErrorPresenterInterface;
use Psr\Log\LoggerInterface;

/**
 * Contrôleur de la page d'accueil (Index).
 *
 * Ce contrôleur affiche la vue principale de l'application.
 * Il récupère un éventuel nom d'utilisateur depuis la session pour personnaliser l'accueil,
 * et gère les erreurs en les passant au gestionnaire de présentation des erreurs.
 *
 * @package GenshinTeam\Controllers
 */
class IndexController extends AbstractController
{
    /**
     * Logger PSR-3 utilisé pour enregistrer les erreurs ou informations système.
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Présentateur d'erreurs destiné à afficher les erreurs à l'utilisateur final.
     *
     * @var ErrorPresenterInterface
     */
    private ErrorPresenterInterface $errorPresenter;

    /**
     * Gestionnaire de session actif.
     *
     * Redéclaré ici en `protected` (hérité du parent) pour manipulations spécifiques.
     *
     * @var SessionManager
     */
    protected SessionManager $session;

    /**
     * Constructeur du contrôleur.
     *
     * Initialise les dépendances principales et démarre la session.
     *
     * @param Renderer                $renderer         Moteur de rendu des vues.
     * @param LoggerInterface         $logger           Logger PSR-3 pour tracer les erreurs.
     * @param ErrorPresenterInterface $errorPresenter   Composant de présentation des erreurs.
     * @param SessionManager          $session          Gestionnaire de session HTTP.
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
        $this->session->start();
    }

    /**
     * Point d’entrée principal du contrôleur.
     *
     * Déclenche la gestion de la requête utilisateur.
     *
     * @return void
     */
    public function run(): void
    {
        $this->handleRequest();
    }

    /**
     * Traite la requête principale pour la page d'accueil.
     *
     * Cette méthode :
     * - récupère l'utilisateur depuis la session (si existant),
     * - génère un titre personnalisé,
     * - rend la vue "index",
     * - et, en cas d'erreur, transmet les données d'erreur à l'ErrorPresenter.
     *
     * @return void
     */
    protected function handleRequest(): void
    {
        $user = $this->session->get('user');

        $this->addData(
            'title',
            is_string($user)
            ? 'Bienvenue sur Genshin Team, ' . htmlspecialchars($user)
            : 'Bienvenue sur Genshin Team'
        );

        try {
            $this->addData('content', $this->renderer->render('index'));
            $this->renderDefault();
        } catch (\Throwable $e) {
            $handler = new ErrorHandler($this->logger);
            $payload = $handler->handle($e);
            $this->errorPresenter->present($payload);
        }
    }
}
