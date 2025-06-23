<?php
declare (strict_types = 1);

namespace GenshinTeam\Controllers;

use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorPresenterInterface;
use Psr\Log\LoggerInterface;

/**
 * Contrôleur responsable de la déconnexion de l'utilisateur.
 *
 * Ce contrôleur effectue une déconnexion sécurisée en :
 * - démarrant la session si nécessaire ;
 * - supprimant toutes les variables de session ;
 * - détruisant la session serveur ;
 * - invalidant le cookie de session ;
 * - redirigeant ensuite vers la page d'accueil.
 *
 * @package GenshinTeam\Controllers
 */
class LogoutController extends AbstractController
{
    /**
     * Gestionnaire de session utilisé pour manipuler les données de session.
     *
     * @var SessionManager
     */
    protected SessionManager $session;

    /** @phpstan-ignore-next-line property.onlyWritten */
    private LoggerInterface $logger;

    /** @phpstan-ignore-next-line property.onlyWritten */
    private ErrorPresenterInterface $errorPresenter;

    /**
     * Initialise le contrôleur de déconnexion avec ses dépendances.
     *
     * @param Renderer                $renderer        Moteur de rendu des vues.
     * @param LoggerInterface         $logger          Logger PSR-3 (non utilisé ici, fourni pour conformité d'injection).
     * @param ErrorPresenterInterface $errorPresenter  Présentateur d'erreurs (non utilisé ici).
     * @param SessionManager          $session         Gestionnaire de session.
     */
    public function __construct(
        Renderer $renderer,
        LoggerInterface $logger,
        ErrorPresenterInterface $errorPresenter,
        SessionManager $session
    ) {
        parent::__construct($renderer, $session);
        $this->session        = $session;
        $this->logger         = $logger;
        $this->errorPresenter = $errorPresenter;
    }

    /**
     * Gère la déconnexion complète de l'utilisateur.
     *
     * Cette méthode nettoie intégralement la session actuelle en :
     * - vidant les données ;
     * - supprimant le cookie de session (si activé) ;
     * - détruisant la session ;
     * puis redirige vers l'accueil.
     *
     * @return void
     */
    protected function handleRequest(): void
    {
        $this->session->start();
        $this->session->clear();

        if (ini_get('session.use_cookies')) {
            $params      = session_get_cookie_params();
            $sessionName = session_name();
            if ($sessionName !== false) {
                setcookie(
                    $sessionName,
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
        }

        $this->session->destroy();
        $this->redirect('index');
    }

    /**
     * Point d'entrée du contrôleur de déconnexion.
     *
     * Exécute le traitement de déconnexion défini dans {@see handleRequest()}.
     *
     * @return void
     */
    public function run(): void
    {
        $this->handleRequest();
    }
}
