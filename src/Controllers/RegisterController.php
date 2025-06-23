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
    private LoggerInterface $logger;
    private ErrorPresenterInterface $errorPresenter;
    protected SessionManager $session;
    private User $userModel;
    /**
     * Constructeur du contrôleur.
     *
     * Démarre la session, instancie le moteur de rendu et génère un jeton CSRF
     * si celui-ci n'est pas encore présent.
     */
    public function __construct(Renderer $renderer, LoggerInterface $logger, ErrorPresenterInterface $errorPresenter, SessionManager $session, ?User $userModel = null)
    {
        // Démarrage de la session nécessaire pour gérer les données utilisateur et le jeton CSRF
        parent::__construct($renderer, $session);
        $this->logger         = $logger;
        $this->errorPresenter = $errorPresenter;
        $this->session        = $session;
        $this->session->start();

        // Génération d'un jeton CSRF s'il n'est pas déjà défini afin de sécuriser le formulaire d'inscription
        if (! $this->session->get('csrf_token')) {
            $this->session->set('csrf_token', bin2hex(random_bytes(32)));
        }

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
     * Valide la soumission du formulaire d'inscription en vérifiant :
     * - Le jeton CSRF pour sécuriser la requête
     * - Le remplissage et le format des champs (pseudo, email, mot de passe et confirmation)
     * - La correspondance entre le mot de passe et sa confirmation
     *
     * En cas d'erreur, le formulaire est réaffiché avec les erreurs et les anciennes valeurs.
     * Si la validation est réussie, le mot de passe est hashé, l'utilisateur est créé en base de données
     * et l'utilisateur est automatiquement connecté avant d'être redirigé vers la page d'accueil.
     *
     * @return void
     */
    protected function handleRegister(): void
    {
        // Vérification du jeton CSRF pour sécuriser la soumission du formulaire
        if (! $this->isCsrfTokenValid()) {
            $this->addError('global', "Requête invalide ! Veuillez réessayer.");
            $this->showRegisterForm();
            return;
        }

        // Récupération et nettoyage des données du formulaire d'inscription
        $nickname        = trim(is_string($_POST['nickname']) ? $_POST['nickname'] : '');
        $email           = trim(is_string($_POST['email']) ? $_POST['email'] : '');
        $password        = trim(is_string($_POST['password']) ? $_POST['password'] : '');
        $confirmPassword = trim(is_string($_POST['confirm-password']) ? $_POST['confirm-password'] : '');

        // Instanciation du validateur pour contrôler le contenu des champs soumis
        $validator = new Validator();

        // Validation du champ "nickname"
        $validator->validateRequired('nickname', $nickname, "Le champ pseudo est obligatoire.");
        $validator->validatePattern(
            'nickname',
            $nickname,
            '/^\w{4,}$/',
            'Votre pseudo doit contenir au moins 4 caractères alphanumériques sans espaces ni caractères spéciaux (sauf underscore)!'
        );

        // Validation du champ "email"
        $validator->validateRequired('email', $email, "Le champ email est obligatoire.");
        $validator->validateEmail('email', $email, "L'email n'est pas valide.");

        // Validation du champ "password"
        $validator->validateRequired('password', $password, "Le champ mot de passe est obligatoire.");
        $validator->validatePattern(
            'password',
            $password,
            '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}$/',
            'Le mot de passe doit contenir au moins un nombre, une lettre majuscule, une minuscule, un caractère spécial et comporter au moins 12 caractères'
        );

        // Validation du champ "confirm-password"
        $validator->validateRequired('confirm-password', $confirmPassword, "La confirmation du mot de passe est obligatoire.");
        $validator->validateMatch('confirm-password', $password, $confirmPassword, "Les mots de passe ne correspondent pas.");

        // Si le validateur a détecté des erreurs, renvoie le formulaire avec les erreurs et les anciennes saisies
        if ($validator->hasErrors()) {
            $this->errors = $validator->getErrors();
            $this->addData('old', $this->getOld([
                'nickname' => $nickname,
                'email'    => $email,
            ]));
            $this->showRegisterForm();
            return;
        }

        // Hashage sécurisé du mot de passe avant stockage en base de données
        $storedHash = password_hash($password, PASSWORD_DEFAULT);

        $userModel = $this->userModel;

        // Vérification que le pseudo choisi n'est pas déjà utilisé
        if ($userModel->getUserByNickname($nickname)) {
            $this->addError('nickname', "Ce pseudo est déjà utilisé. Veuillez en choisir un autre.");
            $this->addData('old', $this->getOld([
                'nickname' => $nickname,
                'email'    => $email,
            ]));
            $this->showRegisterForm();
            return;
        }

        // Vérification que le mail choisi n'est pas déjà utilisé
        if ($userModel->getUserByEmail($email)) {
            $this->addError('email', "Cet email est déjà utilisé. Veuillez en choisir un autre.");
            $this->addData('old', $this->getOld([
                'nickname' => $nickname,
                'email'    => $email,
            ]));
            $this->showRegisterForm();
            return;
        }

        // Création de l'utilisateur en base de données via le modèle User
        $success = $userModel->createUser($nickname, $email, $storedHash);

        // En cas d'échec lors de la création de l'utilisateur, réaffichage du formulaire avec un message d'erreur
        if (! $success) {
            $this->addError('global', "Erreur lors de la création de l'utilisateur. Veuillez réessayer.");
            $this->addData('old', $this->getOld([
                'nickname' => $nickname,
                'email'    => $email,
            ]));
            $this->showRegisterForm();
            return;
        }

        // Connexion automatique de l'utilisateur nouvellement inscrit et redirection vers la page d'accueil
        $this->session->set('user', $nickname);

        header('Location: index');
        exit;
    }
}
