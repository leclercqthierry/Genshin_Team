<?php

declare (strict_types = 1);

use GenshinTeam\Entities\FarmDays;
use PHPUnit\Framework\TestCase;

/**
 * Classe de test unitaire pour l'entité métier FarmDays.
 *
 * Elle vérifie la logique métier liée à la validation des jours de farm, la création d'instances
 * à partir de tableaux ou de chaînes de caractères, et les accesseurs/mutateurs de l'entité.
 * Les cas d'erreur sont également couverts, notamment les entrées invalides ou vides.
 *
 * @covers \GenshinTeam\Entities\FarmDays
 */
class FarmDaysTest extends TestCase
{

    /**
     * Vérifie que les jours valides sont correctement acceptés.
     */
    public function testValidDaysAreAccepted(): void
    {
        $days     = ['Lundi', 'Mercredi'];
        $farmDays = new FarmDays($days, 42);

        $this->assertSame(42, $farmDays->getId());
        $this->assertSame('Lundi/Mercredi', $farmDays->getDays());
        $this->assertSame($days, $farmDays->getDaysArray());
    }

    /**
     * Vérifie qu'une exception est levée avec des jours invalides.
     */
    public function testInvalidDaysThrowException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Jours de farm invalides');

        new FarmDays(['Pizza', 'Jeudi']); // "Pizza" est invalide
    }

    /**
     * Vérifie que fromArray() crée une instance correcte de FarmDays.
     */
    public function testFromArrayCreatesInstance(): void
    {
        $days     = ['Samedi', 'Dimanche'];
        $farmDays = FarmDays::fromArray($days);

        $this->assertInstanceOf(FarmDays::class, $farmDays);
        $this->assertSame('Samedi/Dimanche', $farmDays->getDays());
    }

    /**
     * Vérifie que fromDatabase() crée une instance avec l'identifiant et les jours attendus.
     */
    public function testFromDatabaseCreatesInstanceWithId(): void
    {
        $farmDays = FarmDays::fromDatabase('Mardi/Jeudi', 99);

        $this->assertSame(99, $farmDays->getId());
        $this->assertSame(['Mardi', 'Jeudi'], $farmDays->getDaysArray());
    }

    /**
     * Vérifie que setDays() accepte des valeurs valides et les modifie correctement.
     */
    public function testSetDaysWithValidValues(): void
    {
        $farmDays = FarmDays::fromArray(['Lundi']);
        $farmDays->setDays('Vendredi/Samedi');

        $this->assertSame('Vendredi/Samedi', $farmDays->getDays());
    }

    /**
     * Vérifie que setDays() lève une exception avec des jours invalides.
     */
    public function testSetDaysWithInvalidValuesThrows(): void
    {
        $farmDays = FarmDays::fromArray(['Lundi']);

        $this->expectException(\InvalidArgumentException::class);
        $farmDays->setDays('Funday'); // Jour non valide
    }

    /**
     * Vérifie que le constructeur lève une exception si la liste des jours est vide.
     */
    public function testEmptyDaysArrayIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Jours de farm invalides');

        new FarmDays([], 1); // Tableau vide → devrait échouer
    }

}
