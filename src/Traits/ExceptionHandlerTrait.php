<?php
declare (strict_types = 1);

namespace GenshinTeam\Traits;

use GenshinTeam\Utils\ErrorHandler;
use GenshinTeam\Utils\ErrorPresenterInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Trait permettant la gestion centralisée des exceptions.
 *
 * Utilise ErrorHandler pour transformer une exception en payload exploitable,
 * puis utilise ErrorPresenterInterface pour afficher ou transmettre l'erreur
 * de manière adaptée à l'utilisateur ou au système.
 *
 * @package GenshinTeam\Traits
 */
trait ExceptionHandlerTrait
{
    /**
     * Logger PSR permettant l'enregistrement des erreurs.
     *
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Présenteur d'erreur utilisé pour afficher ou transmettre les erreurs.
     *
     * @var ErrorPresenterInterface
     */
    protected ErrorPresenterInterface $errorPresenter;

    /**
     * Gère une exception en la journalisant et en la présentant via le présentateur.
     *
     * @param Throwable $e L'exception à gérer.
     *
     * @return void
     */
    protected function handleException(Throwable $e): void
    {
        $payload = (new ErrorHandler($this->logger))->handle($e);
        $this->errorPresenter->present($payload);
    }
}
