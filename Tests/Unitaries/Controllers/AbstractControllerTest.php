<?php
declare (strict_types = 1);

use GenshinTeam\Controllers\AbstractController;
use GenshinTeam\Renderer\Renderer;
use GenshinTeam\Session\SessionManager;
use PHPUnit\Framework\TestCase;

/**
 * Contrôleur factice destiné à tester les fonctionnalités héritées d'AbstractController.
 */
class DummyController extends AbstractController
{
    public function run(): void
    {}

    protected function handleRequest(): void
    {}

    /**
     * Permet d'invoquer renderDefault() et capturer le rendu.
     *
     * @return string
     */
    public function callRenderDefault(): string
    {
        ob_start();
        $this->renderDefault();
        return ob_get_clean() ?: '';
    }

    /**
     * Invoque la redirection.
     *
     * @param string $url
     * @return void
     */
    public function callRedirect(string $url): void
    {
        parent::redirect($url);
    }

    /**
     * Ajoute une erreur de validation (accès à la méthode protected).
     *
     * @param string $key
     * @param string $msg
     * @return void
     */
    public function callAddError(string $key, string $msg): void
    {
        $this->addError($key, $msg);
    }

    /**
     * Récupère les anciennes valeurs soumises ou les valeurs par défaut.
     *
     * @param array<string, mixed> $defaults
     * @return array<string, mixed>
     */

    public function callGetOld(array $defaults = []): array
    {
        return $this->getOld($defaults);
    }

    /**
     * Vérifie la validité du jeton CSRF.
     *
     * @return bool
     */
    public function callIsCsrfTokenValid(): bool
    {
        return $this->isCsrfTokenValid();
    }
}

/**
 * Tests unitaires de la classe AbstractController via DummyController.
 *
 * @covers \GenshinTeam\Controllers\AbstractController
 */
class AbstractControllerTest extends TestCase
{
    /** @var string */
    private string $viewPath;

    /**
     * Prépare un environnement de rendu temporaire.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->viewPath = sys_get_temp_dir() . '/views_' . uniqid();
        @mkdir($this->viewPath . '/templates', 0777, true);
        file_put_contents($this->viewPath . '/templates/default.php', '<html><?= $title ?? "" ?><?= $content ?? "" ?></html>');
    }

    /**
     * Nettoie les fichiers temporaires créés.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        @unlink($this->viewPath . '/templates/default.php');
        @rmdir($this->viewPath . '/templates');
        @rmdir($this->viewPath);
    }

    /**
     * Vérifie l’ajout et la récupération de données.
     *
     * @return void
     */
    public function testAddAndGetData(): void
    {
        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());
        $controller->addData('foo', 'bar');

        $this->assertSame('bar', $controller->getData('foo'));
        $this->assertNull($controller->getData('unknown'));
    }

    /**
     * Vérifie l’ajout et la récupération d’erreurs de validation.
     *
     * @return void
     */
    public function testAddAndGetErrors(): void
    {
        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());

        $controller->callAddError('global', 'Erreur globale');
        $controller->callAddError('email', 'Erreur email');

        $this->assertSame(['global' => 'Erreur globale', 'email' => 'Erreur email'], $controller->getErrors());
    }

    /**
     * Vérifie que renderDefault() intègre les variables title et content.
     *
     * @return void
     */
    public function testRenderDefault(): void
    {
        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());
        $controller->addData('title', 'Titre');
        $controller->addData('content', 'Contenu');

        $output = $controller->callRenderDefault();
        $this->assertStringContainsString('Titre', $output);
        $this->assertStringContainsString('Contenu', $output);
    }

    /**
     * Vérifie que getOld() retourne les valeurs par défaut si aucun old n’est défini.
     *
     * @return void
     */
    public function testGetOldReturnsDefaults(): void
    {
        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());
        $defaults   = ['nickname' => 'Jean', 'email' => 'a@b.c'];

        $this->assertSame($defaults, $controller->callGetOld($defaults));
    }

    /**
     * Vérifie que getOld() retourne les anciennes valeurs si elles sont présentes.
     *
     * @return void
     */
    public function testGetOldReturnsOldData(): void
    {
        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());
        $controller->addData('old', ['nickname' => 'Paul']);

        $this->assertSame(['nickname' => 'Paul'], $controller->callGetOld(['nickname' => 'Jean']));
    }

    /**
     * Vérifie la validité du jeton CSRF avec un cas valide puis invalide.
     *
     * @return void
     */
    public function testIsCsrfTokenValid(): void
    {
        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());

        $_SESSION['csrf_token'] = 'abc';
        $_POST['csrf_token']    = 'abc';
        $this->assertTrue($controller->callIsCsrfTokenValid());

        $_POST['csrf_token'] = 'wrong';
        $this->assertFalse($controller->callIsCsrfTokenValid());
    }

    /**
     * Vérifie que setOld() stocke correctement les anciennes valeurs du formulaire.
     *
     * @return void
     */
    public function testSetOldStoresOldData(): void
    {
        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());
        $old        = ['day' => 'Lundi', 'note' => 'Test'];
        // On utilise setOld pour stocker les anciennes valeurs
        $reflection = new \ReflectionClass($controller);
        $method     = $reflection->getMethod('setOld');
        $method->setAccessible(true);
        $method->invoke($controller, $old);

        // On vérifie que getOld retourne bien ces valeurs
        $this->assertSame($old, $controller->callGetOld());
    }
}
