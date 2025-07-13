<?php

declare (strict_types = 1);

namespace GenshinTeam\Controllers;

use GenshinTeam\Entities\User;
use GenshinTeam\Models\PasswordReset;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Traits\ExceptionHandlerTrait;
use GenshinTeam\Traits\HandleFormValidation;
use GenshinTeam\Utils\ErrorPresenterInterface;
use GenshinTeam\Utils\PhpMailerSender;
use GenshinTeam\Validation\Validator;
use Psr\Log\LoggerInterface;

/**
 * Contrôleur pour gérer la réinitialisation du mot de passe des utilisateurs.
 *
 * Ce contrôleur permet aux utilisateurs de réinitialiser leur mot de passe
 * en utilisant un lien de réinitialisation envoyé par email.
 */
class ResetPasswordController extends AbstractController
{

    use ExceptionHandlerTrait;
    use HandleFormValidation;

    private PasswordReset $resetModel;

    public function __construct(
        Renderer $renderer,
        LoggerInterface $logger,
        ErrorPresenterInterface $errorPresenter,
        SessionManager $session
    ) {
        parent::__construct($renderer, $session);
        $this->logger         = $logger;
        $this->errorPresenter = $errorPresenter;
        $this->resetModel     = new PasswordReset($logger, new PhpMailerSender());
    }

    public function setResetModel(PasswordReset $model): void
    {
        $this->resetModel = $model;
    }

    public function setCurrentRoute(string $route): void
    {}

    /**
     * Gère la requête de réinitialisation du mot de passe.
     *
     * Cette méthode vérifie si le token est présent, valide la requête POST,
     * gère la soumission du formulaire et affiche le formulaire de réinitialisation.
     */
    protected function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            /** @var string|null $token */
            $token = $_POST['token'] ?? '';

            if (! $token) {
                $this->addError('global', 'Lien de réinitialisation manquant.');
                $this->showForm();
                return;
            }

            $this->handleFormSubmission($token);
        } else {
            $token = $_GET['token'] ?? '';

            if ($token) {
                // Si le token est présent → on l’utilise, pas d'erreur à ce stade
                $this->showForm();
            } else {
                // Token manquant dans une URL censée le fournir
                if (isset($_GET['token'])) {
                    $this->addError('global', 'Lien de réinitialisation manquant.');
                }
                $this->showForm();
            }
        }
    }

    /**
     * Exécute le contrôleur pour gérer la réinitialisation du mot de passe.
     *
     * Cette méthode est appelée pour traiter la requête de réinitialisation du mot de passe.
     * Elle valide le token, gère la soumission du formulaire et affiche le formulaire de réinitialisation.
     */
    public function run(): void
    {
        $this->handleRequest();
    }

    /**
     * Valide le token de réinitialisation et retourne l'utilisateur associé.
     *
     * @param string $token
     * @return User|null
     */
    protected function validateToken(string $token): ?User
    {
        try {
            $user = $this->resetModel->findUserByToken($token);

            if (! $user || $this->resetModel->isTokenExpired($token)) {
                $this->addError('global', 'Lien invalide ou expiré.');
                return null;
            }

            return $user;
        } catch (\Throwable $e) {
            $this->handleException($e);
            return null;
        }
    }

    public function getPostString(string $key): ?string
    {
        return isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : null;
    }

    /**
     * Gère la soumission du formulaire de réinitialisation du mot de passe.
     *
     * @param string $token
     * @return void
     */
    protected function handleFormSubmission(string $token): void
    {
        if (! $this->isCsrfTokenValid()) {
            $this->addError('global', 'Requête invalide.');
            $this->showForm();
            return;
        }

        $password        = $this->getPostString('password');
        $confirmPassword = $this->getPostString('confirm-password');

        $validator = new Validator();
        $validator->validateRequired('password', $password, 'Le mot de passe est requis.');
        $validator->validateRequired('confirm-password', $confirmPassword, 'La confirmation est requise.');
        $validator->validateMatch('password', $password, $confirmPassword, 'Les mots de passe ne correspondent pas.');

        if ($validator->hasErrors()) {
            $this->showValidationError('password', $password, $validator, 'password', fn() => $this->showForm());
            return;
        }

        $user = $this->validateToken($token);
        if (! $user) {
            $this->showForm();
            return;
        }

        /** @var string $password */
        $this->updatePassword($user, $password, $token);
    }

    /**
     * Met à jour le mot de passe de l'utilisateur et invalide le token.
     *
     * @param User $user
     * @param string $password
     * @param string $token
     * @return void
     */
    protected function updatePassword(User $user, string $password, string $token): void
    {
        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $this->resetModel->updateUserPassword($user->getEmail(), $hashed);
            $this->resetModel->invalidateToken($token);

            $this->addData('success', 'Votre mot de passe a été réinitialisé avec succès.');
        } catch (\Throwable $e) {
            $this->handleException($e);
            return;
        }

        $this->showForm();
    }

    /**
     * Affiche le formulaire de réinitialisation du mot de passe.
     *
     * @return void
     */
    protected function showForm(): void
    {
        // Génère un nouveau token CSRF pour le formulair
        $this->session->set('csrf_token', bin2hex(random_bytes(32)));

        $this->addData('title', 'Réinitialisation du mot de passe');
        $this->addData('errors', $this->getErrors());
        $this->addData('old', $this->getOld());

        $token = $this->session->get('csrf_token');
        $this->addData('csrf_token', is_string($token) ? $token : '');

        $this->addData('scripts', '
            <script src="' . BASE_URL . '/public/assets/js/animation/arrow.js"></script>
            <script type="module" src="' . BASE_URL . '/public/assets/js/reset-password-validator.js"></script>
        ');

        try {
            $this->addData('content', $this->renderer->render('reset-password', [
                'errors'     => $this->getErrors(),
                'old'        => $this->getOld(),
                'csrf_token' => $this->session->get('csrf_token'),
                'success'    => $this->data['success'] ?? null,
            ]));
            $this->renderDefault();
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }
}
