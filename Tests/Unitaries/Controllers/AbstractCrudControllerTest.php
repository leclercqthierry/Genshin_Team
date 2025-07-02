<?php
declare (strict_types = 1);

use GenshinTeam\Controllers\AbstractCrudController;
use GenshinTeam\Models\CrudModelInterface;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use GenshinTeam\Utils\ErrorPresenterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DummyCrudController extends AbstractCrudController
{
    /** @var array<int, string> */
    public array $calls = [];

    protected function getAddRoute(): string
    {return 'add-entity';}
    protected function getEditRoute(): string
    {return 'edit-entity';}
    protected function getDeleteRoute(): string
    {return 'delete-entity';}

    protected function handleAdd(): void
    {$this->calls[] = 'add';}
    protected function handleEdit(): void
    {$this->calls[] = 'edit';}
    protected function handleDelete(): void
    {$this->calls[] = 'delete';}
    protected function showList(): void
    {$this->calls[] = 'list';}
}

class AbstractCrudControllerTest extends TestCase
{
    private DummyCrudController $controller;

    protected function setUp(): void
    {
        $renderer       = $this->createMock(Renderer::class);
        $logger         = $this->createMock(LoggerInterface::class);
        $errorPresenter = $this->createMock(ErrorPresenterInterface::class);
        $session        = $this->createMock(SessionManager::class);
        $model          = $this->createMock(CrudModelInterface::class);

        $this->controller = new DummyCrudController(
            $renderer,
            $logger,
            $errorPresenter,
            $session,
            $model
        );
    }

    public function testDispatchAddRouteCallsHandleAdd(): void
    {
        $this->controller->setCurrentRoute('add-entity');
        $this->controller->run();
        $this->assertContains('add', $this->controller->calls);
    }

    public function testDispatchEditRouteCallsHandleEdit(): void
    {
        $this->controller->setCurrentRoute('edit-entity');
        $this->controller->run();
        $this->assertContains('edit', $this->controller->calls);
    }

    public function testDispatchDeleteRouteCallsHandleDelete(): void
    {
        $this->controller->setCurrentRoute('delete-entity');
        $this->controller->run();
        $this->assertContains('delete', $this->controller->calls);
    }

    public function testDispatchUnknownRouteCallsShowList(): void
    {
        $this->controller->setCurrentRoute('unknown-route');
        $this->controller->run();
        $this->assertContains('list', $this->controller->calls);
    }
}
