<?php
/**
 * Classe Renderer pour le rendu des vues.
 *
 * Permet de charger et d'afficher dynamiquement des vues PHP en injectant des variables.
 * Utilisée par les contrôleurs pour générer le HTML à partir des templates.
 *
 * @package GenshinTeam
 */

namespace GenshinTeam\Renderer;

use Exception;

/**
 * Classe Renderer pour le rendu des vues.
 */
class Renderer
{

    /**
     * Chemin des vues.
     *
     * @var string
     */
    protected string $viewPath;

    /**
     * Constructeur de la classe Renderer.
     *
     * @param string $viewPath Le chemin du dossier contenant les vues.
     */
    public function __construct(string $viewPath)
    {
        $this->viewPath = rtrim($viewPath, '/') . '/';
    }

    /**
     * Rendu d'une vue avec des données dynamiques.
     *
     * @param string $view Le nom du fichier de vue (sans extension).
     * @param array<string, mixed> $data Un tableau associatif contenant les variables à passer à la vue.
     * @return string Le contenu de la vue rendu sous forme de chaîne de caractères.
     * @throws Exception Si le fichier de vue n'est pas trouvé.
     */
    public function render(string $view, array $data = []): string
    {
        $file = $this->viewPath . $view . '.php';

        if (! file_exists($file)) {
            throw new Exception("Vue introuvable : " . $file);
        }

        extract($data);
        ob_start();
        require_once $file;
        $output = ob_get_clean();
        return is_string($output) ? $output : '';
    }
}
