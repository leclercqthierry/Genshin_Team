<?php
declare (strict_types = 1);

use GenshinTeam\Renderer\Renderer;
use PHPUnit\Framework\TestCase;

/**
 * Teste le moteur de rendu Renderer : affichage de vue existante
 * et gestion des erreurs en cas de fichier manquant.
 *
 * @covers \GenshinTeam\Renderer\Renderer
 */
class RendererTest extends TestCase
{
    /**
     * Répertoire temporaire contenant les fichiers de vue pour le test.
     *
     * @var string
     */
    private string $tempDir;

    /**
     * Crée un environnement de test avec un fichier de vue fictif.
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Crée un dossier temporaire unique pour cette exécution
        $this->tempDir = sys_get_temp_dir() . '/renderer_test_' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);

        // Vue de test contenant une simple interpolation PHP avec $name
        $viewContent = '<?php echo "Hello " . $name; ?>';
        file_put_contents($this->tempDir . '/test.php', $viewContent);
    }

    /**
     * Supprime les fichiers temporaires et nettoie le répertoire de test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // Supprime les fichiers créés dans le dossier temporaire
        $files = glob($this->tempDir . '/*');
        if ($files !== false) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        // Supprime le dossier une fois vide
        rmdir($this->tempDir);
    }

    /**
     * Vérifie que render() retourne bien le contenu attendu
     * lorsqu'une vue valide est fournie.
     *
     * @return void
     */
    public function testRenderValidView(): void
    {
        $renderer = new Renderer($this->tempDir);

        // Appelle render() sur la vue "test.php" en injectant $name = "World"
        $output = $renderer->render('test', ['name' => 'World']);

        // Vérifie que la sortie correspond exactement à "Hello World"
        $this->assertSame('Hello World', $output);
    }

    /**
     * Vérifie que render() lève une exception si la vue est absente.
     *
     * @return void
     */
    public function testRenderThrowsExceptionWhenViewNotFound(): void
    {
        $renderer = new Renderer($this->tempDir);

        // On attend une Exception spécifique au rendu manquant
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Vue introuvable : " . $this->tempDir . "/nonexistent.php");

        $renderer->render('nonexistent');
    }
}
