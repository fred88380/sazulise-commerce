<?php

declare(strict_types=1);

// Corrige l'erreur TCPDF: "Some data has already been output, can't send PDF file"
if (php_sapi_name() !== 'cli') {
    if (ob_get_level()) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
    ob_start();
}

require_once __DIR__ . '/../../bootstrap.php';

/* ===============================
   TCPDF include
   ================================ */
$tcpdfAutoload = dirname(__DIR__, 2) . '/../ancien-sazulis/vendor/autoload.php';
if (!is_file($tcpdfAutoload)) {
    http_response_code(500);
    exit('TCPDF introuvable.');
}
require_once $tcpdfAutoload;

/* ===============================
   OUTILS (helpers)
   ================================ */
function norm_name(string $s): string {
    $s = trim($s);
    $s = preg_replace('/\s+/', ' ', $s);
    return mb_strtolower($s);
}

function findFileSmart(string $dir, string $wanted): ?array {
    if (!is_dir($dir)) return null;
    $wantedN = norm_name($wanted);

    $files = @scandir($dir);
    if (!is_array($files)) return null;

    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        if (norm_name($f) === $wantedN) {
            $full = rtrim($dir, '/') . '/' . $f;
            return ['path' => $full, 'file' => $f];
        }
    }

    $wantedBase = preg_replace('/\.[a-z0-9]+$/i', '', $wantedN);
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $fn = norm_name($f);
        $base = preg_replace('/\.[a-z0-9]+$/i', '', $fn);
        if ($base === $wantedBase) {
            $full = rtrim($dir, '/') . '/' . $f;
            return ['path' => $full, 'file' => $f];
        }
    }
    return null;
}

/* ===============================
   AUTH & PARAMS
   ================================ */
$isPreview = (isset($_GET['preview']) && $_GET['preview'] === '1') || (isset($_GET['preview_html']) && $_GET['preview_html'] === '1');
$user = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;

if (!$isPreview && (!$user || (($user['role'] ?? 'client') !== 'client'))) {
    http_response_code(403);
    exit('Accès refusé : vous devez être connecté pour voir cette facture.');
}

$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
if ($orderId <= 0 && !$isPreview) {
    http_response_code(400);
    exit('Commande introuvable.');
}

/* ===============================
   RÉCUPÉRATION DES DONNÉES
   ================================ */
$pdo = \App\Core\Database::getConnection();

if ($pdo === null || $isPreview) {
    $order = [
        'id' => $orderId > 0 ? $orderId : 1,
        'order_ref' => 'SAZ-PRV-001',
        'customer_name' => 'Client Démo',
        'customer_email' => 'demo@sazulis.fr',
        'customer_address' => '1 Rue de la Démo, 88000 Arches',
        'total' => 1299.90,
        'status' => 'paid',
        'created_at' => date('Y-m-d H:i:s'),
        'acompte' => 300.00,
        'remise' => 0.0,
        'acompte_paye' => 1,
        'solde_regle' => 1
    ];
    $items = [
        ['product_name' => 'Site vitrine', 'unit_price' => 999.90, 'quantity' => 1],
        ['product_name' => 'Maintenance', 'unit_price' => 300.00, 'quantity' => 1],
    ];
} else {
    $stmt = $pdo->prepare(
        'SELECT id, order_ref, customer_name, customer_email, customer_address, total, status, created_at, acompte, remise, acompte_paye, solde_regle
         FROM orders
         WHERE id = :id AND (user_id = :user_id OR customer_email = :email)
         LIMIT 1'
    );
    $stmt->execute([
        'id' => $orderId,
        'user_id' => (int) ($user['id'] ?? 0),
        'email' => (string) ($user['email'] ?? ''),
    ]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        http_response_code(404);
        exit('Commande introuvable.');
    }

    $itemStmt = $pdo->prepare('SELECT product_name, unit_price, quantity FROM order_items WHERE order_id = :order_id ORDER BY id ASC');
    $itemStmt->execute(['order_id' => $orderId]);
    $items = $itemStmt->fetchAll() ?: [];
}

/* ===============================
   CALCULS ET VARIABLE SANS TVA
   ================================ */
$numeroFacture = (string) ($order['order_ref'] ?? ('F-' . $order['id']));
$dateFacture = !empty($order['created_at']) ? (new DateTimeImmutable((string) $order['created_at']))->format('d/m/Y') : (new DateTimeImmutable('now'))->format('d/m/Y');

$orderRef = htmlspecialchars((string) ($order['order_ref'] ?? ''), ENT_QUOTES, 'UTF-8');
$customerName = htmlspecialchars((string) ($order['customer_name'] ?? 'Client'), ENT_QUOTES, 'UTF-8');
$customerEmail = htmlspecialchars((string) ($order['customer_email'] ?? ''), ENT_QUOTES, 'UTF-8');
$customerAddress = htmlspecialchars((string) ($order['customer_address'] ?? ''), ENT_QUOTES, 'UTF-8');
$status = htmlspecialchars(ucfirst((string) ($order['status'] ?? 'pending')), ENT_QUOTES, 'UTF-8');

