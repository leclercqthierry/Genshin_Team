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
     * Prépare un environnement de test isolé pour la session.
     *
     * Cette méthode :
     * - Interrompt toute session éventuellement active via session_abort()
     * - Supprime le cookie de session pour éviter les résidus entre tests
     * - Démarre une nouvelle session vierge
     * - Réinitialise $_SESSION pour garantir un état propre
     */
    protected function setUp(): void
    {
        // Abort toute session précédente ouverte automatiquement
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_abort();
        }

        // Supprime les traces éventuelles côté cookie
        unset($_COOKIE[session_name()]);

        // Démarre une session 100 % propre
        session_start();
        $_SESSION = [];
    }

    /**
     * Nettoie l'environnement de test de session après chaque méthode.
     *
     * Cette méthode :
     * - Détruit la session si elle est active
     * - Vide $_SESSION
     * - Supprime le cookie de session pour assurer une isolation complète
     */
    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }

        unset($_COOKIE[session_name()]);
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

    /**
     * Vérifie que la méthode set enregistre correctement une donnée dans la session
     * et que la méthode get permet ensuite de la récupérer.
     *
     * Ce test :
     * - Stocke une clé 'user' avec la valeur 'Aether'
     * - Vérifie que la lecture via get renvoie bien 'Aether'
     */
    public function testSetAndGet(): void
    {
        $session = new SessionManager();

        $session->set('user', 'Aether');
        $value = $session->get('user');

        $this->assertSame('Aether', $value);
    }

    /**
     * Vérifie que la méthode get retourne la valeur par défaut
     * si la clé demandée n'existe pas dans la session.
     *
     * Ce test :
     * - Tente de lire une clé absente
     * - Vérifie que 'default_value' est bien retourné
     */
    public function testGetReturnsDefaultIfKeyMissing(): void
    {
        $session = new SessionManager();

        $value = $session->get('unknown_key', 'default_value');
        $this->assertSame('default_value', $value);
    }

    /**
     * Vérifie que la méthode start() appelle session_start()
     * lorsque aucune session n'est encore active.
     *
     * Ce test :
     * - Ferme une session active si nécessaire
     * - Vérifie que le statut initial est PHP_SESSION_NONE
     * - Instancie le SessionManager qui déclenche start()
     * - Vérifie que la session est effectivement démarrée
     */
    public function testStartTriggersSessionStart(): void
    {
        // Ferme toute session active pour forcer l’appel à session_start()
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_abort(); // annule la session sans la détruire
        }

        $this->assertSame(PHP_SESSION_NONE, session_status());

        $session = new SessionManager();

        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
        $this->assertTrue($session->isStarted());
    }

    /**
     * Vérifie que la méthode clear supprime toutes les entrées de la session.
     *
     * Ce test :
     * - Ajoute plusieurs clés dans la session
     * - Appelle clear()
     * - Vérifie que $_SESSION est vide
     */
    public function testClearEmptiesSession(): void
    {
        $session = new SessionManager();

        $session->set('key1', 'value1');
        $session->set('key2', 'value2');

        $session->clear();

        $this->assertSame([], $_SESSION);
    }

}
