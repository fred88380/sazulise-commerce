<?php

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    if (ob_get_level()) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
    ob_start();
}

session_start();
require_once __DIR__ . '/../protect.php'; // Inclusions et sécurité issues de contrat1.php

/* =========================
   TCPDF include
   ========================= */
$tcpdfPathVendor = __DIR__ . '/../vendor/autoload.php';
$tcpdfPathLocal  = __DIR__ . '/../tcpdf/tcpdf.php';

if (is_file($tcpdfPathVendor)) {
    require_once $tcpdfPathVendor;
} elseif (is_file($tcpdfPathLocal)) {
    require_once $tcpdfPathLocal;
} else {
    die("TCPDF introuvable. Installe TCPDF (vendor/autoload.php ou ../tcpdf/tcpdf.php).");
}

/* =========================
   DB
   ========================= */
require_once __DIR__ . '/../backend/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("Erreur : \$pdo indisponible. Vérifie backend/db.php");
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

/* =========================
   Helpers
   ========================= */
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function money($n): string { return number_format((float)$n, 2, ',', ' ') . ' €'; }
function pick(array $arr, array $keys, $default='') {
    foreach ($keys as $k) {
        if (isset($arr[$k]) && $arr[$k] !== '' && $arr[$k] !== null) return $arr[$k];
    }
    return $default;
}

/**
 * Écrit un bloc HTML en évitant les coupures moches
 */
function writeBlockKeepTogether(TCPDF $pdf, string $html): void {
    $startPage = $pdf->getPage();

    $pdf->startTransaction();
    $pdf->writeHTML($html, true, false, true, false, '');
    $endPage = $pdf->getPage();

    if ($endPage > $startPage) {
        $pdf->rollbackTransaction(true);
        $pdf->AddPage();

        $startPage2 = $pdf->getPage();
        $pdf->startTransaction();
        $pdf->writeHTML($html, true, false, true, false, '');
        $endPage2 = $pdf->getPage();

        if ($endPage2 > $startPage2) {
            $pdf->rollbackTransaction(true);
            $pdf->writeHTML($html, true, false, true, false, '');
        } else {
            $pdf->commitTransaction();
        }
    } else {
        $pdf->commitTransaction();
    }
}

/* =========================
   Auth + params & Modes d'Aperçu
   ========================= */
$isPreview = (isset($_GET['preview']) && $_GET['preview'] === '1') || (isset($_GET['preview_html']) && $_GET['preview_html'] === '1');
$userSession = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;

