<?php

declare (strict_types = 1);

namespace GenshinTeam\Entities;

/**
 * Représente les jours de farm.
 *
 * Cette classe encapsule les données des jours de farm sans dépendance technique (pas de PDO, pas de Logger).
 * Elle est idéale pour être utilisée dans le domaine métier ou dans les vues.
 */
class FarmDays
{
    /**
     * Identifiant unique des jours de farm.
     *
     * @var int
     */
    private int $id;

    /**
     * Jours de farm.
     *
     * @var string
     */
    private string $daysString;

    /**
     * Jours de farm valides.
     *
     * @var array<int, string>
     */
    private const VALID_DAYS = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

    /**
     * Constructeur de la classe FarmDays.
     *
     * @param array<int, string> $daysList Liste des jours de farm
     * @param int $id Identifiant unique des jours de farm (optionnel, par défaut 0)
     * @throws \InvalidArgumentException Si les jours de farm ne sont pas valides
     */
    public function __construct(array $daysList, int $id = 0)
    {
        $this->id = $id;

        if (! $this->areDaysValid($daysList)) {
            throw new \InvalidArgumentException('Jours de farm invalides');
        }

        $this->daysString = implode('/', $daysList);

    }

    /**
     * Crée une instance de FarmDays à partir d'un tableau de jours.
     *
     * @param array<int, string> $days Liste des jours de farm
     * @return self
     */
    public static function fromArray(array $days): self
    {
        return new self($days); // ID temporaire ou nul
    }

    /**
     * Crée une instance de FarmDays à partir d'une chaîne de caractères provenant de la base de données.
     *
     * @param string $daysString Chaîne de jours de farm (format "Lundi/Mardi/.../Dimanche")
     * @param int $id Identifiant unique des jours de farm
     * @return self
     */
    public static function fromDatabase(string $daysString, int $id): self
    {
        $daysArray = explode('/', $daysString);
        return new self($daysArray, $id);
    }

    /**
     * Vérifie si les jours de farm sont valides.
     *
     * @param array<int, string> $days Liste des jours à valider
     * @return bool True si les jours sont valides, sinon false
     */
    private function areDaysValid(array $days): bool
    {
        if (empty($days)) {
            return false;
        }

        foreach ($days as $day) {
            if (! in_array(ucfirst(strtolower($day)), self::VALID_DAYS, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Récupère l'identifiant unique des jours de farm.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Récupère les jours de farm.
     *
     * @return string
     */
    public function getDays(): string
    {
        return $this->daysString;
    }

    /**
     * Définit les jours de farm.
     *
     * @param string $days Jours de farm
     */
    public function setDays(string $days): void
    {
        $daysArray = explode('/', $days);
        if (! $this->areDaysValid($daysArray)) {
            throw new \InvalidArgumentException('Jours de farm invalides');
        }

        $this->daysString = implode('/', $daysArray);
    }

    /**
     * Récupère les jours de farm sous forme de tableau.
     *
     * @return array<int, string>
     */
    public function getDaysArray(): array
    {
        return array_map('trim', explode('/', $this->daysString));
    }
}
