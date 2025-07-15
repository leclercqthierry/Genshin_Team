<?php

declare (strict_types = 1);

namespace GenshinTeam\Entities;

/**
 * Représente un moyen d'obtention.
 *
 * Cette classe encapsule les données d'un moyen d'obtention sans dépendance technique (pas de PDO, pas de Logger).
 * Elle est idéale pour être utilisée dans le domaine métier ou dans les vues.
 */
class Obtaining
{
    /**
     * Identifiant unique d'un moyen d'obtention.
     *
     * @var int
     */
    private int $id;

    /**
     * nom du moyen d'obtention.
     *
     * @var string
     */
    private string $name;

    /**
     * Constructeur de la classe Obtaining.
     *
     * @param string $name nom du moyen d'obtention
     * @param int $id Identifiant unique du moyen d'obtention (optionnel, par défaut 0)
     * @throws \InvalidArgumentException Si le moyen d'obtention n'est pas valide
     */
    public function __construct(string $name, int $id = 0)
    {
        $this->id = $id;

        if (! $this->isObtainingValid($name)) {
            throw new \InvalidArgumentException('Moyen d\'obtention invalide');
        }

        $this->name = $name;

    }

    /**
     * Vérifie si le moyen d'obtention est valide.
     * Elle doit avoir au moins 4 caractères et être composée de lettres (et accent) et espaces uniquement.
     *
     * @param string $obtaining moyen d'obtention à valider
     * @return bool True si le moyen d'obtention est validée, sinon false
     */
    private function isObtainingValid(string $obtaining): bool
    {
        if (strlen($obtaining) < 4) {
            return false;
        }

        if (preg_match('/^[\p{L}\s]+$/u', $obtaining) !== 1) {
            return false;
        }

        return true;
    }

    /**
     * Récupère l'identifiant unique du moyen d'obtention.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Récupère le moyen d'obtention.
     *
     * @return string
     */
    public function getObtaining(): string
    {
        return $this->name;
    }

    /**
     * Définit un moyen d'obtention.
     *
     * @param string $obtaining Moyen d'obtention
     */
    public function setObtaining(string $obtaining): void
    {
        if (! $this->isObtainingValid($obtaining)) {
            throw new \InvalidArgumentException('Moyen d\'obtention invalide');
        }

        $this->name = $obtaining;
    }
}
