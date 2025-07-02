<?php
declare (strict_types = 1);

namespace GenshinTeam\Controllers;

use GenshinTeam\Controllers\AbstractController;
use GenshinTeam\Models\User;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorHandler;
use GenshinTeam\Utils\ErrorPresenterInterface;
use GenshinTeam\Validation\Validator;
use Psr\Log\LoggerInterface;

/**
 * Class RegisterController
 *
 * Ce contrôleur gère l'inscription des utilisateurs.
 * Il initialise la session, génère un jeton CSRF pour sécuriser le formulaire d'inscription,
 * et permet de valider et de créer un nouvel utilisateur en base de données, via le modèle User.
 * En cas d'erreur, le formulaire est réaffiché avec les erreurs et les valeurs précédemment saisies.
 *
 * @package GenshinTeam\Controllers
 */
class RegisterController extends AbstractController
{
    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /** @var ErrorPresenterInterface */
    private ErrorPresenterInterface $errorPresenter;

    /** @var SessionManager */
    protected SessionManager $session;

    /** @var User */
    private User $userModel;

    /**
     * Constructeur du contrôleur.
     *
     * Démarre la session, instancie le moteur de rendu et génère un jeton CSRF
     * si celui-ci n'est pas encore présent.
     * @param Renderer                $renderer        Moteur de rendu des vues.
     * @param LoggerInterface         $logger          Enregistreur PSR-3 des erreurs.
     * @param ErrorPresenterInterface $errorPresenter  Gestionnaire d'affichage des erreurs.
     * @param SessionManager          $session         Gestionnaire de session utilisateur.
     * @param User|null               $userModel       Modèle User (injectable pour tests).
     */
    public function __construct(Renderer $renderer, LoggerInterface $logger, ErrorPresenterInterface $errorPresenter, SessionManager $session, ?User $userModel = null)
    {
        // Démarrage de la session nécessaire pour gérer les données utilisateur et le jeton CSRF
        parent::__construct($renderer, $session);
        $this->logger         = $logger;
        $this->errorPresenter = $errorPresenter;
        $this->session        = $session;
        $this->session->start();

        $this->userModel = $userModel ?: new User($logger);

    }

    /**
     * Gère la requête HTTP entrante selon la méthode utilisée.
     *
     * Si la requête est de type POST, le contrôleur traite l'inscription via {@see handleRegister()}.
     * Sinon, il affiche le formulaire d'inscription via {@see showRegisterForm()}.
     *
     * @return void
     */
    public function handleRequest(): void
    {
        // Utilisation de l'opérateur ternaire pour choisir la méthode à exécuter selon la méthode HTTP
        ($_SERVER['REQUEST_METHOD'] === 'POST') ? $this->handleRegister() : $this->showRegisterForm();
    }

    /**
     * Exécute le contrôleur.
     *
     * Cette méthode publique sert de point d'entrée et appelle {@see handleRequest()} pour lancer le processus d'inscription.
     *
     * @return void
     */
    public function run(): void
    {
        $this->handleRequest();
    }

    /**
     * Affiche le formulaire d'inscription à l'utilisateur.
     *
     * Récupère les anciennes valeurs saisies ou définit des valeurs par défaut,
     * prépare les données nécessaires à la vue (titre, erreurs, jeton CSRF, anciennes valeurs, scripts)
     * et affiche le formulaire via le moteur de rendu.
     * En cas d'exception lors du rendu de la vue, l'erreur est loguée et une vue d'erreur générique est affichée.
     *
     * @return void
     */
    protected function showRegisterForm(): void
    {
        // Récupère les anciennes valeurs saisies par l'utilisateur ou utilise les valeurs par défaut
        $old = $this->data['old'] ?? ['nickname' => '', 'email' => ''];

        // Préparation des données pour la vue d'inscription
        $this->addData('title', 'S\'inscrire');
        $this->addData('errors', $this->getErrors());
        $this->addData('content', $this->renderer->render(
            'register',
            [
                'csrf_token' => $this->session->get('csrf_token'),
                'errors'     => $this->getErrors(),
                'old'        => $old,
            ]
        ));

        try {
            // Rendu final de la vue principale avec toutes les données préparées
            $this->renderDefault();
        } catch (\Throwable $e) {
            // Utilisation de l'instance du gestionnaire d'erreurs pour traiter l'exception
            $handler = new ErrorHandler($this->logger);
            $payload = $handler->handle($e);
            $this->errorPresenter->present($payload);

        }
    }

/**
 * Traite la tentative d'inscription de l'utilisateur.
 *
 * Vérifie successivement :
 * - la validité du token CSRF (sécurité contre les attaques)
 * - la conformité des champs (format, présence, sécurité)
 * - la disponibilité du pseudo et de l'email
 * - la création de l'utilisateur avec mot de passe sécurisé
 *
 * En cas de succès, l'utilisateur est connecté automatiquement puis redirigé.
 * En cas d'erreur, les messages sont affichés avec les valeurs précédemment saisies.
 *
 * @return void
 */
    protected function handleRegister(): void
    {
        // Étape 1 : Protection CSRF
        if (! $this->isCsrfTokenValid()) {
            $this->handleInvalidCsrf();
            return;
        }

        // Étape 2 : Extraction et nettoyage des champs postés
        [$nickname, $email, $password, $confirmPassword] = $this->getRegisterFields();

        // Étape 3 : Validation des champs
        $validator = $this->validateRegisterFields($nickname, $email, $password, $confirmPassword);

        // Étape 4 : Gestion des erreurs de validation
        if ($validator->hasErrors()) {
            $this->handleValidationErrors($validator, $nickname, $email);
            return;
        }

        // Étape 5 : Hash du mot de passe sécurisé (algorithme actuel par défaut)
        $storedHash = password_hash($password, PASSWORD_DEFAULT);

        // Étape 6 : Vérifie unicité du pseudo
        if ($this->userModel->getUserByNickname($nickname)) {
            $this->handleNicknameTaken($nickname, $email);
            return;
        }

        // Étape 7 : Vérifie unicité de l'email
        if ($this->userModel->getUserByEmail($email)) {
            $this->handleEmailTaken($nickname, $email);
            return;
        }

        // Étape 8 : Création en base
        if (! $this->userModel->createUser($nickname, $email, $storedHash)) {
            $this->handleUserCreationFailure($nickname, $email);
            return;
        }

        // Étape 9 : Connexion automatique + redirection
        $this->loginAndRedirect($nickname);
    }

