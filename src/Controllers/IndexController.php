<?php
declare (strict_types = 1);

namespace GenshinTeam\Controllers;

use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
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
     * Définit la route courante pour le contrôleur.
     *
     * @param string $route
     * @return void
     */
    public function setCurrentRoute(string $route): void
    {}

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
        $this->addData('head', '<meta name="description" content="Vous trouverez sur Genshin Team des équipes créés et partagées par la communauté pour vous faciliter la vie en jeu, ainsi que des fiches détaillées pour monter vos personnages favoris ainsi que leurs armes, leurs sets de prédilection, ainsi que leur(s) builds(s).">');

        try {
            $this->addData('content', $this->renderer->render('index'));
            $this->renderDefault();
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }
}
