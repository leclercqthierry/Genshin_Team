<?php
declare (strict_types = 1);

namespace GenshinTeam\Controllers;

use GenshinTeam\Controllers\AbstractController;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorHandler;
use GenshinTeam\Utils\ErrorPresenterInterface;
use Psr\Log\LoggerInterface;

/**
 * Contrôleur pour la gestion des erreurs 404.
 *
 * Cette classe est utilisée pour afficher une page "404 - Page non trouvée"
 * en cas de requête vers une URL inexistante.
 */
class NotFoundController extends AbstractController
{
    private LoggerInterface $logger;
    private ErrorPresenterInterface $errorPresenter;
    protected SessionManager $session;
    /**
     * Constructeur de la classe.
     *
     * @param Renderer $renderer Instance du moteur de rendu permettant d'afficher la page 404.
     * @param LoggerInterface $logger Instance du logger pour enregistrer les erreurs.
     * @param ErrorPresenterInterface $errorPresenter Instance du présentateur d'erreurs.
     */
    public function __construct(Renderer $renderer, LoggerInterface $logger, ErrorPresenterInterface $errorPresenter, SessionManager $session)
    {
        parent::__construct($renderer, $session);
        $this->logger         = $logger;
        $this->errorPresenter = $errorPresenter;
        $this->session        = $session;

    }

    /**
     * Lance la gestion de la requête d'erreur 404.
     *
     * Cette méthode sert de point d'entrée principal et appelle `handleRequest()`.
     *
     * @return void
     */
    public function run(): void
    {
        $this->handleRequest();
    }

    /**
     * Gère l'affichage de la page 404.
     *
     * Ajoute les données nécessaires à l'affichage de la page d'erreur et les rend via `Renderer`.
     *
     * @return void
     */
    protected function handleRequest(): void
    {
        try {
            // Démarrer la session si elle n'existe pas
            $this->session->start();

            // Ajout du titre de la page
            $this->addData('title', '404 - Page non trouvée');

            // Contenu rendu via le moteur de rendu
            $this->addData('content', $this->renderer->render('404'));

            // Rendu final de la page d'erreur
            $this->renderDefault();
        } catch (\Throwable $e) {
            // Utilisation de l'instance du gestionnaire d'erreurs
            $handler = new ErrorHandler($this->logger);
            $payload = $handler->handle($e);
            $this->errorPresenter->present($payload);

        }
    }
}
