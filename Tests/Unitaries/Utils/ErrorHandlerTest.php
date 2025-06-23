<?php
declare (strict_types = 1);

use GenshinTeam\Utils\ErrorHandler;
use GenshinTeam\Utils\ErrorPayload;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Tests de la classe ErrorHandler.
 *
 * Vérifie que les exceptions sont correctement journalisées et transformées en payloads métier.
 *
 * @covers \GenshinTeam\Utils\ErrorHandler
 */
class ErrorHandlerTest extends TestCase
{
    /**
     * Vérifie que handle() loggue l'exception et retourne un payload générique.
     *
     * @return void
     */
    public function testHandleLogsAndReturnsPayload(): void
    {
        $logger    = $this->createMock(LoggerInterface::class);
        $exception = new \Exception('Erreur simulée');

        // Le logger doit être appelé une fois avec le message de l'exception et un tableau contenant la clé 'exception'
        $logger->expects($this->once())
            ->method('error')
            ->with(
                $this->equalTo('Erreur simulée'),
                $this->arrayHasKey('exception')
            );

        $handler = new ErrorHandler($logger);
        $payload = $handler->handle($exception);

        $this->assertInstanceOf(ErrorPayload::class, $payload);
        $this->assertSame('Une erreur est survenue, veuillez réessayer plus tard.', $payload->getMessage());
        $this->assertSame(500, $payload->getStatusCode());
    }

    /**
     * Vérifie que handle() retourne une réponse structurée même pour une exception personnalisée.
     *
     * @return void
     */
    public function testHandleReturnsCustomMessageAndStatusForNotFound(): void
    {
        $logger = new NullLogger();

        // Crée une classe anonyme étendant Exception pour simuler un cas métier personnalisé
        $notFound = new class('Not found') extends \Exception
        {
        };

        $handler = new ErrorHandler($logger);

        $payload = $handler->handle($notFound);

        $this->assertInstanceOf(ErrorPayload::class, $payload);
        // Si ton ErrorHandler mappe différemment certains types d'exception, adapte ici
        $this->assertSame('Une erreur est survenue, veuillez réessayer plus tard.', $payload->getMessage());
        $this->assertSame(500, $payload->getStatusCode());
    }
}
