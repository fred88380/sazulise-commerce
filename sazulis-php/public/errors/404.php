<?php

declare(strict_types=1);

$statusCode = 404;
$title = 'Page introuvable';
$message = 'La page demandee n existe pas ou a ete deplacee.';
$hint = 'Verifie l URL ou passe par le menu principal pour continuer ta navigation.';

require __DIR__ . '/template.php';
