<?php
declare (strict_types = 1);

use GenshinTeam\Session\SessionManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de la classe SessionManager.
 *
 * Vérifie la gestion des sessions : démarrage, suppression de clés, et destruction complète.
 *
 * @covers \GenshinTeam\Session\SessionManager
 */
class SessionManagerTest extends TestCase
{
    /**
     * Démarre la session PHP avant chaque test et réinitialise $_SESSION.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // On démarre une session propre si nécessaire
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Réinitialise le contenu de $_SESSION pour garantir l’isolement des tests
        $_SESSION = [];
    }

    /**
     * Détruit la session active après chaque test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();   // Vide $_SESSION
            session_destroy(); // Termine la session
        }
    }

    /**
     * Teste la méthode isStarted() dans les cas où :
     * - la session est active,
     * - puis fermée avec session_write_close().
     *
     * @return void
     */
    public function testIsStarted(): void
    {
        $session = new SessionManager();

        // La session est active après setUp()
        $this->assertTrue($session->isStarted());

        // On la ferme manuellement
        session_write_close();
        $this->assertFalse($session->isStarted());
    }

    /**
     * Vérifie que remove() supprime une clé donnée dans $_SESSION.
     *
     * @return void
     */
    public function testRemove(): void
    {
        $session = new SessionManager();

        // Ajoute une entrée à la session
        $_SESSION['foo'] = 'bar';

        // Supprime cette entrée via la méthode du composant
        $session->remove('foo');

        // Vérifie que la clé a bien été retirée
        $this->assertArrayNotHasKey('foo', $_SESSION);
    }

    /**
     * Vérifie que destroy() vide $_SESSION et ferme la session PHP.
     *
     * @return void
     */
    public function testDestroy(): void
    {
        $session = new SessionManager();

        // Remplit la session avec une donnée factice
        $_SESSION['foo'] = 'bar';

        // Détruit la session côté gestionnaire
        $session->destroy();

        // Vérifie que la session est considérée comme fermée
        $this->assertFalse($session->isStarted());

        // PHP crée toujours $_SESSION même après session_destroy()
        // → on teste ici qu’elle est bien redevenue un tableau vide
        $this->assertEmpty($_SESSION);
    }
}
