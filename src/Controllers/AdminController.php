<?php
declare (strict_types = 1);

namespace GenshinTeam\Controllers;

use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Traits\ExceptionHandlerTrait;
use GenshinTeam\Utils\ErrorPresenterInterface;
use Psr\Log\LoggerInterface;

/**
 * Contrôleur responsable de l'interface d'administration.
 *
 * Il vérifie que l'utilisateur est authentifié et a les droits nécessaires (rôle admin),
 * puis affiche le panneau d'administration. En cas d'erreur, elle est loggée et présentée proprement.
 *
 */
class AdminController extends AbstractController
{
    use ExceptionHandlerTrait;

    /** @var SessionManager Gestionnaire de session utilisateur */
    protected SessionManager $session;

    /**
     * Initialise le contrôleur avec ses dépendances.
     *
     * @param Renderer $renderer Moteur de rendu HTML
     * @param LoggerInterface $logger Logger PSR-3
     * @param ErrorPresenterInterface $errorPresenter Gestionnaire d'affichage des erreurs
     * @param SessionManager $session Gestionnaire de session
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

        // On démarre la session explicitement
        $this->session->start();
    }

    /**
     * Point d'entrée public du contrôleur.
     * Appelle la logique privée de traitement de la requête.
     *
     * @return void
     */
    public function run(): void
    {
        $this->handleRequest();
    }

    public function setCurrentRoute(string $route): void
    {}

    /**
     * Traite la requête entrante, vérifie les autorisations, et affiche la vue appropriée.
     * En cas d'erreur, une exception est loggée et une réponse utilisateur est rendue.
     *
     * @return void
     */
    protected function handleRequest(): void
    {
        $user   = $this->session->get('user');    // Récupère l'utilisateur courant
        $idRole = $this->session->get('id_role'); // Récupère le rôle (1 = admin)

        // Vérifie que l'utilisateur est bien connecté et a un rôle d'administrateur
        if ($user === null || $idRole !== 1) {
            http_response_code(403); // Interdit l'accès

            $this->addData('title', 'Accès interdit');
            $this->addData('content', '<div role="alert">Vous n\'avez pas accès à cette page.</div>');
            try {
                $this->renderDefault();
            } catch (\Throwable $e) {
                $this->handleException($e);
            }

            return;
        }

        // Utilisateur autorisé : affiche le contenu admin
        $this->addData('title', 'Panneau d\'administration');
        try {
            $this->addData('content', $this->renderer->render('admin'));
            $this->renderDefault();
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }
}
