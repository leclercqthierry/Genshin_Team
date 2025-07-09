<?php
declare (strict_types = 1);

use GenshinTeam\Models\AbstractCrudModel;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/** Classe factice de test héritant d'AbstractCrudModel pour simuler une table fictive.
 *
 */
class DummyModel extends AbstractCrudModel
{
    protected string $table     = 'dummy_table';
    protected string $idField   = 'id_dummy';
    protected string $nameField = 'dummy_name';
}

/**
 * @covers \GenshinTeam\Models\AbstractCrudModel
 */
class AbstractCrudModelTest extends TestCase
{
    /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject */
    private \PDO $pdo;

    /** @var LoggerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private LoggerInterface $logger;

    /**
     * Initialise les mocks pour PDO et le logger avant chaque test.
     */
    protected function setUp(): void
    {
        $this->pdo    = $this->createMock(\PDO::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    /**
     * Vérifie que la méthode add() retourne true en cas de succès.
     */
    public function testAddSuccess(): void
    {
        // Préparation du statement pour une insertion réussie
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['name' => 'TestName'])
            ->willReturn(true);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with('INSERT INTO dummy_table (dummy_name) VALUES (:name)')
            ->willReturn($stmt);

        $model = new DummyModel($this->pdo, $this->logger);
        $this->assertTrue($model->add('TestName'));
    }

    /**
     * Vérifie que add() retourne false en cas d'échec d'exécution SQL.
     */
    public function testAddFailure(): void
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->willReturn(false);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $model = new DummyModel($this->pdo, $this->logger);
        $this->assertFalse($model->add('TestName'));
    }

    /**
     * Vérifie que getAll() retourne correctement les lignes fetchées.
     */
    public function testGetAll(): void
    {
        $expected = [
            ['id_dummy' => 1, 'dummy_name' => 'A'],
            ['id_dummy' => 2, 'dummy_name' => 'B'],
        ];

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn($expected);

        $this->pdo->expects($this->once())
            ->method('query')
            ->with('SELECT * FROM dummy_table ORDER BY dummy_name')
            ->willReturn($stmt);

        $model = new DummyModel($this->pdo, $this->logger);
        $this->assertSame($expected, $model->getAll());
    }

    /**
     * Vérifie que getAll() lève une RuntimeException si la requête SQL échoue.
     *
     * Ce test simule un retour `false` de la méthode `PDO::query()` afin de déclencher
     * l'exception prévue dans le modèle. Il s'assure également que le message d'erreur
     * correspond à celui défini dans le code.
     *
     * @covers \GenshinTeam\Models\AbstractCrudModel::getAll
     */
    public function testGetAllThrowsRuntimeExceptionOnFailure(): void
    {
        $this->pdo->expects($this->once())
            ->method('query')
            ->willReturn(false);

        $model = new DummyModel($this->pdo, $this->logger);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Erreur lors de la requête SQL");

        $model->getAll();
    }

    /**
     * Vérifie que get() retourne un enregistrement trouvé.
     */
    public function testGetFound(): void
    {
        $expected = ['id_dummy' => 1, 'dummy_name' => 'A'];

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with(['id' => 1]);
        $stmt->expects($this->once())->method('fetch')->willReturn($expected);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $model = new DummyModel($this->pdo, $this->logger);
        $this->assertSame($expected, $model->get(1));
    }

    /**
     * Vérifie que get() retourne null si aucun enregistrement n’est trouvé.
     */
    public function testGetNotFound(): void
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with(['id' => 99]);
        $stmt->expects($this->once())->method('fetch')->willReturn(false);

        $this->pdo->expects($this->once())->method('prepare')->willReturn($stmt);

        $model = new DummyModel($this->pdo, $this->logger);
        $this->assertNull($model->get(99));
    }

    /**
     * Vérifie que update() retourne true quand l’exécution SQL réussit.
     */
    public function testUpdate(): void
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['name' => 'A', 'id' => 1])
            ->willReturn(true);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $model = new DummyModel($this->pdo, $this->logger);
        $this->assertTrue($model->update(1, 'A'));
    }

    /**
     * Vérifie que delete() retourne true si la suppression réussit.
     */
    public function testDelete(): void
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['id' => 1])
            ->willReturn(true);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $model = new DummyModel($this->pdo, $this->logger);
        $this->assertTrue($model->delete(1));
    }

    /**
     * Vérifie que la méthode existsByName() retourne true lorsque le nom existe en base.
     *
     * Ce test simule une exécution réussie de la requête préparée sur la base de données
     * et un résultat non nul pour fetchColumn() (équivalent à une ligne trouvée).
     *
     * Il s'assure également que :
     * - le nom est correctement lié en paramètre :name
     * - la requête SQL attendue est utilisée avec prepare()
     * - le modèle DummyModel fonctionne comme prévu dans ce cas
     *
     * @return void
     */
    public function testExistsByNameReturnsTrueIfFound(): void
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['name' => 'foo']);
        $stmt->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(1);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT 1 FROM dummy_table WHERE dummy_name = :name LIMIT 1')
            ->willReturn($stmt);

        $model = new DummyModel($this->pdo, $this->logger);
        $this->assertTrue($model->existsByName('foo'));
    }

    /**
     * Vérifie que la méthode existsByName() retourne false lorsque le nom n’existe pas dans la base de données.
     *
     * Ce test simule une requête SQL SELECT avec le nom 'bar' et un retour de fetchColumn() à false,
     * ce qui correspond à un résultat vide. Il s’assure que :
     * - la requête SQL attendue est bien préparée,
     * - les bons paramètres sont liés,
     * - la méthode existeByName() se comporte correctement en absence de correspondance.
     *
     * @return void
     */
    public function testExistsByNameReturnsFalseIfNotFound(): void
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['name' => 'bar']);
        $stmt->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(false);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT 1 FROM dummy_table WHERE dummy_name = :name LIMIT 1')
            ->willReturn($stmt);

        $model = new DummyModel($this->pdo, $this->logger);
        $this->assertFalse($model->existsByName('bar'));
    }
}
