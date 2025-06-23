<?php
declare (strict_types = 1);

namespace GenshinTeam\Utils;

use GenshinTeam\Renderer\Renderer;

/**
 * Présente les erreurs à l'utilisateur final via le moteur de rendu.
 *
 * Cette classe transforme un objet ErrorPayload en une page HTML affichable à l'utilisateur,
 * en utilisant des templates (comme 'error' et 'default') pour formater le message.
 *
 * @package GenshinTeam\Utils
 */
class ErrorPresenter implements ErrorPresenterInterface
{
    /** @phpstan-ignore-next-line property.onlyWritten */
    private string $viewPath;

    /**
     * Constructeur du présentateur d'erreurs.
     *
     * @param Renderer $renderer  Moteur de rendu chargé d'injecter les données dans les vues.
     * @param string   $viewPath  Chemin de base des vues (non utilisé ici directement, mais conservé pour compatibilité).
     */
    public function __construct(
        private Renderer $renderer,
        string $viewPath
    ) {
        $this->viewPath = $viewPath;
    }

    /**
     * Génère et affiche une page d'erreur à partir d'un ErrorPayload.
     *
     * Cette méthode :
     * - définit le code HTTP de la réponse ;
     * - rend le contenu via un template d'erreur ;
     * - l'insère dans le template principal pour l'affichage.
     *
     * @param ErrorPayload $payload Données de l'erreur à présenter.
     *
     * @return void
     */
    public function present(ErrorPayload $payload): void
    {
        http_response_code($payload->getStatusCode());

        $content = $this->renderer->render(
            'templates/error',
            [
                'title'   => 'Erreur',
                'content' => $payload->getMessage(),
            ]
        );

        echo $this->renderer->render(
            'templates/default',
            [
                'title'   => 'Erreur',
                'content' => $content,
            ]
        );
    }
}
