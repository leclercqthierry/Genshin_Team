<?php
declare (strict_types = 1);

use GenshinTeam\Models\FarmDays;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \GenshinTeam\Models\FarmDays
 *
 * Classe de test unitaire pour le modèle FarmDays.
 */
class FarmDaysTest extends TestCase
{
    /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject */
    private \PDO $pdo;

    /** @var LoggerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private LoggerInterface $logger;

    /**
     * Configuration initiale pour chaque test : création des mocks.
     */
    protected function setUp(): void
    {
        $this->pdo    = $this->createMock(\PDO::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    /**
     * Teste l'ajout réussi d'un enregistrement dans la base de données.
     */
    public function testAddSuccess(): void
    {
        // Création du mock de la requête préparée
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['days' => 'Lundi/Mardi'])
            ->willReturn(true);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with('INSERT INTO zell_farm_days (days) VALUES (:days)')
            ->willReturn($stmt);

        $model = new FarmDays($this->pdo, $this->logger);
        $this->assertTrue($model->add('Lundi/Mardi'));
    }

    /**
     * Teste l'échec d'un ajout (execute retourne false).
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

        $this->logger->expects($this->once())
            ->method('error');

        $model = new FarmDays($this->pdo, $this->logger);
        $this->assertFalse($model->add('Lundi'));
    }

    /**
     * Teste la gestion d'une exception lors d'une requête préparée.
     */
    public function testAddThrowsException(): void
    {
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willThrowException(new \Exception('DB error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('DB error');

        $model = new FarmDays($this->pdo, $this->logger);
        $this->assertFalse($model->add('Lundi'));
    }

    /**
     * Teste la récupération de tous les enregistrements.
     */
    public function testGetAll(): void
    {
        $expected = [
            ['id_farm_days' => 1, 'days' => 'Lundi'],
            ['id_farm_days' => 2, 'days' => 'Mardi'],
        ];

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn($expected);

        $this->pdo->expects($this->once())
            ->method('query')
            ->with('SELECT * FROM zell_farm_days')
            ->willReturn($stmt);

        $model = new FarmDays($this->pdo, $this->logger);
        $this->assertSame($expected, $model->getAll());
    }

    /**
     * Teste le cas d'un échec de la requête SELECT (retourne false).
     */
    public function testGetAllQueryFailure(): void
    {
        $this->pdo->expects($this->once())
            ->method('query')
            ->with('SELECT * FROM zell_farm_days')
            ->willReturn(false);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Échec de récupération des jours de farm');

        $model = new FarmDays($this->pdo, $this->logger);
        $this->assertSame([], $model->getAll());
    }

    /**
     * Teste la récupération d'un enregistrement par ID (trouvé).
     */
    public function testGetFound(): void
    {
        $expected = ['id_farm_days' => 1, 'days' => 'Lundi'];

        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['id_farm_days' => 1]);
        $stmt->expects($this->once())
            ->method('fetch')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn($expected);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with('SELECT * FROM zell_farm_days WHERE id_farm_days = :id_farm_days')
            ->willReturn($stmt);

        $model = new FarmDays($this->pdo, $this->logger);
        $this->assertSame($expected, $model->get(1));
    }

    /**
     * Teste la récupération d'un enregistrement par ID (non trouvé).
     */
    public function testGetNotFound(): void
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['id_farm_days' => 99]);
        $stmt->expects($this->once())
            ->method('fetch')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn(false);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $model = new FarmDays($this->pdo, $this->logger);
        $this->assertNull($model->get(99));
    }

    /**
     * Teste la mise à jour d'un enregistrement.
     */
    public function testUpdate(): void
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['days' => 'Lundi', 'id_farm_days' => 1])
            ->willReturn(true);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with('UPDATE zell_farm_days SET days = :days WHERE id_farm_days = :id_farm_days')
            ->willReturn($stmt);

        $model = new FarmDays($this->pdo, $this->logger);
        $this->assertTrue($model->update(1, 'Lundi'));
    }

    /**
     * Teste la suppression d'un enregistrement.
     */
    public function testDelete(): void
    {
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['id_farm_days' => 1])
            ->willReturn(true);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with('DELETE FROM zell_farm_days WHERE id_farm_days = :id_farm_days')
            ->willReturn($stmt);

        $model = new FarmDays($this->pdo, $this->logger);
        $this->assertTrue($model->delete(1));
    }
}
