<?php
/** @var array<int, array{id_stat: int, name: string}> $stats */

$title        = 'Liste des statistiques';
$items        = $stats;
$nameField    = 'name';
$idField      = 'id_stat';
$emptyMessage = 'Aucune statistique enregistrée.';

include __DIR__ . '/../partials/item-list.php';
