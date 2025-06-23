<?php
declare (strict_types = 1);

namespace GenshinTeam\Renderer;

use Exception;

/**
 * Classe Renderer pour le rendu des vues.
 *
 * Permet de charger et d'afficher dynamiquement des vues PHP en injectant des variables.
 * Utilisée par les contrôleurs pour générer le HTML à partir des templates.
 *
 * @package GenshinTeam
 */
class Renderer
{

    /**
     * Chemin absolu vers le répertoire contenant les fichiers de vues PHP.
     *
     * Terminé automatiquement par un slash (/).
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
     * Injecte les variables fournies dans le fichier de vue correspondant et retourne le HTML généré.
     *
     * @param string $view Le nom du fichier de vue (sans extension .php).
     * @param array<string, mixed> $data Données à injecter dans la vue.
     *
     * @return string HTML généré par le rendu.
     *
     * @throws Exception Si le fichier de vue est introuvable.
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
