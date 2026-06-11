<?php
// Corrige l'erreur TCPDF: "Some data has already been output, can't send PDF file"
if (php_sapi_name() !== 'cli') {
    if (ob_get_level()) ob_end_clean();
    ob_start();
}
include_once __DIR__ . '/../bootstrap.php'; 
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die("Accès refusé : vous devez être connecté pour voir une facture.");
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die("Facture introuvable : identifiant manquant ou invalide.");
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../backend/db.php';

/* ===============================
   CLASSE PDF (HEADER + FOOTER)
================================ */
class FacturePDF extends TCPDF {
    public function Header() {
        $logoPath = __DIR__ . '/../assets/img/sazulis-logo1.png';
        if (is_file($logoPath)) {
            $this->Image($logoPath, 15, 15, 20, 0, '');
        }

        $this->SetXY(40, 15);
        $this->SetFont('helvetica', 'B', 22);
        $this->SetTextColor(40,40,40);
        $this->Cell(0, 18, 'FACTURE', 0, 1, 'L');
        $this->Ln(2);

        $this->SetFont('helvetica', '', 11);
        global $numeroFacture, $dateFacture;
        $this->Cell(0, 6, 'Numéro : ' . $numeroFacture, 0, 1);
        $this->Cell(0, 6, 'Date : ' . $dateFacture, 0, 1);
        $this->Ln(6);
    }

    public function Footer() {
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
   RÉCUP DONNÉES BDD
================================ */
$idFacture = (int)$_GET['id'];
$userId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT * FROM factures WHERE id = ?');
$stmt->execute([$idFacture]);
$facture = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$facture) die('Facture inconnue');

$stmt = $pdo->prepare('SELECT * FROM commandes WHERE id = ?');
$stmt->execute([(int)$facture['id_commande']]);
$commande = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$commande) die('Commande inconnue');

// Sécurité: la facture doit appartenir au client connecté
if ((int)$commande['id_utilisateur'] !== $userId) {
    http_response_code(403);
    die("Accès refusé : cette facture ne vous appartient pas.");
}

$stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = ?');
$stmt->execute([(int)$commande['id_utilisateur']]);
$clientRow = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$clientRow) die('Client inconnu');

