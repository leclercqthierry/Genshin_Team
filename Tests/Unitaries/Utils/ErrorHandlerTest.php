<?php
use GenshinTeam\Utils\ErrorHandler;
use GenshinTeam\Utils\ErrorPayload;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ErrorHandlerTest extends TestCase
{
    public function testHandleLogsAndReturnsPayload()
    {
        $logger    = $this->createMock(\Psr\Log\LoggerInterface::class);
        $exception = new \Exception('Erreur simulée');

        // On vérifie que le logger est bien appelé avec le bon message
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

    public function testHandleReturnsCustomMessageAndStatusForNotFound()
    {
        $logger = new NullLogger();

        // Simule une exception personnalisée (exemple)
        $notFound = new class('Not found') extends \Exception
        {};

        $handler = new ErrorHandler($logger);

        // Si tu ajoutes un mapping spécifique dans generateFriendlyMessage/mapStatusCode, adapte ici
        $payload = $handler->handle($notFound);

        $this->assertInstanceOf(ErrorPayload::class, $payload);
        // Ici, adapte le message attendu si tu fais un mapping spécifique
        $this->assertSame('Une erreur est survenue, veuillez réessayer plus tard.', $payload->getMessage());
        $this->assertSame(500, $payload->getStatusCode());
    }
}
