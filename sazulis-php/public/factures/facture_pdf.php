<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
if (!$user || (($user['role'] ?? 'client') !== 'client')) {
    http_response_code(403);
    exit('Acces refuse : vous devez etre connecte pour voir cette facture.');
}

$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
if ($orderId <= 0 && isset($_GET['projet_id'])) {
    // Legacy fallback: some old links used projet_id.
    $orderId = (int) $_GET['projet_id'];
}

if ($orderId <= 0) {
    http_response_code(400);
    exit('Parametre order_id manquant.');
}

$basePath = rtrim((string) ($_ENV['APP_BASE_PATH'] ?? ''), '/');
header('Location: ' . $basePath . '/profile/documents/' . $orderId . '/invoice');
exit;
