<?php
declare (strict_types = 1);

namespace GenshinTeam\Controllers;

use GenshinTeam\Controllers\AbstractController;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorPresenterInterface;
use Psr\Log\LoggerInterface;

/**
 * Class LogoutController
 *
 * Ce contrôleur gère la déconnexion de l'utilisateur de manière sécurisée.
 * Il réalise un nettoyage complet de la session en démarrant la session (si nécessaire),
 * en vidant toutes les variables de session, en supprimant le cookie de session, et en détruisant la session.
 * Enfin, il redirige l'utilisateur vers la page d'accueil.
 *
 * @package GenshinTeam\Controllers
 */
class LogoutController extends AbstractController
{
    protected SessionManager $session;

    public function __construct(Renderer $renderer, LoggerInterface $logger, ErrorPresenterInterface $errorPresenter, SessionManager $session)
    {
        parent::__construct($renderer, $session);
        $this->session = $session;
    }

    /**
     * Traite la déconnexion en nettoyant complètement la session.
     *
     * Démarre la session si elle n'est pas déjà active, efface les variables de session,
     * supprime le cookie de session (si les cookies de session sont activés) et détruit la session.
     * Puis redirige l'utilisateur vers la page d'accueil pour finaliser la déconnexion.
     *
     * @return void
     */
    protected function handleRequest(): void
    {

        // Démarrer la session si nécessaire
        $this->session->start();

        // Vider toutes les variables de session
        $this->session->clear();

        // Supprimer le cookie de session s'il existe, afin d'empêcher la réutilisation de l'ancienne session
        if (ini_get("session.use_cookies")) {
            $params      = session_get_cookie_params();
            $sessionName = session_name();
            if ($sessionName !== false) {
                setcookie(
                    $sessionName,   // Nom de la session à supprimer
                    '',             // Contenu vide pour le cookie
                    time() - 42000, // Date d'expiration dans le passé pour invalider le cookie
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
        }

        // Détruire la session afin de supprimer toutes les traces de l'utilisateur
        $this->session->destroy();

        // Rediriger l'utilisateur vers la page d'accueil après la déconnexion
        $this->redirect('index');
    }

    /**
     * Méthode publique pour démarrer le processus de déconnexion.
     *
     * Cette méthode sert de point d'entrée pour le contrôleur et appelle la méthode {@see handleRequest()}
     * qui implémente la logique de déconnexion complète.
     *
     * @return void
     */
    public function run(): void
    {
        $this->handleRequest();
    }
}
