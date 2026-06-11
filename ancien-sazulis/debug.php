<?php
require __DIR__ . '/vendor/autoload.php';
$client = new \GuzzleHttp\Client();
try {
    $res = $client->request('GET', 'https://www.google.com');
    echo "Connexion OK ! Code : " . $res->getStatusCode();
} catch (\Exception $e) {
    echo "BLOCAGE HÉBERGEUR : " . $e->getMessage();
}