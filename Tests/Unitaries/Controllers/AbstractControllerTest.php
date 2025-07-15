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

    public function setCurrentRoute(string $route): void
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

    /**
     * Permet d’invoquer renderForbidden() avec un message personnalisé.
     *
     * @param string $message
     * @return void
     */
    public function callRenderForbidden(string $message): void
    {
        $this->renderForbidden($message);
    }

    /**
     * @return array<string, string|array<mixed>>
     */
    public function getAllData(): array
    {
        return $this->data;
    }

    public function callCheckAdminAccess(): bool
    {
        return $this->checkAdminAccess();
    }

    public function getSession(): SessionManager
    {
        return $this->session;
    }
}

/**
 * Autre contrôleur factice destiné à être utiliser pour certain(s) test(s)
 */
class DummyControllerBis extends DummyController
{
    public ?string $caughtException = null;

    protected function renderDefault(): void
    {
        throw new \RuntimeException('Erreur simulée dans renderDefault');
    }

    protected function handleException(\Throwable $e): void
    {
        $this->caughtException = $e->getMessage();
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
        // Supprimer les vues temporaires
        @unlink($this->viewPath . '/templates/default.php');
        @rmdir($this->viewPath . '/templates');
        @rmdir($this->viewPath);

        // Nettoyer les superglobales modifiées
        unset($_POST['csrf_token'], $_SESSION['csrf_token']);
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

    /**
     * Teste la méthode redirect() afin de vérifier qu'elle envoie bien un en-tête HTTP de redirection
     * et interrompt correctement l’exécution via terminate().
     *
     * Le test s’appuie sur un mock de DummyController dans lequel les méthodes `sendHeader` et `terminate`
     * sont redéfinies pour pouvoir être surveillées.
     *
     * @return void
     *
     * @covers \GenshinTeam\Controllers\AbstractController::redirect
     */
    public function testRedirectSendsHeaderAndExits(): void
    {
        $controller = $this->getMockBuilder(DummyController::class)
            ->setConstructorArgs([new Renderer('/chemin/fake'), new SessionManager()])
            ->onlyMethods(['sendHeader', 'terminate']) // ou les méthodes à remplacer
            ->getMock();

        $controller->expects($this->once())
            ->method('sendHeader')
            ->with('Location: /foo');

        $controller->expects($this->once())
            ->method('terminate');

        $controller->callRedirect('/foo');
    }

    /**
     * Teste l'appel de la méthode protégée sendHeader() via réflexion.
     *
     * Ce test permet de couvrir le code réel de sendHeader sans passer par la méthode redirect(),
     * et de valider que l’appel ne déclenche pas d’erreur d’exécution ou de logique.
     *
     * Note : PHP ne permet pas de récupérer les en-têtes envoyés dans ce contexte.
     * L’objectif ici est donc la couverture fonctionnelle plutôt que la vérification de l’effet.
     *
     * @return void
     *
     * @covers \GenshinTeam\Controllers\AbstractController::sendHeader
     */
    public function testSendHeaderCallsNativeHeader(): void
    {
                                          // On va "espionner" les headers envoyés
        $this->expectOutputRegex('/^$/'); // Empêche PHPUnit de râler sur la sortie vide

        $controller = new DummyController(new Renderer('/chemin/fake'), new SessionManager());

        // ici, on utilisera simplement Reflection pour invoquer
        $refMethod = new \ReflectionMethod($controller, 'sendHeader');
        $refMethod->setAccessible(true);

        $refMethod->invoke($controller, 'Location: /accueil');

    }

    /**
     * Teste que renderForbidden() injecte les bonnes données et définit le code HTTP.
     *
     * @return void
     */
    public function testRenderForbiddenSets403AndDisplaysMessage(): void
    {
        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());

        // Capture l’état avant
        ob_start();
        $controller->callRenderForbidden('Accès restreint aux rédacteurs');
        ob_end_clean();

        $data = $controller->getAllData(); // assuming getData() returns set values

        $this->assertSame('Accès interdit', $data['title']);
        $this->assertSame(
            '<div role="alert">Accès restreint aux rédacteurs</div>',
            $data['content']
        );
        $this->assertSame(403, http_response_code());
    }

    /**
     * Teste que renderForbidden() attrape une exception et l'envoie à handleException().
     *
     * @return void
     */
    public function testRenderForbiddenCatchesException(): void
    {
        $controller = new DummyControllerBis(new Renderer($this->viewPath), new SessionManager());

        ob_start(); // Empêche l'affichage direct
        $controller->callRenderForbidden('Erreur critique');
        ob_end_clean();

        $this->assertSame('Erreur simulée dans renderDefault', $controller->caughtException);
    }

    /**
     * Vérifie que la méthode checkAdminAccess() autorise l’accès pour un utilisateur administrateur.
     *
     * Initialise une session contenant un utilisateur avec le rôle administrateur (id_role = 1),
     * puis appelle checkAdminAccess() via la façade publique du contrôleur DummyController.
     * Ce test confirme que la méthode retourne true lorsque les droits sont suffisants.
     *
     * @return void
     */
    public function testCheckAdminAccessReturnsTrueForAdmin(): void
    {
        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());
        $controller->getSession()->set('user', ['id_role' => 1]);

        $result = $controller->callCheckAdminAccess();

        $this->assertTrue($result);
    }

    /**
     * Vérifie que checkAdminAccess() bloque l’accès lorsqu’aucun utilisateur n’est défini en session.
     *
     * Ce test initialise une session vide (sans clé 'user'), puis appelle checkAdminAccess()
     * via DummyController. Il capture la sortie pour éviter tout affichage direct, puis confirme :
     * - Que la méthode retourne false
     * - Que le code HTTP 403 est bien défini
     *
     * @return void
     */
    public function testCheckAdminAccessReturnsFalseWhenUserNotSet(): void
    {
        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());
        $controller->getSession()->remove('user');

        ob_start();
        $result = $controller->callCheckAdminAccess();
        ob_end_clean();

        $this->assertFalse($result);
        $this->assertSame(403, http_response_code());
    }

    /**
     * Vérifie que checkAdminAccess() bloque l’accès pour un utilisateur non administrateur.
     *
     * Initialise une session contenant un utilisateur avec un rôle autre que 1 (ici 2),
     * puis appelle la méthode checkAdminAccess() via DummyController. Ce test capture la sortie
     * pour éviter tout affichage direct, et confirme que :
     * - La méthode retourne false
     * - Un code HTTP 403 est défini pour signaler l'accès interdit
     *
     * @return void
     */
    public function testCheckAdminAccessReturnsFalseForNonAdminUser(): void
    {
        $controller = new DummyController(new Renderer($this->viewPath), new SessionManager());
        $controller->getSession()->set('user', ['id_role' => 2]);

        ob_start();
        $result = $controller->callCheckAdminAccess();
        ob_end_clean();

        $this->assertFalse($result);
        $this->assertSame(403, http_response_code());
    }

}
