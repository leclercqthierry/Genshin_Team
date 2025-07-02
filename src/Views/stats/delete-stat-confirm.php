<?php
/**
 * @var array{name: string} $stat
 * @var int|string          $id
 */

$title     = 'Confirmer la suppression';
$itemLabel = 'statistique';
$itemName  = $stat['name'];
$action    = 'delete-stat';
$fieldName = 'delete_id';
$cancelUrl = 'delete-stat';

include __DIR__ . '/../partials/delete-confirm.php';
