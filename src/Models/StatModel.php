<?php
namespace GenshinTeam\Models;

/**
 * Modèle représentant une statistique utilisée dans l'application.
 *
 * Hérite des fonctionnalités CRUD de la classe AbstractCrudModel.
 * Utilise la table 'zell_stats' avec l'identifiant 'id_stat' et le champ de nom 'name'.
 */
class StatModel extends AbstractCrudModel
{
    /**
     * @var string Nom de la table associée au modèle.
     */
    protected string $table = 'zell_stats';

    /**
     * @var string Nom du champ identifiant principal.
     */
    protected string $idField = 'id_stat';

    /**
     * @var string Nom du champ représentant le nom de la statistique.
     */
    protected string $nameField = 'name';
}
