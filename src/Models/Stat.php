<?php
namespace GenshinTeam\Models;

class Stat extends AbstractCrudModel
{
    protected string $table     = 'zell_stats';
    protected string $idField   = 'id_stat';
    protected string $nameField = 'name';
}
