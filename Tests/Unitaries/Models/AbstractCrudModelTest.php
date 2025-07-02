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

        $this->logger->expects($this->once())->method('error');

        $model = new DummyModel($this->pdo, $this->logger);
        $this->assertFalse($model->add('TestName'));
    }

    /**
     * Vérifie que add() gère proprement une exception PDO et logue l’erreur.
     */
    public function testAddThrowsException(): void
    {
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willThrowException(new \Exception('DB error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('DB error');

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
     * Vérifie que getAll() retourne un tableau vide en cas d’échec SQL.
     */
    public function testGetAllQueryFailure(): void
    {
        $this->pdo->expects($this->once())
            ->method('query')
            ->willReturn(false);

        $this->logger->expects($this->once())
            ->method('error')
            ->with("Échec de récupération des données dans dummy_table");

        $model = new DummyModel($this->pdo, $this->logger);
        $this->assertSame([], $model->getAll());
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
}