// Gestion de l'ID utilisateur connecté
$userId = 0;
if (isset($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
} elseif (isset($userSession['id'])) {
    $userId = (int)$userSession['id'];
}

if (!$isPreview && $userId <= 0) {
    http_response_code(403);
    die("Accès refusé : vous devez être connecté pour voir ce contrat.");
}

$projetId = isset($_GET['projet_id']) ? (int)$_GET['projet_id'] : 0;
if ($projetId <= 0 && !$isPreview) {
    http_response_code(400);
    die("Paramètre projet_id manquant.");
}

/* =========================
   Assets (logo / tampon)
   ========================= */
$logoPath = realpath(__DIR__ . '/../assets/img/sazulis-logo1.png');
if (!$logoPath || !is_file($logoPath)) $logoPath = null;

$stampPath = realpath(__DIR__ . '/../assets/img/Sazulis-tampon0000.png');
if (!$stampPath || !is_file($stampPath)) $stampPath = null;

/* =========================
   Branding (Sazulis)
   ========================= */
$brandName    = "Sazulis";
$brandEmail   = "sazulis@outlook.fr";
$brandWebsite = "sazulis.fr";

$brandLegalStatus = "Micro-entreprise (auto-entrepreneur)";
$brandSirenSiret  = "SIREN/SIRET : 752 628 040 00020";
$brandAddressLine  = "Adresse : 1 Résidence les Fallières, 88380 ARCHES ";
$brandCityForCourt = "Ville : ARCHES ";

$WATERMARK_ALPHA = 0.14;
$WATERMARK_MODE  = 'tile'; 

/* =========================
   Colors
   ========================= */
$gold  = '#D8B35A';
$gold2 = '#E7CF9C';
$dark  = '#1F1F1F';
$muted = '#6B5A33';
$bg    = '#FBFAF7';

/* =========================
   Données DB ou Simulation d'aperçu
   ========================= */
if ($isPreview && $projetId <= 0) {
    // Mode démo / simulation
    $titre        = 'Projet Démo Vitrine';
    $total        = 1299.90;
    $acompte      = 300.00;
    $solde        = 999.90;
    $avancement   = 10;
    $createdAt    = date('Y-m-d H:i:s');
    $statutProjet = 'En attente';

    $statutUser    = 'particulier';
    $clientNom     = 'Client Démo';
    $clientEmail   = 'demo@sazulis.fr';
    $clientAdresse = '1 Rue de la Démo, 88000 Arches';
    $clientSociete = '';
    $clientSiret   = '';
} else {
    // Données réelles issues de la base
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) { http_response_code(404); die("Utilisateur introuvable."); }

    $stmt = $pdo->prepare("SELECT * FROM projets WHERE id = ? AND id_utilisateur = ?");
    $stmt->execute([$projetId, $userId]);
    $projet = $stmt->fetch();
    if (!$projet) { http_response_code(404); die("Projet introuvable ou accès interdit."); }

    $titre        = (string)(pick($projet, ['titre','nom'], 'Projet #'.$projetId));
    $total        = (float)(pick($projet, ['total'], 0));
    $acompte      = (float)(pick($projet, ['acompte'], 0));
    $solde        = (float)(pick($projet, ['solde'], max(0, $total - $acompte)));
    $avancement   = (int)(pick($projet, ['avancement'], 0));
    $createdAt    = (string)(pick($projet, ['created_at'], date('Y-m-d H:i:s')));
    $statutProjet = (string)(pick($projet, ['statut'], '—'));

    $statutUser    = (string)(pick($user, ['statut'], 'particulier'));
    $clientNom     = trim((string)(pick($user, ['nom'], '')));
    $clientEmail   = trim((string)(pick($user, ['email'], '')));
    $clientAdresse = '';
    $clientSociete = '';
    $clientSiret   = '';

    if ($statutUser === 'societe') {
        $clientSociete = trim((string)(pick($user, ['nom_societe'], '')));
        $clientSiret   = trim((string)(pick($user, ['siret'], '')));
        $clientAdresse = trim((string)(pick($user, ['adresse_societe'], '')));
    } else {
        $clientAdresse = trim((string)(pick($user, ['adresse'], '')));
    }
}

/* =========================
   Signature client (depuis fichier)
   ========================= */
$signDir           = __DIR__ . '/signatures';
$signFileName      = $userId . '_' . $projetId . '.png';
$cfgFile           = $signDir . '/' . $userId . '_' . $projetId . '_cfg.json';
$activeSignatureFs = is_file($signDir . '/' . $signFileName) ? $signDir . '/' . $signFileName : null;
$activeCfg = null;
if (is_file($cfgFile)) {
    $tmp = json_decode(file_get_contents($cfgFile), true);
    if (is_array($tmp)) $activeCfg = $tmp;
}

$today = (new DateTimeImmutable('now'))->format('d/m/Y');
$contractNumber = 'CT-' . date('Y') . '-' . str_pad((string)$projetId, 6, '0', STR_PAD_LEFT);

/* =========================
   HTML PREVIEW (Hérité de contrat.php)
   ========================= */
