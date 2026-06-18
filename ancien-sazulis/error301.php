<?php

declare(strict_types=1);

$statusCode = 301;
$title = 'Ressource deplacee';
$message = 'Cette ressource a ete deplacee de facon permanente vers une nouvelle adresse.';
$hint = 'Mets a jour tes favoris avec la nouvelle URL pour eviter les redirections.';

require __DIR__ . '/error_template.php';
