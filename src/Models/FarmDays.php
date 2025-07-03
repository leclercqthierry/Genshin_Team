<?php
namespace GenshinTeam\Models;

/**
 * Modèle représentant des jours de farm dans l'application.
 *
 * Hérite des fonctionnalités CRUD de la classe AbstractCrudModel.
 * Utilise la table 'zell_farm_days' avec comme identifiant 'id_farm_days' et le champ de nom 'days'.
 */
class FarmDays extends AbstractCrudModel
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
}
