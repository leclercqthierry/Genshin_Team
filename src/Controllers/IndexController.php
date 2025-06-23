<?php
declare (strict_types = 1);

namespace GenshinTeam\Controllers;

use GenshinTeam\Controllers\AbstractController;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorHandler;
use GenshinTeam\Utils\ErrorPresenterInterface;
use Psr\Log\LoggerInterface;

class IndexController extends AbstractController
{
    private LoggerInterface $logger;
    private ErrorPresenterInterface $errorPresenter;
    protected SessionManager $session;

    public function __construct(Renderer $renderer, LoggerInterface $logger, ErrorPresenterInterface $errorPresenter, SessionManager $session)
    {
        parent::__construct($renderer, $session);
        $this->logger         = $logger;
        $this->errorPresenter = $errorPresenter;
        $this->session->start();

    }

    public function run(): void
    {
        $this->handleRequest();
    }

    protected function handleRequest(): void
    {
        $user = $this->session->get('user');
        $this->addData(
            'title',
            is_string($user)
            ? 'Bienvenue sur Genshin Team, ' . htmlspecialchars($user)
            : 'Bienvenue sur Genshin Team'
        );

        try {
            $this->addData('content', $this->renderer->render('index'));
            $this->renderDefault();
        } catch (\Throwable $e) {
            // Utilisation du nouveau ErrorHandler
            $handler = new ErrorHandler($this->logger);
            $payload = $handler->handle($e);
            $this->errorPresenter->present($payload);
        }
    }
}
