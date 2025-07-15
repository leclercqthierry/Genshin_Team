<?php
namespace GenshinTeam\Models;

use GenshinTeam\Entities\FarmDays;

/**
 * Modèle représentant des jours de farm dans l'application.
 *
 * Hérite des fonctionnalités CRUD de la classe AbstractCrudModel.
 * Utilise la table 'zell_farm_days' avec comme identifiant 'id_farm_days' et le champ de nom 'days'.
 */
class FarmDaysModel extends AbstractCrudModel
{
    /**
     * @var string Nom de la table associée au modèle.
     */
    protected string $table = 'zell_farm_days';

    /**
     * @var string Nom du champ identifiant principal.
     */
    protected string $idField = 'id_farm_days';

    /**
     * @var string Nom du champ représentant les jours de farm.
     */
    protected string $nameField = 'days';

    /**
     * Récupère une entité métier FarmDays à partir de son identifiant.
     *
     * Cette méthode utilise les données brutes récupérées via le CRUD générique
     * pour instancier une entité FarmDays et encapsuler la logique métier.
     *
     * @param int $id Identifiant unique en base de l'enregistrement
     * @return FarmDays|null L'entité FarmDays correspondante ou null si introuvable
     */
    public function getFarmDays(int $id): ?FarmDays
    {
        $data = $this->get($id); // retourne array
        if (
            isset($data[$this->nameField], $data[$this->idField]) &&
            is_string($data[$this->nameField]) &&
            is_numeric($data[$this->idField])
        ) {
            return FarmDays::fromDatabase(
                $data[$this->nameField],
                (int) $data[$this->idField]
            );
        }

        return null;
    }

    /**
     * Récupère toutes les entités FarmDays existantes sous forme d'objets métiers.
     *
     * Utilise les résultats du CRUD générique pour instancier chaque ligne
     * en tant qu'objet FarmDays, facilitant une manipulation métier avancée.
     *
     * @return array<int, FarmDays> Tableau d'entités FarmDays
     */
    public function getAllFarmDays(): array
    {
        $rows     = $this->getAll();
        $entities = [];

        foreach ($rows as $row) {
            if (
                isset($row[$this->nameField], $row[$this->idField]) &&
                is_string($row[$this->nameField]) &&
                is_numeric($row[$this->idField])
            ) {
                $entities[] = FarmDays::fromDatabase($row[$this->nameField], (int) $row[$this->idField]);
            }
        }

        return $entities;
    }

}
