<?php
// backend/paypal_create_order.php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';   // session + lang (pas d'HTML)
require_once __DIR__ . '/../paypal_init.php'; // charge .env PayPal + variables

header('Content-Type: application/json; charset=utf-8');

// ---- Config sanity
if (empty($paypalClientId) || empty($paypalSecret)) {
    http_response_code(500);
    echo json_encode([
        'error_code' => 'paypal_not_configured',
        'error' => 'PayPal non configuré : PAYPAL_CLIENT_ID et/ou PAYPAL_SECRET manquant dans .env'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---- Utils
function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function read_json_body(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

// ---- Auth (API, donc pas de redirect)
if (empty($_SESSION['user_id'])) {
    json_out(['error_code' => 'unauthorized', 'error' => 'unauthorized'], 401);
}
$userId = (int)$_SESSION['user_id'];

// ---- Input
$payload = read_json_body();
$projectId = isset($payload['project_id']) ? (int)$payload['project_id'] : 0;
$paymentType = isset($payload['payment_type']) ? (string)$payload['payment_type'] : 'acompte';
if (!in_array($paymentType, ['acompte', 'solde'], true)) $paymentType = 'acompte';

if ($projectId <= 0) {
    json_out(['error_code' => 'invalid_project_id', 'error' => 'invalid_project_id'], 400);
}

// ---- DB (montant côté serveur)
require_once __DIR__ . '/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    json_out(['error_code' => 'db_unavailable', 'error' => 'db_unavailable'], 500);
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

try {
    $stmt = $pdo->prepare("SELECT * FROM projets WHERE id = ? AND id_utilisateur = ? LIMIT 1");
    $stmt->execute([$projectId, $userId]);
    $projet = $stmt->fetch();
    if (!$projet) {
        json_out(['error_code' => 'project_not_found', 'error' => 'project_not_found'], 404);
    }

    $total   = (float)($projet['total'] ?? 0);
    $acompte = (float)($projet['acompte'] ?? 0);

    // si solde existe en DB on le prend, sinon total - acompte
    $solde = isset($projet['solde']) ? (float)$projet['solde'] : max(0, $total - $acompte);

    // flags éventuels
    $acompteRecu = (int)($projet['acompte_recu'] ?? 0);
    $soldeRecu   = (int)($projet['solde_recu'] ?? 0);

    if ($paymentType === 'acompte') {
        if ($acompteRecu === 1 || $acompte <= 0) {
            json_out(['error_code' => 'acompte_already_paid', 'error' => 'acompte_already_paid'], 409);
        }
        $amount = $acompte;
        $label = "Acompte projet #{$projectId}";
    } else {
        if ($soldeRecu === 1 || $solde <= 0) {
            json_out(['error_code' => 'solde_already_paid', 'error' => 'solde_already_paid'], 409);
        }
        $amount = $solde;
        $label = "Solde projet #{$projectId}";
    }

    // arrondi 2 décimales
    $amount = round($amount + 1e-9, 2);
    if ($amount <= 0) {
        json_out(['error_code' => 'invalid_amount', 'error' => 'invalid_amount'], 400);
    }
} catch (Throwable $e) {
    json_out(['error_code' => 'db_error', 'error' => 'db_error'], 500);
}

// ---- PayPal config (depuis paypal_init.php)
$clientId = $paypalClientId;
$secret   = $paypalSecret;
$baseUrl  = $paypalApiBase;

// ---- 1) Access token
$ch = curl_init($baseUrl . '/v1/oauth2/token');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_USERPWD => $clientId . ':' . $secret,
    CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
    CURLOPT_HTTPHEADER => ['Accept: application/json', 'Accept-Language: fr_FR'],
]);
$tokenRaw = curl_exec($ch);
$tokenHttp = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$tokenErr = curl_error($ch);
curl_close($ch);

if ($tokenRaw === false || $tokenHttp < 200 || $tokenHttp >= 300) {
    json_out([
        'error_code' => 'paypal_token_error',
        'error' => 'paypal_token_error',
        'details' => $tokenErr ?: $tokenRaw
    ], 502);
}
$tokenJson = json_decode((string)$tokenRaw, true);
$accessToken = $tokenJson['access_token'] ?? null;
if (!$accessToken) {
    json_out(['error_code' => 'paypal_token_missing', 'error' => 'paypal_token_missing'], 502);
}

// ---- 2) Create order
$orderBody = [
    'intent' => 'CAPTURE',
    'purchase_units' => [[
        'description' => $label,
        'custom_id' => "project:{$projectId}|type:{$paymentType}|user:{$userId}",
        'amount' => [
            'currency_code' => 'EUR',
            'value' => number_format($amount, 2, '.', '')
        ],
    ]],
];

$ch = curl_init($baseUrl . '/v2/checkout/orders');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ],
    CURLOPT_POSTFIELDS => json_encode($orderBody),
]);
$orderRaw = curl_exec($ch);
$orderHttp = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$orderErr = curl_error($ch);
curl_close($ch);

if ($orderRaw === false || $orderHttp < 200 || $orderHttp >= 300) {
    json_out([
        'error_code' => 'paypal_create_error',
        'error' => 'paypal_create_error',
        'details' => $orderErr ?: $orderRaw
    ], 502);
}
$orderJson = json_decode((string)$orderRaw, true);
$orderId = $orderJson['id'] ?? null;
if (!$orderId) {
    json_out(['error_code' => 'paypal_order_id_missing', 'error' => 'paypal_order_id_missing'], 502);
}

// stocke un minimum côté session pour la capture
$_SESSION['paypal_orders'][$orderId] = [
    'project_id' => $projectId,
    'payment_type' => $paymentType,
    'amount' => $amount,
    'currency' => 'EUR',
    'created_at' => time(),
];

json_out([
    'id' => $orderId,
    'links' => $orderJson['links'] ?? [],
    'amount' => $amount,
    'currency' => 'EUR',
]);
