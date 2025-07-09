<?php
declare (strict_types = 1);

use GenshinTeam\Utils\ErrorHandler;
use GenshinTeam\Utils\ErrorPayload;
use GenshinTeam\Utils\GenericErrorType;
use GenshinTeam\Utils\PdoErrorType;
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
        $this->assertSame(
            GenericErrorType::UNEXPECTED->getMessage(),
            $payload->getMessage()
        );

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
        $this->assertSame(
            GenericErrorType::UNEXPECTED->getMessage(),
            $payload->getMessage()
        );
        $this->assertSame(500, $payload->getStatusCode());
    }

    /**
     * Vérifie que le gestionnaire d’erreurs transforme correctement une exception PDO en payload utilisateur compréhensible.
     *
     * Ce test simule une exception de type \PDOException avec un code SQLSTATE 23000 (violation de contrainte d’intégrité),
     * et s’assure que le logger est bien invoqué avec les bons paramètres.
     *
     * Il valide que le message retourné dans le payload correspond à celui mappé par PdoErrorType,
     * que le code HTTP retourné est bien 500, et que le payload est bien une instance de ErrorPayload.
     *
     * @return void
     */
    public function testHandleReturnsPdoFriendlyMessage(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $pdoException = new \PDOException('Erreur PDO', (int) '23000'); // Simule une erreur d’intégrité (par exemple)

        $logger->expects($this->once())
            ->method('error')
            ->with(
                $this->equalTo('Erreur PDO'),
                $this->arrayHasKey('exception')
            );

        $handler = new ErrorHandler($logger);
        $payload = $handler->handle($pdoException);

        $this->assertInstanceOf(ErrorPayload::class, $payload);

        // Ce message dépend de ce que retourne PdoErrorType::from('23000')->getMessage()
        $this->assertSame(
            PdoErrorType::from('23000')->getMessage(),
            $payload->getMessage()
        );

        $this->assertSame(500, $payload->getStatusCode());
    }
}
