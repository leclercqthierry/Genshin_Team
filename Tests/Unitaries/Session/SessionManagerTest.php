<?php
declare (strict_types = 1);

use GenshinTeam\Session\SessionManager;
use PHPUnit\Framework\TestCase;

class SessionManagerTest extends TestCase
{
    protected function setUp(): void
    {
        // On s'assure que la session est bien démarrée pour chaque test
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }

    public function testIsStarted(): void
    {
        $session = new SessionManager();

        // Session déjà démarrée dans setUp
        $this->assertTrue($session->isStarted());

        // On ferme la session pour tester le false
        session_write_close();
        $this->assertFalse($session->isStarted());
    }

    public function testRemove(): void
    {
        $session         = new SessionManager();
        $_SESSION['foo'] = 'bar';

        $session->remove('foo');
        $this->assertArrayNotHasKey('foo', $_SESSION);
    }

    public function testDestroy(): void
    {
        $session         = new SessionManager();
        $_SESSION['foo'] = 'bar';

        $session->destroy();

        // Après destruction, la session n'est plus active
        $this->assertFalse($session->isStarted());
        // $_SESSION n'est plus accessible, mais on peut vérifier que la variable existe toujours (vide)
        $this->assertIsArray($_SESSION);
    }
}
