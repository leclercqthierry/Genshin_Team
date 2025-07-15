<?php

declare (strict_types = 1);

namespace GenshinTeam\Entities;

/**
 * Représente une statistique.
 *
 * Cette classe encapsule les données d'une statistique sans dépendance technique (pas de PDO, pas de Logger).
 * Elle est idéale pour être utilisée dans le domaine métier ou dans les vues.
 */
class Stat
{
    /**
     * Identifiant unique d'une statistique.
     *
     * @var int
     */
    private int $id;

    /**
     * nom de la statistique.
     *
     * @var string
     */
    private string $name;

    /**
     * Constructeur de la classe Stat.
     *
     * @param string $name nom de la statistique
     * @param int $id Identifiant unique de la statistique (optionnel, par défaut 0)
     * @throws \InvalidArgumentException Si la statistique n'est pas valide
     */
    public function __construct(string $name, int $id = 0)
    {
        $this->id = $id;

        if (! $this->isStatValid($name)) {
            throw new \InvalidArgumentException('Statistique invalide');
        }

        $this->name = $name;

    }

    /**
     * Vérifie si la statistique est valide.
     * Elle doit avoir au moins 2 caractères et être composée de lettres, chiffres, espaces, % ou + uniquement.
     *
     * @param string $stat statistique à valider
     * @return bool True si la statistique est validée, sinon false
     */
    private function isStatValid(string $stat): bool
    {
        if (strlen($stat) < 2) {
            return false;
        }

        if (preg_match('/^[\w\s%+]+$/u', $stat) !== 1) {
            return false;
        }

        return true;
    }

    /**
     * Récupère l'identifiant unique de la statistique.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Récupère la statistique.
     *
     * @return string
     */
    public function getStat(): string
    {
        return $this->name;
    }

    /**
     * Définit une statistique.
     *
     * @param string $stat Statistique
     */
    public function setStat(string $stat): void
    {
        if (! $this->isStatValid($stat)) {
            throw new \InvalidArgumentException('Statistique invalide');
        }

        $this->name = $stat;
    }
}
