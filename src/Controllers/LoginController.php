<?php
declare (strict_types = 1);

namespace GenshinTeam\Controllers;

use GenshinTeam\Controllers\AbstractController;
use GenshinTeam\Models\User;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorHandler;
use GenshinTeam\Utils\ErrorPresenterInterface;
use Psr\Log\LoggerInterface;

/**
 * Contrôleur responsable de la gestion du formulaire de connexion.
 *
 * Ce contrôleur :
 * - Affiche le formulaire de connexion ;
 * - Vérifie la validité des identifiants de l'utilisateur ;
 * - Implémente une protection CSRF ;
 * - Limite le nombre de tentatives de connexion ;
 * - Redirige l'utilisateur connecté.
 *
 * @package GenshinTeam\Controllers
 */
class LoginController extends AbstractController
{

    /** @var LoggerInterface Enregistreur PSR-3 des erreurs et activités. */
    private LoggerInterface $logger;

    /** @var ErrorPresenterInterface Gère l'affichage utilisateur des erreurs récupérées. */
    private ErrorPresenterInterface $errorPresenter;

    /** @var SessionManager Gestionnaire de session utilisateur. */
    protected SessionManager $session;

    /** @var User Modèle métier utilisé pour interagir avec les données utilisateur. */
    private User $userModel;

    /**
     * Initialise le contrôleur avec ses dépendances et configure la session.
     *
     * @param Renderer                $renderer        Moteur de rendu de vues.
     * @param LoggerInterface         $logger          Logger PSR-3.
     * @param ErrorPresenterInterface $errorPresenter  Gestionnaire de rendu d'erreurs.
     * @param SessionManager          $session         Gestionnaire de session.
     * @param User|null               $userModel       Modèle utilisateur (injectable pour les tests).
     */
    public function __construct(Renderer $renderer, LoggerInterface $logger, ErrorPresenterInterface $errorPresenter, SessionManager $session, ?User $userModel = null)
    {

        parent::__construct($renderer, $session);
        $this->logger         = $logger;
        $this->errorPresenter = $errorPresenter;
        $this->session        = $session;
        $this->userModel      = $userModel ?: new User($logger);

        // Génération d'un jeton CSRF s'il n'est pas déjà défini afin de sécuriser la session
        if (! $this->session->get('csrf_token')) {
            $this->session->set('csrf_token', bin2hex(random_bytes(32)));
        }

        // Initialisation du compteur de tentatives de connexion
        $this->session->set('login_attempts', $this->session->get('login_attempts', 0));
    }

    /**
     * Méthode publique servant à démarrer le traitement de la requête.
     * Elle appelle la méthode protégée {@see handleRequest()} qui implémente la logique spécifique.
     *
     * @return void
     */
    public function run(): void
    {
        $this->handleRequest();
    }

    /**
     * Traitement de la requête d'authentification.
     *
     * Selon la méthode HTTP de la requête, cette méthode appelle soit {@see handleLogin()} pour traiter
     * une tentative de connexion via la méthode POST, soit {@see showLoginForm()} pour afficher le formulaire de connexion.
     *
     * @return void
     */
    public function handleRequest(): void
    {
        ($_SERVER['REQUEST_METHOD'] === 'POST') ? $this->handleLogin() : $this->showLoginForm();
    }