/* ===============================
   DÉSIGNATION (projets.titre + produits.nom)
   -> utilisé dans "Désignation"
   On cherche le projet lié à la facture ou à la commande
   Puis on récupère le nom du produit lié (id_produit)
================================ */
$designation = '';
$projetRow = null;
try {
    // Priorité : projet lié à la facture
    $stmt = $pdo->prepare("
        SELECT *
        FROM projets
        WHERE facture_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([$idFacture]);
    $projetRow = $stmt->fetch(PDO::FETCH_ASSOC);

    // Sinon : projet lié à la commande
    if (!$projetRow) {
        $stmt = $pdo->prepare("
            SELECT *
            FROM projets
            WHERE commande_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([(int)$commande['id']]);
        $projetRow = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $projetRow = null;
}

$projetTitre = $projetRow['titre'] ?? '';
$produitNom = '';
if (!empty($projetRow['id_produit'])) {
    try {
        $stmt = $pdo->prepare('SELECT nom FROM produits WHERE id = ?');
        $stmt->execute([(int)$projetRow['id_produit']]);
        $prodRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $produitNom = $prodRow['nom'] ?? '';
    } catch (Throwable $e) {
        $produitNom = '';
    }
}

if ($projetTitre && $produitNom) {
    $designation = $projetTitre . ' – ' . $produitNom;
} elseif ($projetTitre) {
    $designation = $projetTitre;
} elseif ($produitNom) {
    $designation = $produitNom;
} else {
    $designation = 'Projet';
}

/* ===============================
   PRODUITS (prix)
   - On prend les prix de commande_produits
   - Désignation sera FORCÉE au titre du projet dans le tableau
================================ */
$produits = [];
try {
    $stmt = $pdo->prepare('SELECT nom, prix FROM commande_produits WHERE id_commande = ?');
    $stmt->execute([(int)$commande['id']]);
    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $produits = [];
}

// Si aucun produit: on met une ligne avec le montant facture (ou 0)
if (empty($produits)) {
    $produits = [[
        'nom'  => $projetTitre, // pas utilisé (on force dans tableau), mais ok
        'prix' => (float)($facture['montant'] ?? 0)
    ]];
}

/* ===============================
   MÉTADONNÉES FACTURE
================================ */
$numeroFacture = $facture['numero'] ?? ('F-' . $idFacture);
$dateFacture = !empty($facture['date_emission'])
    ? date('d/m/Y', strtotime((string)$facture['date_emission']))
    : date('d/m/Y');

// Entreprise
$entreprise = [
    'nom' => 'SAZULIS',
    'adresse' => '1 Résidence les Fallières, 88380 Arches',
    'email' => 'contact@sazulis.fr',
    'tel' => '06 98 76 67 80'
];

// Client
$statutUser = $clientRow['statut'] ?? 'particulier';
$clientNom = $clientRow['nom'] ?? '';
$clientEmail = $clientRow['email'] ?? '';
$clientAdresse = '';
$clientSociete = '';
$clientSiret = '';

if ($statutUser === 'societe') {
    $clientSociete = $clientRow['nom_societe'] ?? '';
    $clientSiret = $clientRow['siret'] ?? '';
    $clientAdresse = $clientRow['adresse_societe'] ?? '';
} else {
    $clientAdresse = $clientRow['adresse'] ?? '';
}

/* ===============================
   CALCULS (SANS TVA)
================================ */
$total = 0.0;
foreach ($produits as $p) {
    $total += (float)($p['prix'] ?? 0);
}

$acompte = isset($facture['acompte']) ? (float)$facture['acompte'] : 0.0;
$remise  = isset($facture['remise'])  ? (float)$facture['remise']  : 0.0;

$totalFinal  = max(0, $total - $remise);
$resteAPayer = max(0, $totalFinal - $acompte);

// Tampons
$acomptePaye = ((int)($facture['acompte_paye'] ?? 0) === 1);
$soldeRegle  = ((int)($facture['solde_regle'] ?? 0) === 1);

/* ===============================
   INIT PDF
================================ */
$pdf = new FacturePDF();
$pdf->SetCreator('SAZULIS');
$pdf->SetAuthor('SAZULIS');
$pdf->SetTitle('Facture ' . $numeroFacture);

$pdf->SetMargins(15, 20, 15);
$pdf->SetAutoPageBreak(true, 35);
$pdf->AddPage();

/* ===============================
   FILIGRANE (GRAND + CENTRÉ)
================================ */
$filigrane = __DIR__ . '/../assets/img/sazulis-logo1.png';
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
$pdf->SetTextColor(40,40,40);
$pdf->Cell(90, 6, 'Émetteur', 0, 0);
$pdf->Cell(0, 6, 'Client', 0, 1);

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(60,60,60);

$pdf->MultiCell(
    90, 6,
    "{$entreprise['nom']}\n{$entreprise['adresse']}\n{$entreprise['email']}\n{$entreprise['tel']}",
    0, 'L', false, 0
);

$blocClient = $clientNom . "\n";
if ($statutUser === 'societe') {
    if ($clientSociete) $blocClient .= $clientSociete . "\n";
    if ($clientSiret) $blocClient .= 'SIRET : ' . $clientSiret . "\n";
    if ($clientAdresse) $blocClient .= $clientAdresse . "\n";
} else {
    if ($clientAdresse) $blocClient .= $clientAdresse . "\n";
}
$blocClient .= $clientEmail;

$pdf->MultiCell(0, 6, $blocClient, 0, 'L');
$pdf->Ln(10);

/* ===============================
   TABLEAU PRODUITS (HTML)
   ✅ Désignation = projets.titre (FORCÉ)
================================ */
$html = '
<table cellpadding="8">
    <thead>
        <tr style="background-color:#E6C77A; color:#000;">
            <th width="70%">Désignation</th>
            <th width="30%" align="right">Prix</th>
        </tr>
    </thead>
    <tbody>';

foreach ($produits as $p) {
    $nom  = htmlspecialchars($designation, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $prix = (float)($p['prix'] ?? 0);

    $html .= '
    <tr>
        <td>'.$nom.'</td>
        <td align="right">'.number_format($prix,2,',',' ').' €</td>
    </tr>';
}

$html .= '
    </tbody>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(5);

/* ===============================
   TOTAUX
================================ */
$pdf->SetFont('helvetica', '', 11);
$pdf->SetTextColor(40,40,40);

$pdf->Cell(130, 8, 'Total', 0);
$pdf->Cell(40, 8, number_format($total,2,',',' ').' €', 0, 1, 'R');

if ($remise > 0) {
    $pdf->Cell(130, 8, 'Remise', 0);
    $pdf->Cell(40, 8, '-'.number_format($remise,2,',',' ').' €', 0, 1, 'R');
}

if ($acompte > 0) {
    $pdf->Cell(130, 8, 'Acompte', 0);
    $pdf->Cell(40, 8, number_format($acompte,2,',',' ').' €', 0, 1, 'R');
}

$pdf->SetFont('helvetica', 'B', 13);
$pdf->SetTextColor(180, 40, 40);
$pdf->Cell(130, 10, 'RESTE À PAYER', 0);
$pdf->Cell(40, 10, number_format($resteAPayer,2,',',' ').' €', 0, 1, 'R');

/* ===============================
   TEXTE BAS (pro)
================================ */
$pdf->SetTextColor(80,80,80);
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
   BLOC BAS (TAMPONS + SIGNATURES) - REMONTÉ
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
    $pdf->SetTextColor(40,40,40);
    $pdf->Cell(0, 8, 'VALIDATION DE PAIEMENT', 0, 1, 'C');
    $pdf->Ln(4);

    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(80,80,80);
    $pdf->MultiCell(
        0, 6,
        "Ce document atteste du règlement des sommes dues pour le projet mentionné dans la facture.\n\n".
        "Le paiement de l’acompte et du solde valide la réalisation des prestations conformément au devis accepté.\n\n".
        "La livraison finale du projet est conditionnée au règlement complet du solde.",
        0, 'C'
    );

    $pageH = $pdf->getPageHeight();
    $breakMargin = $pdf->getBreakMargin();
    $yTopBlock = $pageH - $breakMargin - $blockHeight - $blockOffsetUp;
}

$pdf->SetY($yTopBlock);

/* ===============================
   TAMPONS
================================ */
$tamponWidth = 32;
$tamponSpacing = 18;
$pageWidth = $pdf->getPageWidth();
$xStart = ($pageWidth - ($tamponWidth * 2 + $tamponSpacing)) / 2;

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$imgDir = $root . '/assets/img';

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
$pdf->SetTextColor(40,40,40);
$pdf->SetXY(20, $ySig);
$pdf->Cell(60, 8, 'Prestataire', 0, 0, 'C');
$pdf->SetXY(110, $ySig);
$pdf->Cell(80, 8, 'Client', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(80,80,80);
$pdf->SetXY(20, $ySig + 8);
$pdf->Cell(80, 6, 'Signature et tampon du prestataire', 0, 0, 'C');
$pdf->SetXY(110, $ySig + 8);
$pdf->Cell(80, 6, 'Signature et tampon du client', 0, 1, 'C');

$tamponPrestataire = __DIR__ . '/../assets/img/Sazulis-tampon0000.png';
if (is_file($tamponPrestataire)) {
    $pdf->Image($tamponPrestataire, 25, $ySig + 15, 26, 0, '');
}

$signPath = __DIR__ . '/../factures/signatures/' . $idFacture . '_' . $userId . '.png';
if (is_file($signPath)) {
    $pdf->Image($signPath, 132, $ySig + 15, 40, 0, '');
}

/* ===============================
   SORTIE PDF
================================ */
$pdf->Output($numeroFacture . '.pdf', 'I');
