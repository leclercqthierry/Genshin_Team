<?php
declare (strict_types = 1);

namespace GenshinTeam\Controllers;

use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;

/**
 * Class AbstractController
 *
 * Cette classe abstraite sert de base pour la gestion des contrôleurs dans l'architecture MVC du projet.
 * Elle définit des méthodes et propriétés communes permettant de transmettre des données aux vues
 * ainsi que de gérer les erreurs rencontrées lors du traitement des requêtes.
 *
 * Chaque contrôleur enfant devra implémenter la méthode abstraite {@see handleRequest()}, qui contiendra
 * la logique de traitement spécifique à la requête.
 *
 * @package GenshinTeam\Controllers
 */
abstract class AbstractController
{
    /**
     * Tableau contenant les données à transmettre aux vues.
     *
     * @var array<string, string|array<mixed>>
     */
    protected array $data = [];

    /**
     * Tableau contenant les messages d'erreur.
     *
     * Ce tableau permet de stocker les messages d'erreur pouvant survenir lors du traitement de la requête.
     * Ces erreurs pourront être affichées à l'utilisateur pour lui indiquer les problèmes rencontrés.
     *
     * @var array<string, string>
     */
    protected array $errors = [];

    /**
     * Instance du moteur de rendu des vues.
     *
     * @var Renderer
     */
    protected Renderer $renderer;

    /**
     * Instance du gestionnaire de session.
     *
     * @var SessionManager
     */
    protected SessionManager $session;

    /**
     * Constructeur.
     *
     * @param Renderer $renderer Instance du moteur de rendu.
     * @param SessionManager $session Instance du gestionnaire de session.
     */
    public function __construct(Renderer $renderer, SessionManager $session)
    {
        $this->renderer = $renderer;
        $this->session  = $session;

        // Génération du jeton CSRF s'il n'existe pas déjà
        if (! $this->session->get('csrf_token')) {
            $this->session->set('csrf_token', bin2hex(random_bytes(32)));
        }

    }

    /**
     * Méthode abstraite à redéfinir dans les contrôleurs enfants.
     *
     * Chaque contrôleur enfant doit implémenter cette méthode pour définir
     * la logique spécifique de traitement de la requête.
     *
     * @return void
     */
    abstract protected function handleRequest(): void;

    /**
     * Définition de la méthode run obligatoire pour tous les contrôleurs
     * @return void
     */
    abstract public function run(): void;

    /**
     * Ajoute des données au tableau $data.
     *
     * Permet d'ajouter ou de remplacer une valeur dans le tableau, en utilisant une clé spécifique.
     * Cette méthode est utile pour préparer les données qui seront ensuite transmises à la vue.
     *
     * @param string                     $key   Clé à ajouter au tableau.
     * @param string|array<string, mixed> $value Valeur ou tableau de valeurs à ajouter.
     *
     * @return void
     */
    public function addData(string $key, string | array $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Récupère une valeur depuis le tableau de données.
     *
     * Cette méthode retourne la valeur associée à la clé spécifiée, si elle existe, ou null sinon.
     *
     * @param string $key La clé associée à la valeur.
     *
     * @return string|array<mixed>|null La valeur correspondante ou null si la clé n'existe pas.
     */
    public function getData(string $key): string | array | null
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Ajoute une erreur dans le tableau des erreurs.
     *
     * Permet de stocker une erreur associée à une clé spécifique (ex: 'global', 'email', etc.).
     * Ces erreurs pourront être affichées dans la vue pour informer l'utilisateur des dysfonctionnements.
     *
     * @param string $key     La clé de l'erreur.
     * @param string $message Le message d'erreur associé.
     *
     * @return void
     */
    protected function addError(string $key, string $message): void
    {
        $this->errors[$key] = $message;
    }

    /**
     * Récupère l'ensemble des erreurs enregistrées.
     *
     * Retourne le tableau complet des erreurs. Cela permet de vérifier si des erreurs
     * sont survenues lors du traitement de la requête et de les afficher dans la vue si nécessaire.
     *
     * @return array<string, string> Le tableau des erreurs.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Affiche la vue avec le template par défaut.
     *
     * @return void
     */
    protected function renderDefault(): void
    {
        echo $this->renderer->render('templates/default', $this->data);
    }

    /**
     * Récupère les anciennes données ou retourne des valeurs par défaut.
     *
     * @param array<string, mixed> $defaults Valeurs par défaut à retourner si aucune ancienne donnée n'existe.
     *
     * @return array<string, mixed> Les anciennes données ou les valeurs par défaut.
     */
    protected function getOld(array $defaults = []): array
    {
        $oldData = $this->data['old'] ?? $defaults;
        /**
         * @var array<string, mixed> $oldData
         */
        return $oldData;
    }

    /**
     * Stocke les anciennes données du formulaire pour les réafficher en cas d'erreur.
     *
     * @param array<string, mixed> $old
     * @return void
     */
    protected function setOld(array $old): void
    {
        $this->data['old'] = $old;
    }

    /**
     * Valide le token CSRF.
     *
     * @return bool True si le token est valide, false sinon.
     */
    protected function isCsrfTokenValid(): bool
    {
        $postToken    = $_POST['csrf_token'] ?? null;
        $sessionToken = $this->session->get('csrf_token');
        return is_string($postToken) && is_string($sessionToken) && $postToken === $sessionToken;
    }

    /**
     * Redirige vers une URL puis termine l'exécution du script.
     *
     * @param string $url L'URL de destination.
     *
     * @return void
     */
    // protected function redirect(string $url): void
    // {
    //     header('Location: ' . $url);
    //     exit;
    // }
    protected function redirect(string $url): void
    {
        $this->sendHeader("Location: {$url}");
        $this->terminate();
    }

    protected function sendHeader(string $header): void
    {
        header($header);
    }

    protected function terminate(): void
    {
        exit;
    }

    /**
     * Définit la route courante pour le contrôleur.
     *
     * @param string $route
     * @return void
     */
    abstract public function setCurrentRoute(string $route): void;

}