    /**
     * Affiche le formulaire de connexion à l'utilisateur.
     *
     * Prépare les données nécessaires à la vue (titre, erreurs, ancienne saisie du pseudo et jeton CSRF)
     * et intègre également les scripts nécessaires (par exemple, pour le menu burger).
     *
     * En cas d'exception lors du rendu de la vue principale, l'erreur est enregistrée dans un fichier de logs
     * et une vue d'erreur générique est affichée à l'utilisateur.
     *
     * @return void
     */
    protected function showLoginForm(): void
    {
        // Récupère l'ancienne valeur saisie pour le pseudo, si disponible
        $old = $this->data['old'] ?? ['nickname' => ''];

        // Ajout des données à la vue : titre et messages d'erreurs
        $this->addData('title', 'Se Connecter');
        $this->addData('errors', $this->getErrors());

        // Passage du jeton CSRF, des erreurs et de la saisie précédente à la vue 'login'
        $this->addData('content', $this->renderer->render(
            'login',
            [
                'csrf_token' => $this->session->get('csrf_token'),
                'errors'     => $this->getErrors(),
                'old'        => $old,
            ]
        ));

        try {
            // Rendu final de la vue avec l'ensemble des données préparées
            $this->renderDefault();
        } catch (\Throwable $e) {
            // Utilise l'instance du gestionnaire d'erreurs pour traiter l'exception
            $handler = new ErrorHandler($this->logger);
            $payload = $handler->handle($e);
            $this->errorPresenter->present($payload);

        }
    }

    /**
     * Traite la tentative de connexion de l'utilisateur.
     *
     * Vérifie d'abord le jeton CSRF pour sécuriser la requête.
     * Récupère et nettoie la saisie utilisateur pour le pseudo et le mot de passe.
     * Vérifie que les champs ne sont pas vides et limite le nombre de tentatives de connexion.
     * Si les identifiants sont incorrects, un message d'erreur générique est affiché et le compteur de tentatives est incrémenté.
     * En cas d'identifiants corrects, la session est régénérée pour éviter la fixation de session et
     * l'utilisateur est redirigé vers la page d'accueil.
     *
     * @return void
     */
    protected function handleLogin(): void
    {
        // Vérification du jeton CSRF afin d'éviter les attaques de type Cross-Site Request Forgery
        if (! $this->isCsrfTokenValid()) {
            $this->addError('global', "Requête invalide ! Veuillez réessayer.");
            $this->showLoginForm();
            return;
        }

        // Suppression des espaces indésirables et récupération des données saisies
        $nickname = isset($_POST['nickname']) && is_string($_POST['nickname']) ? trim($_POST['nickname']) : '';
        $password = isset($_POST['password']) && is_string($_POST['password']) ? trim($_POST['password']) : '';

        // Vérification et limitation du nombre de tentatives de connexion
        if ($this->session->get('login_attempts') >= 3) {
            $this->addError('global', "Trop de tentatives échouées, veuillez réessayer plus tard.");
            $this->addData('old', $this->getOld(['nickname' => $nickname]));
            $this->showLoginForm();
            return;
        }

        // Validation de la saisie utilisateur : tous les champs doivent être renseignés
        if (empty($nickname) || empty($password)) {
            $this->addError('global', "Veuillez remplir tous les champs.");
            $this->addData('old', $this->getOld(['nickname' => $nickname]));
            $this->showLoginForm();
            return;
        }

        // Passe le logger à User
        $user = $this->userModel->getUserByNickname($nickname);

        // Vérification des identifiants avec un message d'erreur générique pour ne pas divulguer d'indice
        if ($user === null || ! isset($user['password']) || ! is_string($user['password']) || ! password_verify($password, $user['password'])) {
            $attempts = $this->session->get('login_attempts', 0);
            if (! is_int($attempts)) {
                $attempts = 0;
            }
            $this->session->set('login_attempts', $attempts + 1);

            $this->addError('global', "Pseudo ou mot de passe incorrect.");
            $this->addData('old', $this->getOld(['nickname' => $nickname]));
            $this->showLoginForm();
            return;
        }

        // Connexion réussie : régénération de l'ID de session pour prévenir la fixation de session
        session_regenerate_id(true);
        $this->session->set('user', $nickname);
        $this->session->set('id_role', $user['id_role']);
        $this->session->set('login_attempts', 0); // Réinitialisation du compteur de tentatives

        // Régénération du jeton CSRF pour sécuriser les futures requêtes
        $this->session->set('csrf_token', bin2hex(random_bytes(32)));

        // Redirection vers l'accueil après une connexion réussie
        $this->redirect('index');
    }
}
