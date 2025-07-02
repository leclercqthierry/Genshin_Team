<?php

$title     = 'Confirmer la suppression';
$itemLabel = 'jour de farm';

/** @var array{days: string} $farmDay */
$itemName  = $farmDay['days'];
$action    = 'delete-farm-days';
$fieldName = 'delete_id';
$cancelUrl = 'delete-farm-days';

include __DIR__ . '/../partials/delete-confirm.php';
