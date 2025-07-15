<?php

declare (strict_types = 1);

use GenshinTeam\Entities\FarmDays;
use GenshinTeam\Models\FarmDaysModel;
use Psr\Log\NullLogger;
use Tests\TestCase\DatabaseTestCase;

/**
 * Classe de test pour le modèle FarmDaysModel.
 *
 * Cette classe s'appuie sur une base SQLite en mémoire pour tester les fonctionnalités
 * de persistance et de récupération des entités métier FarmDays. Elle vérifie notamment :
 * - la création correcte d'une instance de FarmDaysModel avec la table nécessaire ;
 * - la récupération d'une entité FarmDays par ID ;
 * - la récupération de toutes les entités FarmDays stockées ;
 * - le comportement attendu lorsque l'ID recherché est inexistant.
 *
 * Les tests assurent que le modèle respecte la structure attendue
 * et que les valeurs manipulées sont valides selon les règles métiers.
 *
 * @covers \GenshinTeam\Models\FarmDaysModel
 */
class FarmDaysModelTest extends DatabaseTestCase
{

/**
 * Crée une instance de FarmDaysModel avec une table SQLite mémoire pour les tests.
 *
 * @return FarmDaysModel
 */
    protected function createFarmDaysModel(): FarmDaysModel
    {
        $this->pdo->exec("
        CREATE TABLE IF NOT EXISTS zell_farm_days (
            id_farm_days INTEGER PRIMARY KEY AUTOINCREMENT,
            days TEXT NOT NULL
        )
    ");

        return new FarmDaysModel($this->pdo, new NullLogger());
    }

    /**
     * Vérifie que la méthode getFarmDays retourne une instance valide de FarmDays.
     *
     * Ce test insère un enregistrement simulé, puis récupère l'entité métier FarmDays
     * correspondante via la méthode getFarmDays() et compare les valeurs attendues.
     *
     * @return void
     */
    public function testGetFarmDaysReturnsEntity(): void
    {
                                                      // Arrange
        $model        = $this->createFarmDaysModel(); // méthode qui instancie le modèle avec une BDD de test
        $expectedId   = 1;
        $expectedDays = 'Lundi/Mardi';

        // Insère manuellement un enregistrement (via PDO direct ou méthode add())
        $model->add($expectedDays);

        // Act
        $entity = $model->getFarmDays($expectedId);

        // Assert
        $this->assertInstanceOf(FarmDays::class, $entity);
        $this->assertSame($expectedId, $entity->getId());
        $this->assertSame($expectedDays, $entity->getDays());
    }

    /**
     * Vérifie que getAllFarmDays retourne un tableau d'objets FarmDays valides.
     *
     * Ce test insère plusieurs enregistrements dans la base de test,
     * puis s'assure que chaque élément retourné par getAllFarmDays() :
     * - est une instance de FarmDays
     * - contient des jours valides au format attendu ("Jour/Jour/...")
     *
     * @return void
     */
    public function testGetAllFarmDaysReturnsEntityArray(): void
    {
        // Arrange
        $model = $this->createFarmDaysModel();
        $model->add('Mercredi/Jeudi');
        $model->add('Samedi');

        // Act
        $entities = $model->getAllFarmDays();

        // Assert
        $this->assertNotEmpty($entities);

        foreach ($entities as $entity) {
            $this->assertInstanceOf(FarmDays::class, $entity);
            $this->assertMatchesRegularExpression('/^(Lundi|Mardi|Mercredi|Jeudi|Vendredi|Samedi|Dimanche)(\/(Lundi|Mardi|Mercredi|Jeudi|Vendredi|Samedi|Dimanche))*$/', $entity->getDays());
        }
    }

    /**
     * Vérifie que la méthode getFarmDays retourne null lorsque l'identifiant est introuvable.
     *
     * Ce test simule une récupération d'entité FarmDays avec un ID inexistant dans la base de données.
     * Il s'assure que la méthode retourne bien `null`, conformément à la logique métier attendue.
     *
     * @return void
     */
    public function testGetFarmDaysReturnsNullIfNotFound(): void
    {
        $model = $this->createFarmDaysModel();

        // Ne pas insérer de données → ID inexistant
        $result = $model->getFarmDays(999);

        $this->assertNull($result);
    }
}
