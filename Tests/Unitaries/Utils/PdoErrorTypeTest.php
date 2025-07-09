<?php

declare (strict_types = 1);

use GenshinTeam\Utils\ErrorHandler;
use GenshinTeam\Utils\PdoErrorType;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Fabrique utilitaire pour générer des exceptions PDO réalistes dans les tests.
 *
 * Cette classe permet de simuler une \PDOException avec un code SQLSTATE précis,
 * comme le ferait réellement une exception levée par PDO en base.
 * Utile pour tester le mappage des erreurs dans ErrorHandler ou d'autres composants.
 *
 * Exemple :
 *   $e = FakePdoExceptionFactory::withSqlState('Violation d\'intégrité', '23000');
 *
 * @internal Utilisation réservée aux tests unitaires.
 */
class FakePdoExceptionFactory
{
    /**
     * Crée une PDOException avec un code SQLSTATE personnalisé
     *
     * @param string $message Le message d’erreur
     * @param string $sqlState Le code SQLSTATE (ex: '23000', '08001', etc.)
     * @return \PDOException Une exception mimant un vrai comportement PDO
     */
    public static function withSqlState(string $message, string $sqlState): \PDOException
    {
        $e = new \PDOException($message);

        // Utilisation de la réflexion pour forcer le code
        $ref      = new \ReflectionObject($e);
        $codeProp = $ref->getProperty('code');
        $codeProp->setAccessible(true);
        $codeProp->setValue($e, $sqlState);

        return $e;
    }
}

/**
 * Tests unitaires couvrant la gestion des erreurs PDO par ErrorHandler,
 * en s’appuyant sur l’enum PdoErrorType pour générer des messages utilisateur adaptés.
 *
 * Chaque test simule une exception PDO avec un code SQLSTATE précis
 * afin de vérifier que le message retourné correspond bien à celui attendu
 * pour le type d’erreur mappé : connexion échouée, erreur SQL, timeout, etc.
 *
 * @covers \GenshinTeam\Utils\ErrorHandler
 * @covers \GenshinTeam\Utils\PdoErrorType
 */
class PdoErrorTypeTest extends TestCase
{
    /**
     * Vérifie que l'ErrorHandler retourne le bon message utilisateur
     * lorsqu'une exception PDO avec le code SQLSTATE '08001' (échec de connexion)
     * est levée. Ce code est mappé à la constante CONNECTION_FAILED de PdoErrorType.
     *
     * @covers \GenshinTeam\Utils\ErrorHandler::handle
     * @covers \GenshinTeam\Utils\PdoErrorType::getMessage
     */
    public function testHandleReturnsConnectionErrorMessage(): void
    {
        $exception = FakePdoExceptionFactory::withSqlState('Violation', '08001');
        $handler   = new ErrorHandler(new NullLogger());
        $payload   = $handler->handle($exception);

        $this->assertSame(
            PdoErrorType::CONNECTION_FAILED->getMessage(),
            $payload->getMessage()
        );
    }

    /**
     * Vérifie que l'ErrorHandler retourne le message approprié
     * lorsqu'une exception PDO avec le code SQLSTATE '42000' (erreur de syntaxe SQL)
     * est interceptée. Ce code est mappé à la constante SYNTAX_ERROR dans PdoErrorType.
     *
     * @covers \GenshinTeam\Utils\ErrorHandler::handle
     * @covers \GenshinTeam\Utils\PdoErrorType::getMessage
     */
    public function testHandleReturnsSyntaxErrorMessage(): void
    {
        $exception = FakePdoExceptionFactory::withSqlState('Erreur SQL', '42000');
        $handler   = new ErrorHandler(new NullLogger());
        $payload   = $handler->handle($exception);

        $this->assertSame(
            PdoErrorType::SYNTAX_ERROR->getMessage(),
            $payload->getMessage()
        );
    }

    /**
     * Vérifie que l'ErrorHandler retourne le message correspondant à un timeout
     * lorsqu'une exception PDO avec le code SQLSTATE 'HYT00' est levée.
     * Ce code est mappé à la constante TIMEOUT dans l'enum PdoErrorType.
     *
     * @covers \GenshinTeam\Utils\ErrorHandler::handle
     * @covers \GenshinTeam\Utils\PdoErrorType::getMessage
     */
    public function testHandleReturnsTimeoutMessage(): void
    {
        $exception = FakePdoExceptionFactory::withSqlState('Timeout', 'HYT00');
        $handler   = new ErrorHandler(new NullLogger());
        $payload   = $handler->handle($exception);

        $this->assertSame(
            PdoErrorType::TIMEOUT->getMessage(),
            $payload->getMessage()
        );
    }
}
