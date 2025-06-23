<?php
use GenshinTeam\Utils\ErrorPayload;
use GenshinTeam\Utils\ErrorPresenter;
use PHPUnit\Framework\TestCase;

class ErrorPresenterTest extends TestCase
{

    public function testPresentRendersAndDisplaysError()
    {
        $calls = [];

        $renderer = $this->getMockBuilder(\GenshinTeam\Renderer\Renderer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();

        $renderer->method('render')
            ->willReturnCallback(function ($view, $data = []) use (&$calls) {
                $calls[] = $view;
                if ($view === 'templates/error') {
                    return '<div>Erreur personnalisée</div>';
                }
                if ($view === 'templates/default') {
                    return '<html><body>Erreur personnalisée</body></html>';
                }
                return '';
            });

        $presenter = new ErrorPresenter($renderer, '/fake/path');
        $payload   = new ErrorPayload('Erreur personnalisée', 404);

        ob_start();
        $presenter->present($payload);
        $output = ob_get_clean();

        // Vérifie l'ordre des appels
        $this->assertSame(['templates/error', 'templates/default'], $calls);

        // Vérifie le code HTTP
        $this->assertSame(404, http_response_code());

        // Vérifie le HTML final
        $this->assertStringContainsString('<html><body>Erreur personnalisée</body></html>', $output);
    }
}