if (isset($_GET['preview_html']) && $_GET['preview_html'] === '1') {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!doctype html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Aperçu contrat - <?= e($titre) ?></title>
        <style>
            body{font-family:Arial,sans-serif;background:#f5f7fb;color:#172033;margin:0;padding:32px}
            .sheet{max-width:900px;margin:0 auto;background:#fff;border:1px solid #d7deea;border-radius:18px;padding:32px;box-shadow:0 18px 42px rgba(18,31,53,.08)}
            .top{display:flex;justify-content:space-between;gap:24px;flex-wrap:wrap;border-bottom:2px solid #1a2347;padding-bottom:18px;margin-bottom:18px}
            .brand{font-size:30px;font-weight:800;letter-spacing:.08em;color:#1a2347}
            .meta{color:#5e6b84;font-size:14px}
            .section{margin-top:18px}
            .section h2{font-size:18px;margin:0 0 10px;color:#1a2347}
            .box{background:#f8fafc;border:1px solid #e4eaf3;border-radius:14px;padding:14px 16px}
            .summary{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
            .summary div{background:#f8fafc;border:1px solid #e4eaf3;border-radius:14px;padding:12px 14px}
            .summary strong{display:block;color:#1a2347;margin-bottom:4px}
            .signature{margin-top:28px;padding-top:18px;border-top:1px dashed #cfd8e3;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
            .sigbox{min-height:110px;border:1px solid #e4eaf3;border-radius:14px;padding:14px}
        </style>
    </head>
    <body>
        <div class="sheet">
            <div class="top">
                <div>
                    <div class="brand"><?= e($brandName) ?></div>
                    <div class="meta">Aperçu contrat de prestation</div>
                </div>
                <div class="meta">
                    <strong>Contrat</strong><br>Ref: <?= e($contractNumber) ?><br>Date: <?= e($today) ?>
                </div>
            </div>
            <div class="section">
                <h2>Client</h2>
                <div class="box">
                    <?php if ($statutUser === 'societe'): ?>
                        <strong><?= e($clientSociete) ?></strong> (SIRET: <?= e($clientSiret) ?>)<br>
                        Représentant : <?= e($clientNom) ?><br>
                    <?php else: ?>
                        <strong><?= e($clientNom) ?></strong><br>
                    <?php endif; ?>
                    <?= e($clientAdresse) ?><br>
                    <?= e($clientEmail) ?>
                </div>
            </div>
            <div class="section">
                <h2>Résumé financier du projet</h2>
                <div class="summary">
                    <div><strong>Titre du projet</strong><?= e($titre) ?></div>
                    <div><strong>Statut d'avancement</strong><?= e($statutProjet) ?> (<?= $avancement ?>%)</div>
                    <div><strong>Montant Total</strong><?= money($total) ?></div>
                    <div><strong>Acompte versé</strong><?= money($acompte) ?></div>
                    <div><strong>Solde restant</strong><?= money($solde) ?></div>
                </div>
            </div>
            <div class="signature">
                <div class="sigbox"><strong><?= e($brandName) ?></strong><br><br><br><hr></div>
                <div class="sigbox"><strong><?= e($clientNom) ?></strong><br><br><br><hr></div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

/* =========================
   TCPDF Custom Class (Premium Design)
   ========================= */
class SazulisPDF extends TCPDF {
    public ?string $logoPath = null;
    public float $watermarkAlpha = 0.14;
    public string $watermarkMode = 'tile';

    public function Header() {
        if ($this->logoPath && is_file($this->logoPath)) {
            $this->SetAlpha($this->watermarkAlpha);

            $pageW = $this->getPageWidth();
            $pageH = $this->getPageHeight();

            if ($this->watermarkMode === 'single') {
                $wmW = $pageW * 0.78;
                $x = ($pageW - $wmW) / 2;
                $y = ($pageH - $wmW) / 2.25;

                $this->StartTransform();
                $this->Rotate(15, $pageW/2, $pageH/2);
                $this->Image($this->logoPath, $x, $y, $wmW, 0, '', '', '', false, 300, '', false, false, 0, false, false, false);
                $this->StopTransform();
            } else {
                $tileW = 60;
                $stepX = 78;
                $stepY = 58;

                $this->StartTransform();
                $this->Rotate(25, $pageW/2, $pageH/2);

                for ($yy = -40; $yy < $pageH + 80; $yy += $stepY) {
                    for ($xx = -40; $xx < $pageW + 80; $xx += $stepX) {
                        $this->Image($this->logoPath, $xx, $yy, $tileW, 0, '', '', '', false, 300, '', false, false, 0, false, false, false);
                    }
                }
                $this->StopTransform();
            }
            $this->SetAlpha(1);
        }
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().' / '.$this->getAliasNbPages(), 0, 0, 'C');
    }
}

/* =========================
   PDF Generation Setup
   ========================= */
$pdf = new SazulisPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->logoPath = $logoPath;
$pdf->watermarkAlpha = $WATERMARK_ALPHA;
$pdf->watermarkMode  = $WATERMARK_MODE;

$pdf->SetCreator('Sazulis');
$pdf->SetAuthor('Sazulis');
$pdf->SetTitle('Contrat - ' . $titre);
$pdf->SetSubject('Contrat de prestation - ' . $titre);

$pdf->SetMargins(14, 16, 14);
$pdf->SetAutoPageBreak(true, 18);
$pdf->SetFont('helvetica', '', 10);

$pdf->AddPage();

/* =========================
   Header Boxed Design
   ========================= */
$pageW = $pdf->getPageWidth();
$marginL = 14;
$boxX = $marginL;
$boxY = 12;
$boxW = $pageW - ($marginL * 2);
$boxH = 32;

$pdf->SetFillColor(251, 250, 247);
$pdf->SetDrawColor(231, 207, 156);
$pdf->RoundedRect($boxX, $boxY, $boxW, $boxH, 4, '1111', 'DF');

$logoW = 22;
$logoPad = 8;
if ($logoPath && is_file($logoPath)) {
    $logoX = $boxX + $boxW - $logoW - $logoPad;
    $logoY = $boxY + 5;
    $pdf->Image($logoPath, $logoX, $logoY, $logoW, 0, '', '', '', false, 300, '', false, false, 0, false, false, false);
}

$pdf->SetTextColor(216, 179, 90);
$pdf->SetFont('helvetica', 'B', 18);
$pdf->SetXY($boxX + 8, $boxY + 6);
$pdf->Cell($boxW - 8 - ($logoW + $logoPad + 2), 8, $brandName . " • Contrat de prestation", 0, 2, 'L', false);

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(107, 90, 51);
$pdf->SetX($boxX + 8);
$pdf->Cell($boxW - 16, 6, "Contrat n° {$contractNumber} — Généré le {$today}", 0, 2, 'L', false);

$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(31, 31, 31);
$pdf->SetX($boxX + 8);
$pdf->MultiCell($boxW - 16, 6, "Projet : {$titre}    Statut : {$statutProjet}    Avancement : {$avancement}%", 0, 'L', false, 1);

$pdf->Ln(4);

/* =========================
   Blocs de Coordonnées
   ========================= */
if ($statutUser === 'societe') {
    $clientBloc = "<b>Société :</b> ".e($clientSociete)."<br>
                   <b>Représentant :</b> ".e($clientNom)."<br>
                   <b>SIRET :</b> ".e($clientSiret)."<br>
                   <b>Adresse :</b> ".e($clientAdresse)."<br>
                   <b>Email :</b> ".e($clientEmail);
} else {
    $clientBloc = "<b>Client :</b> ".e($clientNom)."<br>
                   <b>Adresse :</b> ".e($clientAdresse)."<br>
                   <b>Email :</b> ".e($clientEmail);
}

$prestBloc = "<b>{$brandName}</b><br>
              <b>Statut :</b> ".e($brandLegalStatus)."<br>
              <b>{$brandSirenSiret}</b><br>
              <b>".e($brandAddressLine)."</b><br>
              <b>Email :</b> ".e($brandEmail)."<br>
              <b>Site :</b> ".e($brandWebsite);

/* =========================
   CSS Styles
   ========================= */
$css = <<<CSS
<style>
  .wrap { font-family: helvetica; color: {$dark}; }
  .section { margin-top: 10px; border: 1px solid {$gold2}; border-radius: 12px; padding: 10px; background: #fff; }
  .h { font-size: 12.5px; font-weight: bold; color: {$muted}; margin-bottom: 6px; }
  .small { font-size: 9.5px; color: #666; }
  .two { width: 100%; }
  .col { width: 48%; display:inline-block; vertical-align: top; }
  .right { margin-left: 4%; }
  .table { width: 100%; border-collapse: collapse; margin-top: 6px; }
  .table th { background: {$gold}; color: #fff; padding: 7px; text-align: left; font-size: 9.5px; text-transform:uppercase; letter-spacing:.4px; }
  .table td { border-bottom: 1px solid #eee; padding: 7px; font-size: 9.8px; }
  ul { margin: 0; padding-left: 16px; }
  li { margin-bottom: 3px; }
  .sign { margin-top: 12px; border: 1px dashed {$gold2}; border-radius: 12px; padding: 10px; background: #fff; }
  .sigbox { width: 48%; display:inline-block; vertical-align: top; }
  .sigline { margin-top: 24px; border-top: 1px solid #bbb; width: 95%; }
  .hint { color:#777; font-size:9px; margin-top:6px; }
</style>
CSS;

/* ---- 1) Parties ---- */
$sec1 = $css . <<<HTML
<div class="wrap">
  <div class="section">
    <div class="h">1) Parties</div>
    <div class="two">
      <div class="col">
        {$prestBloc}<br>
        <span class="small">Ci-après “le Prestataire”.</span>
      </div>
      <div class="col right">
        {$clientBloc}<br>
        <span class="small">Ci-après “le Client”.</span>
      </div>
    </div>
  </div>
</div>
HTML;
writeBlockKeepTogether($pdf, $sec1);

/* ---- 2) Objet ---- */
$sec2 = $css . <<<HTML
<div class="wrap">
  <div class="section">
    <div class="h">2) Objet</div>
    Le présent contrat encadre la prestation de développement web relative au projet <b>{TITRE}</b>, incluant la conception,
    le développement, les corrections raisonnables et la livraison conformément aux conditions ci-dessous.
  </div>
</div>
HTML;
$sec2 = str_replace('{TITRE}', e($titre), $sec2);
writeBlockKeepTogether($pdf, $sec2);

/* ---- 3) Périmètre, livrables & hors périmètre ---- */
$sec3 = $css . <<<HTML
<div class="wrap">
  <div class="section">
    <div class="h">3) Périmètre, livrables & hors périmètre</div>
    <ul>
      <li>Le périmètre est celui défini par la commande / devis accepté (fonctionnalités, pages, options, modules).</li>
      <li>Toute demande non prévue (ajouts, refonte, fonctionnalités supplémentaires) constitue du <b>hors périmètre</b> et fera l’objet d’un devis/avenant.</li>
      <li>Les livrables peuvent inclure : code source, interface, back-office, mise en ligne, documentation courte selon l’offre.</li>
    </ul>
  </div>
</div>
HTML;
writeBlockKeepTogether($pdf, $sec3);

/* ---- 4) Recette, validations & acceptation ---- */
$sec4 = $css . <<<HTML
<div class="wrap">
  <div class="section">
    <div class="h">4) Recette, validations & acceptation</div>
    <ul>
      <li>Une phase de <b>recette</b> est prévue : le Client vérifie les livrables et remonte les anomalies sous 7 jours.</li>
      <li>À défaut de retour sous 7 jours, les livrables sont réputés <b>acceptés</b>.</li>
      <li>Les corrections “raisonnables” couvrent les bugs et écarts au périmètre, pas les changements d’avis ou ajout de fonctionnalités.</li>
    </ul>
  </div>
</div>
HTML;
writeBlockKeepTogether($pdf, $sec4);

/* ---- 5) Prix, paiement & suspension ---- */
$sec5 = $css . <<<HTML
<div class="wrap">
  <div class="section">
    <div class="h">5) Prix, paiement & suspension</div>
    <table class="table">
      <thead>
        <tr><th>Élément</th><th>Montant</th><th>Modalité</th></tr>
      </thead>
      <tbody>
        <tr><td>Total prestation</td><td><b>{TOTAL}</b></td><td>Selon commande</td></tr>
        <tr><td>Acompte</td><td><b>{ACOMPTE}</b></td><td>Avant démarrage</td></tr>
        <tr><td>Solde</td><td><b>{SOLDE}</b></td><td>Avant livraison / mise en production</td></tr>
      </tbody>
    </table>
    <ul>
      <li>Le démarrage du projet est conditionné au paiement de l’acompte, sauf accord écrit contraire.</li>
      <li><b>Acompte :</b> l’acompte couvre la réservation de créneau, la phase de cadrage et le lancement des travaux. Sauf disposition légale impérative applicable (notamment en B2C), l’acompte est réputé non remboursable à l’issue d’un délai de 7 jours suivant son paiement.</li>
      <li>En cas de retard de paiement, le Prestataire peut <b>suspendre</b> la prestation et/ou la livraison jusqu’à régularisation.</li>
    </ul>
  </div>
</div>
HTML;

$sec5 = str_replace(
    ['{TOTAL}','{ACOMPTE}','{SOLDE}'],
    [money($total), money($acompte), money($solde)],
    $sec5
);
writeBlockKeepTogether($pdf, $sec5);

/* ---- 6) Obligations du Client ---- */
$sec6 = $css . <<<HTML
<div class="wrap">
  <div class="section">
    <div class="h">6) Obligations du Client (contenus & accès)</div>
    <ul>
      <li>Le Client fournit textes, images, logos, accès (hébergeur, DNS, emails, comptes API) dans des délais raisonnables.</li>
      <li>Le Prestataire n’est pas responsable des retards dus à l’absence de contenus, de validations ou d’accès techniques.</li>
      <li>Le Client garantit disposer des droits sur les contenus fournis (images, textes, marques).</li>
    </ul>
  </div>
</div>
HTML;
writeBlockKeepTogether($pdf, $sec6);

/* ---- 7) Hébergement, services tiers & sécurité ---- */
$sec7 = $css . <<<HTML
<div class="wrap">
  <div class="section">
    <div class="h">7) Hébergement, services tiers & sécurité</div>
    <ul>
      <li>Les services tiers (hébergeur, registrar, paiement, plugins, API) restent sous leurs propres conditions et limitations.</li>
      <li>Le Prestataire ne peut être tenu responsable d’une panne/incident causé par un tiers (OVH, Stripe, DNS, etc.).</li>
      <li>Le Prestataire met en œuvre des bonnes pratiques, mais aucune sécurité n’est absolue ; le Client doit gérer ses accès et mots de passe.</li>
    </ul>
  </div>
</div>
HTML;
writeBlockKeepTogether($pdf, $sec7);

/* ---- 8) Propriété intellectuelle ---- */
$sec8 = $css . <<<HTML
<div class="wrap">
  <div class="section">
    <div class="h">8) Propriété intellectuelle</div>
    <ul>
      <li>Les livrables spécifiques au projet deviennent la propriété du Client après paiement intégral.</li>
      <li>Le Prestataire conserve la propriété de ses outils, bibliothèques et composants génériques réutilisables.</li>
      <li>Sauf opposition écrite, le Prestataire peut citer le projet comme référence (portfolio), sans divulguer d’informations sensibles.</li>
    </ul>
  </div>
</div>
HTML;
writeBlockKeepTogether($pdf, $sec8);

/* ---- 9) Maintenance, support & évolutions ---- */
$sec9 = $css . <<<HTML
<div class="wrap">
  <div class="section">
    <div class="h">9) Maintenance, support & évolutions</div>
    <ul>
      <li>Sauf mention contraire, la maintenance n’est pas incluse dans la prestation initiale.</li>
      <li>Les demandes d’évolution, d’assistance et de correction hors recette feront l’objet d’un devis/forfait.</li>
      <li>Les mises à jour (CMS/plugins/serveur) peuvent nécessiter des interventions facturables si non incluses.</li>
    </ul>
  </div>
</div>
HTML;
writeBlockKeepTogether($pdf, $sec9);

/* ---- 10) Responsabilités ---- */
$sec10 = $css . <<<HTML
<div class="wrap">
  <div class="section">
    <div class="h">10) Responsabilités & limitation</div>
    <ul>
      <li>Le Prestataire est tenu à une obligation de moyens.</li>
      <li>Le Prestataire ne saurait être responsable des pertes indirectes (perte de CA, données, exploitation, réputation…).</li>
      <li>En cas de dommage prouvé imputable au Prestataire, l’indemnisation est limitée au <b>montant effectivement payé</b> par le Client.</li>
    </ul>
    <div class="small">Certaines limitations peuvent être encadrées par la loi selon le type de Client (B2C/B2B).</div>
  </div>
</div>
HTML;
writeBlockKeepTogether($pdf, $sec10);

/* ---- 11) Résiliation ---- */
$sec11 = $css . <<<HTML
<div class="wrap">
  <div class="section">
    <div class="h">11) Résiliation</div>
    <ul>
      <li>Résiliation possible en cas de manquement grave après mise en demeure restée sans effet 15 jours.</li>
      <li>Si le Client met fin au projet, les travaux réalisés restent dus et facturables au prorata.</li>
    </ul>
  </div>
</div>
HTML;
writeBlockKeepTogether($pdf, $sec11);

/* ---- 12) Droit applicable & litiges ---- */
$sec12 = $css . <<<HTML
<div class="wrap">
  <div class="section">
    <div class="h">12) Droit applicable & litiges</div>
    <ul>
      <li>Les parties privilégient une résolution amiable (échanges écrits, médiation).</li>
      <li>À défaut, les tribunaux compétents seront ceux du ressort du Prestataire : <b>{COURT}</b>, sauf disposition légale impérative contraire.</li>
    </ul>
  </div>
</div>
HTML;
$sec12 = str_replace('{COURT}', e($brandCityForCourt), $sec12);
writeBlockKeepTogether($pdf, $sec12);

/* ---- Bloc Signatures ---- */
$signHtml = $css . <<<HTML
<div class="wrap">
  <div class="sign">
    <div class="h">Signatures</div>
    <div class="sigbox">
      <b>Le Prestataire</b><br>{$brandName}<br>
      <div class="sigline"></div>
      <span class="small">Signature / Cachet</span>
    </div>
    <div class="sigbox" style="margin-left:4%;">
      <b>Le Client</b><br>{CLIENT}<br>
      <div class="sigline"></div>
      <span class="small" style="color: #ff0000; font-weight: bold; text-transform: uppercase;">
        Le signataire reconnaît avoir lu le présent contrat et déclare en accepter expressément l’ensemble des clauses, sans réserve ni restriction.
      </span>
      <div class="hint">Si vous avez signé en ligne, votre signature apparaîtra ci-dessus.</div>
      <div class="hint">Si vous le désirez, vous pouvez également imprimer ce document pour le signer manuellement et me le renvoyer par mail à contact@sazulis.fr.</div>
    </div>
    <div style="margin-top:8px" class="small">
      Date du projet : {PDATE} — Document généré le {$today}
    </div>
  </div>
</div>
HTML;

$signHtml = str_replace(
    ['{CLIENT}','{PDATE}'],
    [e($clientNom), e(substr($createdAt,0,10))],
    $signHtml
);
writeBlockKeepTogether($pdf, $signHtml);

/* =========================
   Tampon automatique prestataire
   ========================= */
if ($stampPath && is_file($stampPath)) {
    $last = $pdf->getNumPages();
    $pdf->setPage($last);

    $pageW = $pdf->getPageWidth();
    $pageH = $pdf->getPageHeight();

    $usableW = $pageW - 28;
    $leftColMaxX = $marginL + ($usableW * 0.48);

    $stampW = 25; 
    $stampX = min($marginL + 300, $leftColMaxX - $stampW - 2); 
    $stampY = $pageH - 137.8; 

    $stampX = max($marginL + 8, $stampX);
    $stampY = max(20, $stampY);

    $pdf->SetAlpha(0.90);
    $pdf->Image($stampPath, $stampX, $stampY, $stampW, 0, 'PNG', '', '', false, 300, '', false, false, 0, false, false, false);
    $pdf->SetAlpha(1);
}

/* =========================
   Signature dynamique Client
   ========================= */
if ($activeSignatureFs && is_file($activeSignatureFs)) {
    $last = $pdf->getNumPages();
    $pdf->setPage($last);

    $cfg = [
        'w' => 55,
        'x' => 125,
        'y' => 193,
    ];

    if (is_array($activeCfg)) {
        $cfg['w'] = isset($activeCfg['w']) ? (float)$activeCfg['w'] : $cfg['w'];
        $cfg['x'] = isset($activeCfg['x']) ? (float)$activeCfg['x'] : $cfg['x'];
        $cfg['y'] = isset($activeCfg['y']) ? (float)$activeCfg['y'] : $cfg['y'];
    }

    $x = (float)$cfg['x'];
    $y = (float)$cfg['y'];
    $w = (float)$cfg['w'];

    $w = max(20, min(100, $w));
    $x = max(5, $x);
    $y = max(5, $y);

    $pdf->Image($activeSignatureFs, $x, $y, $w, 0, 'PNG', '', '', false, 300, '', false, false, 0, false, false, false);
}

/* =========================
   PDF Output
   ========================= */
$fileName = 'Contrat_' . preg_replace('/[^a-zA-Z0-9_-]+/', '_', $contractNumber) . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: ' . ($isPreview ? 'inline' : 'attachment') . '; filename="' . $fileName . '"');
$pdf->Output($fileName, 'I');
exit;