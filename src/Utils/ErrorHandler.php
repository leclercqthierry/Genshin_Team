<?php
declare (strict_types = 1);

namespace GenshinTeam\Utils;

use GenshinTeam\Utils\ErrorPayload;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Gère les exceptions non prises en charge par l'application.
 *
 * Cette classe centralise la gestion des erreurs critiques en :
 * - consignant les exceptions dans un logger PSR-3 ;
 * - construisant un objet de type ErrorPayload contenant un message user-friendly et un code HTTP.
 *
 * @package GenshinTeam\Utils
 */
class ErrorHandler
{
    /**
     * @param LoggerInterface $logger Instance du logger PSR-3 utilisé pour tracer les erreurs.
     */
    public function __construct(
        private LoggerInterface $logger
    ) {}

    /**
     * Traite une exception et retourne un objet décrivant l'erreur côté utilisateur.
     *
     * Cette méthode :
     * - consigne l'erreur dans les logs ;
     * - génère un message utilisateur ;
     * - associe un code HTTP adapté.
     *
     * @param Throwable $e L'exception capturée.
     *
     * @return ErrorPayload Objet contenant le message et le code HTTP à retourner.
     */
    public function handle(Throwable $e): ErrorPayload
    {
        $this->logger->error($e->getMessage(), ['exception' => $e]);

        $message = $this->generateFriendlyMessage($e);
        $status  = $this->mapStatusCode($e);

        return new ErrorPayload($message, $status);
    }

    /**
     * Génère un message destiné à l'utilisateur à partir de l'exception donnée.
     *
     * Cette méthode peut être enrichie pour mapper différents types d'exceptions
     * vers des messages explicites.
     *
     * @param Throwable $e L'exception d'origine.
     *
     * @return string Message destiné à l'affichage.
     */
    private function generateFriendlyMessage(Throwable $e): string
    {
        return 'Une erreur est survenue, veuillez réessayer plus tard.';
    }

    /**
     * Associe un code HTTP pertinent à l'exception reçue.
     *
     * Exemple :
     * - NotFoundException => 404
     * - AuthException     => 401
     * - Sinon             => 500
     *
     * @param Throwable $e L'exception interceptée.
     *
     * @return int Code HTTP correspondant.
     */
    private function mapStatusCode(Throwable $e): int
    {
        return 500;
    }
}
