<?php
namespace GenshinTeam\Models;

/**
 * Modèle représentant un mode d'obtention dans l'application.
 *
 * Cette classe hérite des fonctionnalités CRUD génériques de AbstractCrudModel.
 * Elle est associée à la table 'zell_obtaining', avec l'identifiant principal 'id_obtaining'
 * et le champ 'name' pour désigner le nom de l'obtention.
 */
class ObtainingModel extends AbstractCrudModel
{
    /**
     * @var string Nom de la table associée au modèle.
     */
    protected string $table = 'zell_obtaining';

    /**
     * @var string Nom du champ identifiant principal.
     */
    protected string $idField = 'id_obtaining';

    /**
     * @var string Nom du champ représentant le nom de l’obtention.
     */
    protected string $nameField = 'name';
}
