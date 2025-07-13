<?php
declare (strict_types = 1);

// Charge l’autoloader de Composer
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/constants.php';

// Définition éventuelle d'autres constantes ou setup global
putenv('APP_ENV=test');
