<?php
declare (strict_types = 1);

use GenshinTeam\Renderer\Renderer;
use PHPUnit\Framework\TestCase;

class RendererTest extends TestCase
{
    /**
     * Répertoire temporaire utilisé pour les tests.
     *
     * @var string
     */
    private string $tempDir;

    /**
     * Crée un dossier temporaire et y dépose un fichier de vue pour les tests.
     */
    protected function setUp(): void
    {
        // Créer un dossier temporaire unique
        $this->tempDir = sys_get_temp_dir() . '/renderer_test_' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);

        // Créer une vue de test "test.php" qui va utiliser la variable $name
        $viewContent = '<?php echo "Hello " . $name; ?>';
        file_put_contents($this->tempDir . '/test.php', $viewContent);
    }

    /**
     * Supprime le dossier temporaire et ses fichiers après chaque test.
     */
    protected function tearDown(): void
    {
        // Supprime tous les fichiers dans le dossier temporaire
        $files = glob($this->tempDir . '/*');
        if ($files !== false) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        // Supprime le dossier temporaire
        rmdir($this->tempDir);
    }

    /**
     * Teste que le rendu d'une vue existante retourne le résultat attendu.
     */
    public function testRenderValidView(): void
    {
        $renderer = new Renderer($this->tempDir);
        $output   = $renderer->render('test', ['name' => 'World']);
        $this->assertEquals('Hello World', $output);
    }

    /**
     * Teste que la méthode render() lève une exception si le fichier de vue n'existe pas.
     */
    public function testRenderThrowsExceptionWhenViewNotFound(): void
    {
        $renderer = new Renderer($this->tempDir);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Vue introuvable : " . $this->tempDir . "/nonexistent.php");
        $renderer->render('nonexistent');
    }
}
