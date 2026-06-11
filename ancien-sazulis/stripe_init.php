<?php
require __DIR__ . '/vendor/autoload.php';

// Chargement des variables d'environnement depuis .env
if (file_exists(__DIR__ . '/.env')) {
	$lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	foreach ($lines as $line) {
		if (strpos(trim($line), '#') === 0) continue;
		if (strpos($line, '=') === false) continue;
		list($name, $value) = explode('=', $line, 2);
		$_ENV[trim($name)] = trim($value);
	}
}

if (!empty($_ENV['STRIPE_SECRET_KEY'])) {
	\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
} else {
	throw new Exception('Clé secrète Stripe manquante dans .env');
}