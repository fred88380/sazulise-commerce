<?php

declare(strict_types=1);

$statusCode = 500;
$title = 'Erreur interne';
$message = 'Une erreur technique est survenue pendant le traitement de la requete.';
$hint = 'Reessaie dans quelques instants. Si le probleme persiste, contacte le support.';

require __DIR__ . '/error_template.php';
