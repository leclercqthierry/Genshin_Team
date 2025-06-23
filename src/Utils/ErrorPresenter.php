<?php
declare (strict_types = 1);

namespace GenshinTeam\Utils;

use GenshinTeam\Renderer\Renderer;

class ErrorPresenter implements ErrorPresenterInterface
{
    public function __construct(
        private Renderer $renderer,
        private string $viewPath
    ) {}

    public function present(ErrorPayload $payload): void
    {
        http_response_code($payload->getStatusCode());

        // 1) Génère le contenu de la page d’erreur
        $content = $this->renderer->render(
            'templates/error',
            [
                'title'   => 'Erreur',
                'content' => $payload->getMessage(),
            ]
        );

        // 2) Injecte dans le layout global
        echo $this->renderer->render(
            'templates/default',
            [
                'title'   => 'Erreur',
                'content' => $content,
            ]
        );
    }
}
