<?php

declare(strict_types=1);

$statusCode = 403;
$title = 'Acces refuse';
$message = 'Tu n as pas l autorisation d acceder a cette ressource.';
$hint = 'Connecte toi avec un compte autorise ou contacte le support Sazulis.';

require __DIR__ . '/error_template.php';
