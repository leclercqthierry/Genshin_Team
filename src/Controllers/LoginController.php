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
        $csrf_token = $this->session->get('csrf_token');
        $this->addData('content', $this->renderer->render(
            'login',
            [
                'csrf_token' => $csrf_token,
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
     * Cette méthode suit plusieurs étapes :
     * - Vérifie le jeton CSRF
     * - Récupère les champs du formulaire
     * - Valide le nombre de tentatives échouées
     * - Vérifie que les champs ne sont pas vides
     * - Tente l’authentification
     * - Connecte l'utilisateur ou affiche une erreur
     *
     * @return void
     */
    protected function handleLogin(): void
    {
        // Sécurité : validation du token CSRF
        if (! $this->isCsrfTokenValid()) {
            $this->handleInvalidCsrf();
            return;
        }

        // Champs saisis par l'utilisateur
        [$nickname, $password] = $this->getLoginFields();

        // Trop de tentatives ? On bloque
        if ($this->hasTooManyAttempts()) {
            $this->handleTooManyAttempts($nickname);
            return;
        }

        // Champs requis vides
        if ($this->hasEmptyFields($nickname, $password)) {
            $this->handleEmptyFields($nickname);
            return;
        }

        // Recherche de l’utilisateur
        $user = $this->userModel->getUserByNickname($nickname);

        // Vérifie les identifiants
        if (! $this->isValidUser($user, $password)) {
            $this->handleInvalidCredentials($nickname);
            return;
        }

        // Authentification réussie
        /** @var array{password: string, id_role: int} $user */
        $this->loginUser($user, $nickname);
    }

    /**
     * Récupère et nettoie les champs du formulaire de connexion.
     *
     * @return array{0: string, 1: string}
     */
    private function getLoginFields(): array
    {
        $nickname = isset($_POST['nickname']) && is_string($_POST['nickname']) ? trim($_POST['nickname']) : '';
        $password = isset($_POST['password']) && is_string($_POST['password']) ? trim($_POST['password']) : '';
        return [$nickname, $password];
    }

    /**
     * Vérifie si le nombre de tentatives de connexion est dépassé.
     */
    private function hasTooManyAttempts(): bool
    {
        return $this->session->get('login_attempts') >= 3;
    }

    /**
     * Vérifie si les champs sont vides.
     */
    private function hasEmptyFields(string $nickname, string $password): bool
    {
        return empty($nickname) || empty($password);
    }

    /**
     * Vérifie la validité de l'utilisateur et du mot de passe.
     *
     * @param array<string, mixed>|null $user
     */
    private function isValidUser(?array $user, string $password): bool
    {
        return $user !== null
        && isset($user['password'])
        && is_string($user['password'])
        && password_verify($password, $user['password']);
    }

    /**
     * Gère le cas d'un jeton CSRF invalide.
     */
    private function handleInvalidCsrf(): void
    {
        $this->addError('global', "Requête invalide ! Veuillez réessayer.");
        $this->showLoginForm();
    }

    /**
     * Gère le cas où trop de tentatives ont été effectuées.
     */
    private function handleTooManyAttempts(string $nickname): void
    {
        $this->addError('global', "Trop de tentatives échouées, veuillez réessayer plus tard.");
        $this->addData('old', $this->getOld(['nickname' => $nickname]));
        $this->showLoginForm();
    }

    /**
     * Gère le cas où des champs sont vides.
     */
    private function handleEmptyFields(string $nickname): void
    {
        $this->addError('global', "Veuillez remplir tous les champs.");
        $this->addData('old', $this->getOld(['nickname' => $nickname]));
        $this->showLoginForm();
    }

    /**
     * Gère le cas où les identifiants sont invalides.
     */
    private function handleInvalidCredentials(string $nickname): void
    {
        $attempts = $this->session->get('login_attempts', 0);

        /** @var int $attempts */
        $this->session->set('login_attempts', $attempts + 1);

        $this->addError('global', "Pseudo ou mot de passe incorrect.");
        $this->addData('old', $this->getOld(['nickname' => $nickname]));
        $this->showLoginForm();
    }

    /**
     * Connecte l'utilisateur et effectue la redirection.
     *
     * @param array{password: string, id_role: int, ...} $user
     */
    private function loginUser(array $user, string $nickname): void
    {
        session_regenerate_id(true);
        $this->session->set('user', $nickname);
        $this->session->set('id_role', $user['id_role']);
        $this->session->set('login_attempts', 0);
        $this->session->set('csrf_token', bin2hex(random_bytes(32)));
        $this->redirect('index');
    }
}
