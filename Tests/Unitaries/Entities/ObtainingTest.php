<?php

declare (strict_types = 1);

use GenshinTeam\Entities\Obtaining;
use PHPUnit\Framework\TestCase;

class ObtainingTest extends TestCase
{
    /**
     * Vérifie que le constructeur accepte un nom valide.
     */
    public function testValidObtainingIsAccepted(): void
    {
        $Obtaining = new Obtaining('Récompense hebdomadaire', 123);

        $this->assertSame(123, $Obtaining->getId());
        $this->assertSame('Récompense hebdomadaire', $Obtaining->getObtaining());
    }

    /**
     * Vérifie qu'une chaîne trop courte déclenche une exception.
     */
    public function testShortObtainingThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Obtaining('Feu'); // 3 caractères → invalide
    }

    /**
     * Vérifie que des caractères spéciaux déclenchent une exception.
     */
    public function testObtainingWithSpecialCharactersThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Obtaining('Loot#boss'); // caractère non autorisé
    }

    /**
     * Vérifie que des chiffres déclenchent une exception.
     */
    public function testObtainingWithNumbersThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Obtaining('Étape 2');
    }

    /**
     * Vérifie que setObtaining accepte une nouvelle valeur valide.
     */
    public function testSetObtainingWithValidValue(): void
    {
        $Obtaining = new Obtaining('Défi quotidien');
        $Obtaining->setObtaining('Mission spéciale');

        $this->assertSame('Mission spéciale', $Obtaining->getObtaining());
    }

    /**
     * Vérifie que setObtaining rejette une valeur invalide.
     */
    public function testSetObtainingWithInvalidValueThrowsException(): void
    {
        $Obtaining = new Obtaining('Récompense de quête');

        $this->expectException(\InvalidArgumentException::class);
        $Obtaining->setObtaining('1234'); // contient des chiffres → invalide
    }

    /**
     * Vérifie qu'une chaîne vide déclenche une exception.
     */
    public function testEmptyObtainingThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Obtaining('');
    }
}
