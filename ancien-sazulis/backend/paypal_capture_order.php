<?php
// backend/paypal_capture_order.php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../paypal_init.php';

header('Content-Type: application/json; charset=utf-8');

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

if (empty($_SESSION['user_id'])) {
    json_out(['error_code' => 'unauthorized', 'error' => 'unauthorized'], 401);
}
$userId = (int)$_SESSION['user_id'];

$payload = read_json_body();
$orderID = isset($payload['orderID']) ? trim((string)$payload['orderID']) : '';
$projectId = isset($payload['project_id']) ? (int)$payload['project_id'] : 0;
$paymentType = isset($payload['payment_type']) ? (string)$payload['payment_type'] : 'acompte';
if (!in_array($paymentType, ['acompte', 'solde'], true)) $paymentType = 'acompte';

if ($orderID === '' || $projectId <= 0) {
    json_out(['error_code' => 'invalid_request', 'error' => 'invalid_request'], 400);
}

require_once __DIR__ . '/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    json_out(['error_code' => 'db_unavailable', 'error' => 'db_unavailable'], 500);
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Vérif projet appartient à l'utilisateur + calc montant attendu
try {
    $stmt = $pdo->prepare("SELECT * FROM projets WHERE id = ? AND id_utilisateur = ? LIMIT 1");
    $stmt->execute([$projectId, $userId]);
    $projet = $stmt->fetch();
    if (!$projet) {
        json_out(['error_code' => 'project_not_found', 'error' => 'project_not_found'], 404);
    }

    $total   = (float)($projet['total'] ?? 0);
    $acompte = (float)($projet['acompte'] ?? 0);
    $solde = isset($projet['solde']) ? (float)$projet['solde'] : max(0, $total - $acompte);

    $expected = ($paymentType === 'solde') ? $solde : $acompte;
    $expected = round($expected + 1e-9, 2);

    if ($expected <= 0) {
        json_out(['error_code' => 'invalid_amount', 'error' => 'invalid_amount'], 400);
    }
} catch (Throwable $e) {
    json_out(['error_code' => 'db_error', 'error' => 'db_error'], 500);
}

// Si on a l'order stocké en session, on vérifie cohérence
if (!empty($_SESSION['paypal_orders'][$orderID])) {
    $s = $_SESSION['paypal_orders'][$orderID];
    if ((int)($s['project_id'] ?? 0) !== $projectId || (string)($s['payment_type'] ?? '') !== $paymentType) {
        json_out(['error_code' => 'order_mismatch', 'error' => 'order_mismatch'], 400);
    }
}

// Config PayPal (depuis paypal_init.php)
$clientId = $paypalClientId;
$secret   = $paypalSecret;
$baseUrl  = $paypalApiBase;

if (!$clientId || !$secret) {
    json_out(['error_code' => 'paypal_keys_missing', 'error' => 'PAYPAL_CLIENT_ID/PAYPAL_SECRET manquant'], 500);
}

// Access token
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
    json_out(['error_code' => 'paypal_token_error', 'error' => 'paypal_token_error', 'details' => $tokenErr ?: $tokenRaw], 502);
}
$tokenJson = json_decode((string)$tokenRaw, true);
$accessToken = $tokenJson['access_token'] ?? null;
if (!$accessToken) {
    json_out(['error_code' => 'paypal_token_missing', 'error' => 'paypal_token_missing'], 502);
}

// Capture
$ch = curl_init($baseUrl . '/v2/checkout/orders/' . rawurlencode($orderID) . '/capture');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ],
]);
$capRaw = curl_exec($ch);
$capHttp = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$capErr = curl_error($ch);
curl_close($ch);

if ($capRaw === false || $capHttp < 200 || $capHttp >= 300) {
    json_out(['error_code' => 'paypal_capture_error', 'error' => 'paypal_capture_error', 'details' => $capErr ?: $capRaw], 502);
}

$capJson = json_decode((string)$capRaw, true);
$status = (string)($capJson['status'] ?? '');

// Vérifie le montant capturé (tolérance 1 centime)
$capturedValue = null;
try {
    $pu = $capJson['purchase_units'][0] ?? [];
    $payments = $pu['payments']['captures'][0] ?? null;
    if (is_array($payments) && isset($payments['amount']['value'])) {
        $capturedValue = round((float)$payments['amount']['value'] + 1e-9, 2);
    }
} catch (Throwable $e) {
    $capturedValue = null;
}

if ($status === 'COMPLETED') {
    if ($capturedValue !== null && abs($capturedValue - $expected) > 0.01) {
        json_out([
            'error_code' => 'amount_mismatch',
            'error' => 'amount_mismatch',
            'status' => $status,
            'expected' => $expected,
            'captured' => $capturedValue
        ], 409);
    }

    // Update projet : marque payé
    try {
        if ($paymentType === 'acompte') {
            $pdo->prepare("UPDATE projets SET acompte_recu = 1 WHERE id = ? AND id_utilisateur = ?")->execute([$projectId, $userId]);
        } else {
            // si colonne solde_recu existe, on la met, sinon on met acompte_recu + statut (fallback)
            $cols = $pdo->query("DESCRIBE projets")->fetchAll(PDO::FETCH_ASSOC);
            $hasSoldeRecu = false;
            foreach ($cols as $c) {
                if (($c['Field'] ?? '') === 'solde_recu') { $hasSoldeRecu = true; break; }
            }
            if ($hasSoldeRecu) {
                $pdo->prepare("UPDATE projets SET solde_recu = 1 WHERE id = ? AND id_utilisateur = ?")->execute([$projectId, $userId]);
            } else {
                $pdo->prepare("UPDATE projets SET statut = 'payé' WHERE id = ? AND id_utilisateur = ?")->execute([$projectId, $userId]);
            }
        }
    } catch (Throwable $e) {
        // paiement OK, mais update DB KO => on retourne quand même COMPLETED + warning
        json_out(['status' => $status, 'warning' => 'db_update_failed'], 200);
    }

    // nettoyage session
    if (isset($_SESSION['paypal_orders'][$orderID])) unset($_SESSION['paypal_orders'][$orderID]);

    json_out(['status' => $status], 200);
}

json_out(['status' => $status, 'raw' => $capJson], 200);
