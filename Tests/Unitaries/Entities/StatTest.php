<?php

declare (strict_types = 1);

use GenshinTeam\Entities\Stat;
use PHPUnit\Framework\TestCase;

class StatTest extends TestCase
{
    /**
     * Vérifie que la statistique valide est acceptée par le constructeur.
     */
    public function testValidStatIsAccepted(): void
    {
        $stat = new Stat('ATK %', 1);

        $this->assertSame(1, $stat->getId());
        $this->assertSame('ATK %', $stat->getStat());
    }

    /**
     * Vérifie qu'une statistique trop courte déclenche une exception.
     */
    public function testTooShortStatThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Stat('A'); // 1 caractère → invalide
    }

    /**
     * Vérifie qu'une statistique avec des caractères invalides déclenche une exception.
     */
    public function testStatWithSpecialCharactersThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Stat('ATK#'); // # interdit
    }

    /**
     * Vérifie que setStat accepte une valeur valide.
     */
    public function testSetStatWithValidValue(): void
    {
        $stat = new Stat('HP +');
        $stat->setStat('DEF %');

        $this->assertSame('DEF %', $stat->getStat());
    }

    /**
     * Vérifie que setStat rejette une valeur invalide.
     */
    public function testSetStatWithInvalidValueThrowsException(): void
    {
        $stat = new Stat('Crit Rate');

        $this->expectException(\InvalidArgumentException::class);
        $stat->setStat('!!!'); // caractères spéciaux → invalide
    }

    /**
     * Vérifie qu'une statistique vide est rejetée.
     */
    public function testEmptyStatThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Stat('');
    }

    /**
     * Vérifie qu'une statistique contenant des chiffres est acceptée.
     */
    public function testStatWithDigitsIsValid(): void
    {
        $stat = new Stat('Vitesse 100');

        $this->assertSame('Vitesse 100', $stat->getStat());
    }
}
