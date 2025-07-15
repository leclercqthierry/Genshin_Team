<?php

declare (strict_types = 1);

use GenshinTeam\Entities\User;
use PHPUnit\Framework\TestCase;

/**
 * Teste le fonctionnement de l'entité User.
 *
 * Vérifie que les accesseurs renvoient bien les valeurs attendues
 * et que la méthode de construction via tableau fonctionne correctement.
 *
 */

class UserTest extends TestCase
{
    /**
     * Teste la construction manuelle d'un User avec un rôle explicite.
     *
     * @covers ::__construct
     * @covers ::getId
     * @covers ::getNickname
     * @covers ::getEmail
     * @covers ::getPassword
     * @covers ::getRole
     */
    public function testUserConstruction(): void
    {
        $user = new User(1, 'Jean', 'jean@example.com', 'pass123', 1);

        $this->assertSame(1, $user->getId());
        $this->assertSame('Jean', $user->getNickname());
        $this->assertSame('jean@example.com', $user->getEmail());
        $this->assertSame('pass123', $user->getPassword());
        $this->assertSame(1, $user->getRole());
    }

    /**
     * Teste la construction d'un User sans fournir de rôle (valeur par défaut).
     *
     * @covers ::__construct
     * @covers ::getRole
     */
    public function testDefaultRole(): void
    {
        $user = new User(2, 'Claire', 'claire@example.com', 'securePass');

        $this->assertSame(2, $user->getRole()); // rôle par défaut
    }

    /**
     * Teste la création d'un User à partir d'un tableau associatif.
     *
     * @covers ::fromArray
     */
    public function testFromArray(): void
    {
        $data = [
            'id_user'  => 3,
            'nickname' => 'Alix',
            'email'    => 'alix@example.com',
            'password' => 'secret',
            'id_role'  => 2,
        ];

        $user = User::fromDatabase($data);

        $this->assertSame(3, $user->getId());
        $this->assertSame('Alix', $user->getNickname());
        $this->assertSame('alix@example.com', $user->getEmail());
        $this->assertSame('secret', $user->getPassword());
        $this->assertSame(2, $user->getRole());
    }
}