    /**
     * Récupère et nettoie les champs du formulaire d'inscription.
     *
     * @return array{0: string, 1: string, 2: string, 3: string}
     */
    private function getRegisterFields(): array
    {
        $nickname        = trim(is_string($_POST['nickname']) ? $_POST['nickname'] : '');
        $email           = trim(is_string($_POST['email']) ? $_POST['email'] : '');
        $password        = trim(is_string($_POST['password']) ? $_POST['password'] : '');
        $confirmPassword = trim(is_string($_POST['confirm-password']) ? $_POST['confirm-password'] : '');
        return [$nickname, $email, $password, $confirmPassword];
    }

    /**
     * Valide les champs du formulaire d'inscription.
     */
    private function validateRegisterFields(string $nickname, string $email, string $password, string $confirmPassword): Validator
    {
        $validator = new Validator();

        $validator->validateRequired('nickname', $nickname, "Le champ pseudo est obligatoire.");
        $validator->validatePattern(
            'nickname',
            $nickname,
            '/^\w{4,}$/',
            'Votre pseudo doit contenir au moins 4 caractères alphanumériques sans espaces ni caractères spéciaux (sauf underscore)!'
        );
        $validator->validateRequired('email', $email, "Le champ email est obligatoire.");
        $validator->validateEmail('email', $email, "L'email n'est pas valide.");
        $validator->validateRequired('password', $password, "Le champ mot de passe est obligatoire.");
        $validator->validatePattern(
            'password',
            $password,
            '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}$/',
            'Le mot de passe doit contenir au moins un nombre, une lettre majuscule, une minuscule, un caractère spécial et comporter au moins 12 caractères'
        );
        $validator->validateRequired('confirm-password', $confirmPassword, "La confirmation du mot de passe est obligatoire.");
        $validator->validateMatch('confirm-password', $password, $confirmPassword, "Les mots de passe ne correspondent pas.");

        return $validator;
    }

    /**
     * Gère le cas d'un jeton CSRF invalide.
     */
    private function handleInvalidCsrf(): void
    {
        $this->addError('global', "Requête invalide ! Veuillez réessayer.");
        $this->showRegisterForm();
    }

    /**
     * Gère les erreurs de validation.
     */
    private function handleValidationErrors(Validator $validator, string $nickname, string $email): void
    {
        $this->errors = $validator->getErrors();
        $this->addData('old', $this->getOld([
            'nickname' => $nickname,
            'email'    => $email,
        ]));
        $this->showRegisterForm();
    }

    /**
     * Gère le cas où le pseudo est déjà utilisé.
     */
    private function handleNicknameTaken(string $nickname, string $email): void
    {
        $this->addError('nickname', "Ce pseudo est déjà utilisé. Veuillez en choisir un autre.");
        $this->addData('old', $this->getOld([
            'nickname' => $nickname,
            'email'    => $email,
        ]));
        $this->showRegisterForm();
    }

    /**
     * Gère le cas où l'email est déjà utilisé.
     */
    private function handleEmailTaken(string $nickname, string $email): void
    {
        $this->addError('email', "Cet email est déjà utilisé. Veuillez en choisir un autre.");
        $this->addData('old', $this->getOld([
            'nickname' => $nickname,
            'email'    => $email,
        ]));
        $this->showRegisterForm();
    }

    /**
     * Gère l'échec de création de l'utilisateur.
     */
    private function handleUserCreationFailure(string $nickname, string $email): void
    {
        $this->addError('global', "Erreur lors de la création de l'utilisateur. Veuillez réessayer.");
        $this->addData('old', $this->getOld([
            'nickname' => $nickname,
            'email'    => $email,
        ]));
        $this->showRegisterForm();
    }

    /**
     * Connecte l'utilisateur nouvellement inscrit et le redirige.
     */
    private function loginAndRedirect(string $nickname): void
    {
        $this->session->set('user', $nickname);
        $this->redirect('index');
    }

}