$total = 0.0;
foreach ($items as $item) {
    $total += (float)($item['unit_price'] ?? 0) * max(1, (int)($item['quantity'] ?? 0));
}

$acompte = isset($order['acompte']) ? (float)$order['acompte'] : 0.0;
$remise  = isset($order['remise'])  ? (float)$order['remise']  : 0.0;

$totalFinal  = max(0, $total - $remise);
$resteAPayer = max(0, $totalFinal - $acompte);

$acomptePaye = ((int)($order['acompte_paye'] ?? 0) === 1);
$soldeRegle  = ((int)($order['solde_regle'] ?? 0) === 1);

// Entreprise émettrice
$entreprise = [
    'nom' => 'SAZULIS',
    'adresse' => '1 Résidence les Fallières, 88380 Arches',
    'email' => 'contact@sazulis.fr',
    'tel' => '06 98 76 67 80'
];

/* ===============================
   HTML PREVIEW MODE
   ================================ */
if (isset($_GET['preview_html']) && $_GET['preview_html'] === '1') {
    $rows = '';
    foreach ($items as $item) {
        $name = htmlspecialchars((string) ($item['product_name'] ?? 'Produit'), ENT_QUOTES, 'UTF-8');
        $unitPrice = number_format((float) ($item['unit_price'] ?? 0), 2, ',', ' ');
        $quantity = max(1, (int) ($item['quantity'] ?? 0));
        $lineTotal = number_format((float) ($item['unit_price'] ?? 0) * $quantity, 2, ',', ' ');
        $rows .= '<tr><td>' . $name . '</td><td style="text-align:center;">' . $quantity . '</td><td style="text-align:right;">' . $unitPrice . ' EUR</td><td style="text-align:right;">' . $lineTotal . ' EUR</td></tr>';
    }

    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!doctype html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Aperçu facture</title><style>body{font-family:Arial,sans-serif;background:#f5f7fb;color:#172033;margin:0;padding:32px}.sheet{max-width:900px;margin:0 auto;background:#fff;border:1px solid #d7deea;border-radius:18px;padding:32px;box-shadow:0 18px 42px rgba(18,31,53,.08)}.top{display:flex;justify-content:space-between;gap:24px;flex-wrap:wrap;border-bottom:2px solid #1a2347;padding-bottom:18px;margin-bottom:18px}.brand{font-size:30px;font-weight:800;letter-spacing:.08em;color:#1a2347}.meta{color:#5e6b84;font-size:14px}.section{margin-top:18px}.section h2{font-size:18px;margin:0 0 10px;color:#1a2347}.box{background:#f8fafc;border:1px solid #e4eaf3;border-radius:14px;padding:14px 16px}.summary{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.summary div{background:#f8fafc;border:1px solid #e4eaf3;border-radius:14px;padding:12px 14px}.summary strong{display:block;color:#1a2347;margin-bottom:4px}</style></head><body><div class="sheet"><div class="top"><div><div class="brand">SAZULIS</div><div class="meta">Aperçu facture</div></div><div class="meta"><strong>Facture</strong><br>Ref: <?= $orderRef ?><br>Date: <?= $dateFacture ?></div></div><div class="section"><h2>Client</h2><div class="box"><?= $customerName ?><br><?= $customerEmail ?><br><?= $customerAddress ?></div></div><div class="section"><h2>Résumé commande</h2><div class="summary"><div><strong>Référence</strong><?= $orderRef ?></div><div><strong>Statut</strong><?= $status ?></div><div><strong>Date</strong><?= $dateFacture ?></div><div><strong>Total Final</strong><?= number_format($totalFinal, 2, ',', ' ') ?> EUR</div></div></div><div class="section"><h2>Détails facture</h2><table cellpadding="6" cellspacing="0" border="1" width="100%" style="border-color:#e4eaf3;border-collapse:collapse;"><tr style="background-color:#f8fafc;"><th align="left">Désignation</th><th width="60" align="center">Qté</th><th width="90" align="right">PU</th><th width="90" align="right">Total</th></tr><?= ($rows !== '' ? $rows : '<tr><td colspan="4">Aucun article trouvé</td></tr>') ?></table></div></div></body></html>
    <?php
    exit;
}

/* ===============================
   CLASSE PDF (HEADER + FOOTER)
   ================================ */
class SazulisInvoicePdf extends TCPDF
{
    public function Header(): void
    {
        $logoPath = dirname(__DIR__) . '/assets/img/sazulis-logo1.png';
        if (is_file($logoPath)) {
            $this->Image($logoPath, 15, 15, 20, 0, '');
        }

        $this->SetXY(40, 15);
        $this->SetFont('helvetica', 'B', 22);
        $this->SetTextColor(40, 40, 40);
        $this->Cell(0, 18, 'FACTURE', 0, 1, 'L');
        $this->Ln(2);

        $this->SetFont('helvetica', '', 11);
        global $numeroFacture, $dateFacture;
        $this->Cell(0, 6, 'Numéro : ' . $numeroFacture, 0, 1);
        $this->Cell(0, 6, 'Date : ' . $dateFacture, 0, 1);
        $this->Ln(6);
    }

    public function Footer(): void
    {
        $this->SetY(-25);
        $this->SetDrawColor(230, 199, 122);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->Ln(5);
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(140, 140, 140);
        $this->Cell(0, 6, 'Société SAZULIS – SIREN 752 628 040 00020', 0, 0, 'C');
    }
}

/* ===============================
   INIT PDF
   ================================ */
$pdf = new SazulisInvoicePdf('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('SAZULIS');
$pdf->SetAuthor('SAZULIS');
$pdf->SetTitle('Facture ' . $numeroFacture);

$pdf->SetMargins(15, 20, 15);
$pdf->SetAutoPageBreak(true, 35);
$pdf->AddPage();

/* ===============================
   FILIGRANE (GRAND + CENTRÉ)
   ================================ */
$filigrane = dirname(__DIR__) . '/assets/img/sazulis-logo1.png';
if (is_file($filigrane)) {
    $pageWidth  = $pdf->getPageWidth();
    $pageHeight = $pdf->getPageHeight();

    $imgWidth = $pageWidth - 30;
    $x = ($pageWidth - $imgWidth) / 2;
    $y = ($pageHeight / 2) - ($imgWidth / 2);

    $pdf->SetAlpha(0.18);
    $pdf->Image($filigrane, $x, $y, $imgWidth, 0, '');
    $pdf->SetAlpha(1);
}

/* ===============================
   BLOCS ENTREPRISE / CLIENT
   ================================ */
$pdf->Ln(28);

$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetTextColor(40, 40, 40);
$pdf->Cell(90, 6, 'Émetteur', 0, 0);
$pdf->Cell(0, 6, 'Client', 0, 1);

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(60, 60, 60);

$pdf->MultiCell(
    90, 6,
    "{$entreprise['nom']}\n{$entreprise['adresse']}\n{$entreprise['email']}\n{$entreprise['tel']}",
    0, 'L', false, 0
);

$blocClient = $customerName . "\n" . $customerAddress . "\n" . $customerEmail;
$pdf->MultiCell(0, 6, $blocClient, 0, 'L');
$pdf->Ln(10);

/* ===============================
   TABLEAU ARTICLES (HTML)
   ================================ */
$html = '
<table cellpadding="8" style="width:100%;">
    <thead>
        <tr style="background-color:#E6C77A; color:#000;">
            <th width="50%">Désignation</th>
            <th width="15%" align="center">Qté</th>
            <th width="15%" align="right">PU</th>
            <th width="20%" align="right">Total</th>
        </tr>
    </thead>
    <tbody>';

if (empty($items)) {
    $html .= '<tr><td colspan="4" align="center">Aucun article trouvé</td></tr>';
} else {
    foreach ($items as $item) {
        $nom  = htmlspecialchars((string)($item['product_name'] ?? 'Produit'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $qty  = max(1, (int)($item['quantity'] ?? 0));
        $pu   = (float)($item['unit_price'] ?? 0);
        $line = $pu * $qty;

        $html .= '
        <tr>
            <td width="50%">'.$nom.'</td>
            <td width="15%" align="center">'.$qty.'</td>
            <td width="15%" align="right">'.number_format($pu, 2, ',', ' ').' €</td>
            <td width="20%" align="right">'.number_format($line, 2, ',', ' ').' €</td>
        </tr>';
    }
}

$html .= '
    </tbody>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(5);

/* ===============================
   TOTAUX SANS TVA
   ================================ */
$pdf->SetFont('helvetica', '', 11);
$pdf->SetTextColor(40, 40, 40);

$pdf->Cell(130, 8, 'Total global', 0);
$pdf->Cell(40, 8, number_format($total, 2, ',', ' ').' €', 0, 1, 'R');

if ($remise > 0) {
    $pdf->Cell(130, 8, 'Remise', 0);
    $pdf->Cell(40, 8, '-'.number_format($remise, 2, ',', ' ').' €', 0, 1, 'R');
}

if ($acompte > 0) {
    $pdf->Cell(130, 8, 'Acompte', 0);
    $pdf->Cell(40, 8, number_format($acompte, 2, ',', ' ').' €', 0, 1, 'R');
}

$pdf->SetFont('helvetica', 'B', 13);
$pdf->SetTextColor(180, 40, 40);
$pdf->Cell(130, 10, 'RESTE À PAYER', 0);
$pdf->Cell(40, 10, number_format($resteAPayer, 2, ',', ' ').' €', 0, 1, 'R');

/* ===============================
   TEXTE BAS PRO
   ================================ */
$pdf->SetTextColor(80, 80, 80);
$pdf->SetFont('helvetica', '', 9);
$pdf->Ln(6);

$pdf->MultiCell(
    0, 6,
    "Merci pour votre confiance.\n".
    "Conditions de règlement : acompte dû à la commande – solde exigible à la livraison / finalisation des prestations.\n".
    "En cas de question, contactez-nous à {$entreprise['email']} ou au {$entreprise['tel']}.",
    0, 'C'
);

/* ==========================================================
   BLOC BAS (TAMPONS + SIGNATURES) - INTERRUPTIONS SAUVAGES
   ========================================================== */
$blockHeight   = 78;
$blockOffsetUp = 12;

$pageH = $pdf->getPageHeight();
$breakMargin = $pdf->getBreakMargin();
$yTopBlock = $pageH - $breakMargin - $blockHeight - $blockOffsetUp;

if ($pdf->GetY() > ($yTopBlock - 5)) {
    $pdf->AddPage();

    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetTextColor(40, 40, 40);
    $pdf->Cell(0, 8, 'VALIDATION DE PAIEMENT', 0, 1, 'C');
    $pdf->Ln(4);

    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->MultiCell(
        0, 6,
        "Ce document atteste du règlement des sommes dues pour la commande mentionnée.\n\n".
        "Le paiement de l’acompte et du solde valide la réalisation des prestations conformément au contrat accepté.\n\n".
        "La livraison finale du projet est conditionnée au règlement complet du solde.",
        0, 'C'
    );

    $pageH = $pdf->getPageHeight();
    $breakMargin = $pdf->getBreakMargin();
    $yTopBlock = $pageH - $breakMargin - $blockHeight - $blockOffsetUp;
}

$pdf->SetY($yTopBlock);

/* ===============================
   TAMPONS DYNAMIQUES
   ================================ */
$tamponWidth = 32;
$tamponSpacing = 18;
$pageWidth = $pdf->getPageWidth();
$xStart = ($pageWidth - ($tamponWidth * 2 + $tamponSpacing)) / 2;

$imgDir = dirname(__DIR__) . '/assets/img';

$acompteFound = findFileSmart($imgDir, 'Acompte-Paye.png');
$soldeFound   = findFileSmart($imgDir, 'Solde-regler.png');

$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(60, 60, 60);
$pdf->Cell(0, 6, 'Statut de paiement', 0, 1, 'C');

$yTampon = $pdf->GetY();

if ($acomptePaye && $acompteFound && is_file($acompteFound['path'])) {
    $pdf->Image($acompteFound['path'], $xStart, $yTampon, $tamponWidth, 0, '');
}
if ($soldeRegle && $soldeFound && is_file($soldeFound['path'])) {
    $pdf->Image($soldeFound['path'], $xStart + $tamponWidth + $tamponSpacing, $yTampon, $tamponWidth, 0, '');
}

/* ===============================
   SIGNATURES
   ================================ */
$ySig = $yTampon + $tamponWidth + 5;

$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(40, 40, 40);
$pdf->SetXY(20, $ySig);
$pdf->Cell(60, 8, 'Prestataire', 0, 0, 'C');
$pdf->SetXY(110, $ySig);
$pdf->Cell(80, 8, 'Client', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(80, 80, 80);
$pdf->SetXY(20, $ySig + 8);
$pdf->Cell(80, 6, 'Signature et tampon du prestataire', 0, 0, 'C');
$pdf->SetXY(110, $ySig + 8);
$pdf->Cell(80, 6, 'Signature et tampon du client', 0, 1, 'C');

$tamponPrestataire = dirname(__DIR__) . '/assets/img/Sazulis-tampon0000.png';
if (is_file($tamponPrestataire)) {
    $pdf->Image($tamponPrestataire, 25, $ySig + 15, 26, 0, '');
}

// Emplacement de signature dynamique client si sauvegardée (basé sur order/user)
$userId = $user ? (int)($user['id'] ?? 0) : 0;
$signPath = dirname(__DIR__) . '/factures/signatures/' . $orderId . '_' . $userId . '.png';
if (is_file($signPath)) {
    $pdf->Image($signPath, 132, $ySig + 15, 40, 0, '');
}

/* ===============================
   SORTIE DU PDF
   ================================ */
$filename = 'Facture_' . preg_replace('/[^a-zA-Z0-9_-]+/', '_', $numeroFacture) . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: ' . ($isPreview ? 'inline' : 'attachment') . '; filename="' . $filename . '"');
$pdf->Output($filename, 'I');
exit;