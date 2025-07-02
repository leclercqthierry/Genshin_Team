<?php

$title = 'Liste des jours de farm';

/** @var array<int, array{id_farm_days: int, days: string}> $farmDays */
$items        = $farmDays;
$nameField    = 'days';
$idField      = 'id_farm_days';
$emptyMessage = 'Aucun jour de farm enregistré.';

include __DIR__ . '/../partials/item-list.php';
