<?php

$title     = 'Confirmer la suppression';
$itemLabel = 'moyen d\'obtention';

/** @var array{name: string} $obtaining */
$itemName  = $obtaining['name'];
$action    = 'delete-obtaining';
$fieldName = 'delete_id';
$cancelUrl = 'delete-obtaining';

include __DIR__ . '/../partials/delete-confirm.php';
