<?php
declare (strict_types = 1);

use GenshinTeam\Utils\ErrorPayload;
use GenshinTeam\Utils\ErrorPresenter;
use PHPUnit\Framework\TestCase;

/**
 * Teste le comportement du ErrorPresenter lors de l'affichage d'erreurs HTML.
 *
 * @covers \GenshinTeam\Utils\ErrorPresenter
 */
class ErrorPresenterTest extends TestCase
{
    /**
     * Vérifie que le payload est correctement transformé en HTML via le renderer
     * et que le code HTTP est défini selon le status du payload.
     *
     * @return void
     */
    public function testPresentRendersAndDisplaysError(): void
    {
        /** @var array<int, string> $calls Liste des vues rendues dans l'ordre */
        $calls = [];

        // Mocke le renderer avec un comportement personnalisé sur render()
        $renderer = $this->getMockBuilder(\GenshinTeam\Renderer\Renderer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();

        // Simule les retours de render() selon la vue appelée
        $renderer->method('render')
            ->willReturnCallback(function (string $view, array $data = []) use (&$calls): string {
                $calls[] = $view;

                return match ($view) {
                    'templates/error'   => '<div>Erreur personnalisée</div>',
                    'templates/default' => '<html><body>Erreur personnalisée</body></html>',
                    default             => '',
                };
            });

        // Instancie le présentateur d’erreur avec un chemin fictif
        $presenter = new ErrorPresenter($renderer, '/fake/path');

        // Crée un payload d’erreur simulé (status 404)
        $payload = new ErrorPayload('Erreur personnalisée', 404);

        // Capture la sortie HTML générée
        ob_start();
        $presenter->present($payload);
        $output = ob_get_clean();
        $this->assertIsString($output);

        // Vérifie que les vues sont appelées dans le bon ordre
        $this->assertSame(['templates/error', 'templates/default'], $calls);

        // Vérifie que le code HTTP a bien été modifié
        $this->assertSame(404, http_response_code());

        // Vérifie que le HTML généré contient bien la sortie attendue
        $this->assertStringContainsString('<html><body>Erreur personnalisée</body></html>', $output);
    }
}
