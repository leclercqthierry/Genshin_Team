<?php
/** @var array<int, array{id_stat: int, name: string}> $stat */

$title        = 'Liste des statistiques';
$items        = $stat;
$nameField    = 'name';
$idField      = 'id_stat';
$emptyMessage = 'Aucune statistique enregistrée.';

include __DIR__ . '/../partials/item-list.php';
