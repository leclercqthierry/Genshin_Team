<?php

$title = 'Liste des moyens d\'obtention';

/** @var array<int, array{id_obtaining: int, name: string}> $obtainings */
$items        = $obtainings;
$nameField    = 'name';
$idField      = 'id_obtaining';
$emptyMessage = 'Aucun moyen d\'obtention enregistré.';

include __DIR__ . '/../partials/item-list.php';
