<?php
declare (strict_types = 1);

namespace GenshinTeam\Controllers;

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
 * Contrôleur chargé de la gestion du formulaire de réinitialisation de mot de passe.
 *
 * Utilise le modèle PasswordReset pour générer un lien de réinitialisation
 * et s’appuie sur des traits pour la validation du formulaire et la gestion des exceptions.
 *
 * @package GenshinTeam\Controllers
 */
class ForgotPasswordController extends AbstractController
{
    use HandleFormValidation;
    use ExceptionHandlerTrait;

    /**
     * Modèle chargé de la logique de réinitialisation de mot de passe.
     *
     * @var PasswordReset
     */
    private PasswordReset $resetModel;

    /**
     * Initialise le contrôleur avec ses dépendances.
     *
     * @param Renderer $renderer Moteur de rendu des vues.
     * @param LoggerInterface $logger Outil de journalisation des erreurs.
     * @param ErrorPresenterInterface $errorPresenter Affiche ou transmet les erreurs.
     * @param SessionManager $session Gestionnaire de session.
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
        $this->resetModel     = new PasswordReset($logger, new PhpMailerSender());
    }

    /**
     * Point d’entrée du contrôleur.
     *
     * Affiche le formulaire ou gère la soumission selon la méthode HTTP.
     *
     * @return void
     */
    public function run(): void
    {
        ($_SERVER['REQUEST_METHOD'] === 'POST')
        ? $this->handleRequest()
        : $this->showForm();
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
     * Affiche le formulaire de réinitialisation avec ses données et éventuelles erreurs.
     *
     * Utilise le moteur de rendu pour afficher le template 'forgot-password'.
     *
     * @return void
     */
    private function showForm(): void
    {
        $this->addData('title', 'Réinitialisation de mot de passe');
        $this->addData('errors', $this->getErrors());
        $this->addData('old', $this->getOld());
        $this->addData('scripts', '
            <script src="' . BASE_URL . '/public/assets/js/animation/arrow.js"></script>
            <script type="module" src="' . BASE_URL . '/public/assets/js/forgot-password-validator.js"></script>
        ');

        try {
            $this->addData('content', $this->renderer->render('forgot-password', [
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

    /**
     * Gère la soumission du formulaire.
     *
     * - Valide le token CSRF.
     * - Vérifie la validité de l’email fourni.
     * - Déclenche la génération du lien de réinitialisation.
     * - Affiche un message de confirmation neutre.
     *
     * @return void
     */
    protected function handleRequest(): void
    {
        if (! $this->isCsrfTokenValid()) {
            $this->addError('global', "Requête invalide.");
            $this->showForm();
            return;
        }

        $email = isset($_POST['email']) && is_string($_POST['email']) ? trim($_POST['email']) : '';

        $validator = new Validator();

        $validator->validateRequired('email', $email, "L'adresse email est requise.");
        if ($validator->hasErrors()) {
            $this->showValidationError('email', $email, $validator, 'email', fn() => $this->showForm());
            return;
        }

        $validator->validateEmail('email', $email, "Veuillez saisir une adresse email valide.");
        if ($validator->hasErrors()) {
            $this->showValidationError('email', $email, $validator, 'email', fn() => $this->showForm());
            return;
        }

        try {
            $this->resetModel->generateResetLink($email);
        } catch (\Throwable $e) {
            $this->handleException($e);
            return;
        }

        // Message neutre pour ne pas indiquer si l’email existe ou non
        $this->addData('success', "Si votre adresse est reconnue, un lien de réinitialisation vous a été envoyé.");
        $this->showForm();
    }

}
