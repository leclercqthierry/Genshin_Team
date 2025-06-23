<?php
declare (strict_types = 1);

namespace GenshinTeam\Utils;

use GenshinTeam\Utils\ErrorPayload;
use Psr\Log\LoggerInterface;
use Throwable;

class ErrorHandler
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    /**
     * Ne fait QUE :
     *  - logger l’exception
     *  - construire un payload (message + code HTTP)
     */
    public function handle(Throwable $e): ErrorPayload
    {
        // 1) Log
        $this->logger->error($e->getMessage(), ['exception' => $e]);

        // 2) Calcul d’un message user-friendly
        $message = $this->generateFriendlyMessage($e);

        // 3) Mapping vers un status HTTP
        $status = $this->mapStatusCode($e);

        return new ErrorPayload($message, $status);
    }

    private function generateFriendlyMessage(Throwable $e): string
    {
        // ton code de mapping d’exception → message
        // ex. si $e instanceof NotFoundException => "Page non trouvée"
        return 'Une erreur est survenue, veuillez réessayer plus tard.';
    }

    private function mapStatusCode(Throwable $e): int
    {
        // ex. NotFoundException → 404, AuthException → 401, sinon 500
        return 500;
    }
}
