<?php
declare (strict_types = 1);

namespace GenshinTeam\Router;

use Exception;
use GenshinTeam\Controllers\AbstractController;
use GenshinTeam\Controllers\NotFoundController;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorPresenterInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Router
 *
 * Ce routeur gère l'acheminement des requêtes HTTP vers le contrôleur associé.
 * Il permet d'enregistrer des routes sous la forme d'une association entre un chemin d'URL et un contrôleur.
 * Lors du dispatch, il analyse l'URI demandée, la sécurise et tente de trouver une correspondance parmi
 * les routes enregistrées. Si aucune correspondance n'est trouvée, la page d'erreur 404 est affichée.
 *
 * @package GenshinTeam\Router
 */
class Router
{
    /**
     * Contrôleur injecté manuellement (souvent en contexte de test).
     *
     * @var object|null
     */
    private ?object $controllerInstance = null;

    /**
     * Logger PSR-3 pour la journalisation des événements et erreurs.
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Présentateur des erreurs à l'utilisateur final.
     *
     * @var ErrorPresenterInterface
     */
    private ErrorPresenterInterface $errorPresenter;

    /**
     * Gestionnaire de session HTTP.
     *
     * @var SessionManager
     */
    private SessionManager $session;

    /**
     * Initialise les dépendances nécessaires au routage.
     *
     * @param LoggerInterface         $logger          Logger PSR-3.
     * @param ErrorPresenterInterface $errorPresenter  Gestionnaire d'affichage des erreurs.
     * @param SessionManager          $session         Gestionnaire de session utilisateur.
     */
    public function __construct(LoggerInterface $logger, ErrorPresenterInterface $errorPresenter, SessionManager $session)
    {
        $this->logger         = $logger;
        $this->errorPresenter = $errorPresenter;
        $this->session        = $session;
    }

    /**
     * Tableau associatif stockant les routes.
     * La clé représente le chemin de l'URL, et la valeur correspond au nom du contrôleur.
     *
     * @var array<string, string>
     */
    private array $routes = [];

    /**
     * Définit l'instance du contrôleur.
     *
     * @param object $controller L'instance du contrôleur.
     *
     * @return void
     */
    public function setControllerInstance(object $controller): void
    {
        $this->controllerInstance = $controller;
    }

    /**
     * Ajoute une route.
     *
     * Enregistre une nouvelle route en associant un chemin d'URL à un contrôleur.
     *
     * @param string $path       Le chemin de l'URL de la route.
     * @param string $controller Le nom du contrôleur à charger pour cette route.
     *
     * @return void
     */
    public function addRoute(string $path, string $controller): void
    {
        $this->routes[$path] = $controller;
    }

    /**
     * Récupère la liste des routes enregistrées.
     *
     * Cette méthode retourne un tableau associatif où chaque clé représente
     * une route et chaque valeur correspond au contrôleur associé.
     *
     * @return array<string, string> Un tableau des routes enregistrées,
     *                               associant chaque nom de route à son contrôleur.
     */
    public function getRoutes(): array
    {
        return $this->routes; // Retourne les routes enregistrées
    }

    /**
     * Instancie dynamiquement un contrôleur à partir de l'URI donnée.
     *
     * Si aucun contrôleur ne correspond, le contrôleur 404 est retourné.
     *
     * @param string $uri URI propre de la requête (ex: 'login', 'index').
     *
     * @return AbstractController Instance du contrôleur correspondant.
     *
     * @throws Exception Si le contrôleur ne respecte pas l'héritage requis.
     */
    protected function resolveController(string $uri): AbstractController
    {
        $controllerClass = 'GenshinTeam\\Controllers\\' . ($this->routes[$uri] ?? 'NotFoundController');
        $renderer        = new Renderer(PROJECT_ROOT . '/src/Views');

        if (! class_exists($controllerClass)) {
            return new NotFoundController($renderer, $this->logger, $this->errorPresenter, $this->session);
        }

        $controller = new $controllerClass($renderer, $this->logger, $this->errorPresenter, $this->session);

        if (! $controller instanceof AbstractController) {
            throw new Exception("Le contrôleur '{$controllerClass}' n'hérite pas de AbstractController.");
        }

        return $controller;
    }

    /**
     * Dispatch la requête en déterminant et exécutant le contrôleur approprié.
     *
     * - Analyse et assainit l'URI de la requête.
     * - Résout l'instance du contrôleur.
     * - Vérifie que le contrôleur hérite de `AbstractController`.
     * - Exécute le contrôleur.
     *
     * @throws Exception Si le contrôleur n'hérite pas de AbstractController.
     * @throws Exception Si l'URI de la requête n'est pas valide.
     *
     * @return void
     */
    public function dispatch(): void
    {
        if (is_string($_SERVER['REQUEST_URI'])) {
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '';
            $uri = trim(filter_var($uri, FILTER_SANITIZE_URL) ?: '', '/');

            // Détermination du contrôleur à instancier
            $controller = $this->controllerInstance ?? $this->resolveController($uri);

            // Exécution du contrôleur
            /**
             * @var AbstractController $controller
             */
            $controller->run();
        } else {
            // Si l'URI n'est pas une chaîne, on affiche une erreur
            throw new Exception("L'URI de la requête n'est pas valide.");
        }
    }
}
