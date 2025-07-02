<?php
namespace GenshinTeam\Models;

class FarmDays extends AbstractCrudModel
{
    protected string $table     = 'zell_farm_days';
    protected string $idField   = 'id_farm_days';
    protected string $nameField = 'days';
}
