<?php
declare(strict_types=1);
session_start();
include_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier

/**
 * -----------------------------
 * CONFIG
 * -----------------------------
 */

// 1) Chemin TCPDF (à adapter selon ton installation)
// Si tu as TCPDF dans vendor (composer) : require __DIR__ . '/../vendor/autoload.php';
// Sinon si tu as le dossier tcpdf : require_once __DIR__ . '/../tcpdf/tcpdf.php';

$tcpdfPathVendor = __DIR__ . '/../vendor/autoload.php';
$tcpdfPathLocal  = __DIR__ . '/../tcpdf/tcpdf.php';

if (is_file($tcpdfPathVendor)) {
    require_once $tcpdfPathVendor;
} elseif (is_file($tcpdfPathLocal)) {
    require_once $tcpdfPathLocal;
} else {
    die("TCPDF introuvable. Installe TCPDF (vendor/autoload.php ou ../tcpdf/tcpdf.php).");
}

// 2) DB
require_once __DIR__ . '/../backend/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("Erreur : \$pdo indisponible. Vérifie backend/db.php");
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// 3) Helpers
function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
function money($n): string {
    return number_format((float)$n, 2, ',', ' ') . ' €';
}

// 4) Auth : utilisateur connecté obligatoire
if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    die("Accès refusé : utilisateur non connecté.");
}
$userId = (int)$_SESSION['user_id'];

// 5) Param projet_id
$projetId = isset($_GET['projet_id']) ? (int)$_GET['projet_id'] : 0;
if ($projetId <= 0) {
    http_response_code(400);
    die("Paramètre projet_id manquant.");
}

/**
 * -----------------------------
 * RÉCUPÉRATION DES DONNÉES
 * -----------------------------
 */

// Utilisateur
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
    http_response_code(404);
    die("Utilisateur introuvable.");
}

// Projet : doit appartenir à l'utilisateur (sauf si tu veux autoriser admin/superadmin)
$stmt = $pdo->prepare("SELECT * FROM projets WHERE id = ? AND id_utilisateur = ?");
$stmt->execute([$projetId, $userId]);
$projet = $stmt->fetch();
if (!$projet) {
    http_response_code(404);
    die("Projet introuvable ou accès interdit.");
}

// Optionnel : n'autoriser le contrat que si accepté
$statutProjet = (string)($projet['statut'] ?? '');
if (!in_array($statutProjet, ['accepté','accepte','en_dev','demande','termine','livre'], true)) {
    // si ton enum est différent, ajuste ici
}

// Champs projet robustes
$titre = (string)($projet['titre'] ?? $projet['nom'] ?? ('Projet #' . $projetId));
$total = (float)($projet['total'] ?? 0);
$acompte = (float)($projet['acompte'] ?? 0);
$solde = (float)($projet['solde'] ?? max(0, $total - $acompte));
$avancement = (int)($projet['avancement'] ?? 0);
$createdAt = (string)($projet['created_at'] ?? date('Y-m-d H:i:s'));

// Infos client (particulier/société)
$statutUser = (string)($user['statut'] ?? 'particulier');

$clientNom = (string)($user['nom'] ?? '');
$clientEmail = (string)($user['email'] ?? '');
$clientAdresse = '';

if ($statutUser === 'societe') {
    $societe = trim((string)($user['nom_societe'] ?? ''));
    $siret = trim((string)($user['siret'] ?? ''));
    $adresseSociete = trim((string)($user['adresse_societe'] ?? ''));
    $clientAdresse = $adresseSociete;
} else {
    $clientAdresse = trim((string)($user['adresse'] ?? ''));
}

/**
 * -----------------------------
 * DESIGN / COULEURS
 * -----------------------------
 */
$brandName = "Sazulis";
$brandEmail = "sazulis@outlook.fr";
$brandWebsite = "sazulis.fr";

// palette proche de ton site
$gold = '#D8B35A';
$gold2 = '#E7CF9C';
$dark = '#1F1F1F';
$muted = '#6B5A33';
$bg = '#FBFAF7';

/**
 * -----------------------------
 * TCPDF SETUP
 * -----------------------------
 */
class SazulisPDF extends TCPDF {
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().' / '.$this->getAliasNbPages(), 0, 0, 'C');
    }
}

$pdf = new SazulisPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Meta
$pdf->SetCreator('Sazulis');
$pdf->SetAuthor('Sazulis');
$pdf->SetTitle('Contrat - ' . $titre);
$pdf->SetSubject('Contrat de prestation - ' . $titre);

// Marges
$pdf->SetMargins(14, 16, 14);
$pdf->SetAutoPageBreak(true, 18);

// Police
$pdf->SetFont('helvetica', '', 10);

$pdf->AddPage();

/**
 * -----------------------------
 * HTML DU CONTRAT (Design)
 * -----------------------------
 */
$contractNumber = 'CT-' . date('Y') . '-' . str_pad((string)$projetId, 5, '0', STR_PAD_LEFT);

$clientBloc = ($statutUser === 'societe')
    ? "<b>Société :</b> " . e((string)($user['nom_societe'] ?? '')) . "<br>
       <b>Représentant :</b> " . e($clientNom) . "<br>
       <b>SIRET :</b> " . e((string)($user['siret'] ?? '')) . "<br>
       <b>Adresse :</b> " . e($clientAdresse) . "<br>
       <b>Email :</b> " . e($clientEmail)
    : "<b>Client :</b> " . e($clientNom) . "<br>
       <b>Adresse :</b> " . e($clientAdresse) . "<br>
       <b>Email :</b> " . e($clientEmail);

$today = (new DateTimeImmutable('now'))->format('d/m/Y');

$html = <<<HTML
<style>
  .wrap { font-family: helvetica; color: {$dark}; }
  .header {
    border: 1px solid {$gold2};
    border-radius: 14px;
    padding: 14px;
    background: {$bg};
  }
  .brand {
    font-size: 20px;
    font-weight: bold;
    color: {$gold};
    letter-spacing: 1px;
  }
  .subtitle {
    color: {$muted};
    font-size: 11px;
    margin-top: 2px;
  }
  .pill {
    display:inline-block;
    padding: 4px 10px;
    border-radius: 999px;
    border: 1px solid {$gold2};
    color: {$muted};
    font-size: 10px;
    background: #fff;
  }
  .section {
    margin-top: 14px;
    border: 1px solid {$gold2};
    border-radius: 14px;
    padding: 12px;
    background: #fff;
  }
  .h {
    font-size: 13px;
    font-weight: bold;
    color: {$muted};
    margin-bottom: 6px;
  }
  .two {
    width: 100%;
  }
  .col {
    width: 48%;
    display:inline-block;
    vertical-align: top;
  }
  .right { margin-left: 4%; }
  .small { font-size: 10px; color: #666; }
  .table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 6px;
  }
  .table th {
    background: {$gold};
    color: #fff;
    padding: 8px;
    text-align: left;
    font-size: 10px;
  }
  .table td {
    border-bottom: 1px solid #eee;
    padding: 8px;
    font-size: 10px;
  }
  .sign {
    margin-top: 14px;
    border: 1px dashed {$gold2};
    border-radius: 14px;
    padding: 12px;
    background: #fff;
  }
  .sigbox {
    width: 48%;
    display:inline-block;
    vertical-align: top;
  }
  .sigline {
    margin-top: 28px;
    border-top: 1px solid #bbb;
    width: 95%;
  }
</style>

<div class="wrap">

  <div class="header">
    <div class="brand">{$brandName} • Contrat de prestation</div>
    <div class="subtitle">Contrat n° <b>{$contractNumber}</b> — Généré le {$today}</div>
    <div style="margin-top:8px;">
      <span class="pill">Projet : <b>{e($titre)}</b></span>
      <span class="pill">Statut projet : <b>{e($statutProjet ?: '—')}</b></span>
      <span class="pill">Avancement : <b>{$avancement}%</b></span>
    </div>
  </div>

  <div class="section">
    <div class="h">1) Parties</div>
    <div class="two">
      <div class="col">
        <b>Prestataire :</b> {$brandName}<br>
        <b>Email :</b> {$brandEmail}<br>
        <b>Site :</b> {$brandWebsite}<br>
        <span class="small">Ci-après “le Prestataire”.</span>
      </div>
      <div class="col right">
        {$clientBloc}<br>
        <span class="small">Ci-après “le Client”.</span>
      </div>
    </div>
  </div>

  <div class="section">
    <div class="h">2) Objet</div>
    Le présent contrat encadre la réalisation du projet <b>{e($titre)}</b>, incluant l’analyse, la conception, le développement,
    les corrections raisonnables et la livraison selon les modalités définies ci-dessous.
  </div>

  <div class="section">
    <div class="h">3) Périmètre & livrables</div>
    <ul>
      <li>Recueil des besoins et cadrage.</li>
      <li>Conception (UI/UX) si incluse au devis.</li>
      <li>Développement et intégration.</li>
      <li>Phase de recette et corrections.</li>
      <li>Livraison finale et mise en ligne selon l’offre choisie.</li>
    </ul>
    <span class="small">Le périmètre exact peut être complété dans une annexe (cahier des charges) si nécessaire.</span>
  </div>

  <div class="section">
    <div class="h">4) Prix & paiement</div>
    <table class="table">
      <thead>
        <tr>
          <th>Élément</th>
          <th>Montant</th>
          <th>Modalité</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Total prestation</td>
          <td><b>{money($total)}</b></td>
          <td>Selon devis / commande</td>
        </tr>
        <tr>
          <td>Acompte</td>
          <td><b>{money($acompte)}</b></td>
          <td>À régler pour démarrage</td>
        </tr>
        <tr>
          <td>Solde</td>
          <td><b>{money($solde)}</b></td>
          <td>À régler avant livraison</td>
        </tr>
      </tbody>
    </table>
    <div class="small" style="margin-top:6px;">
      Le démarrage du projet est conditionné au paiement de l’acompte, sauf accord écrit contraire.
    </div>
  </div>

  <div class="section">
    <div class="h">5) Délais</div>
    Les délais sont indicatifs et dépendent de la disponibilité du Client pour valider les étapes (maquettes, retours, contenus).
    Toute demande hors périmètre peut entraîner une révision des délais et/ou du budget.
  </div>

  <div class="section">
    <div class="h">6) Responsabilités & contenus</div>
    Le Client s’engage à fournir les contenus nécessaires (textes, images, accès). Le Prestataire ne pourra être tenu responsable
    des retards causés par l’absence de retours ou de contenus.
  </div>

  <div class="section">
    <div class="h">7) Propriété intellectuelle</div>
    Les livrables deviennent la propriété du Client après paiement intégral. Le Prestataire conserve les droits sur ses outils
    et composants génériques.
  </div>

  <div class="section">
    <div class="h">8) Résiliation</div>
    En cas de résiliation, les travaux déjà réalisés restent dus. Les éléments livrables partiels pourront être remis selon
    l’état d’avancement et le paiement effectué.
  </div>

  <div class="sign">
    <div class="h">Signatures</div>
    <div class="sigbox">
      <b>Le Prestataire</b><br>
      {$brandName}<br>
      <div class="sigline"></div>
      <span class="small">Signature / Cachet</span>
    </div>
    <div class="sigbox" style="margin-left:4%;">
      <b>Le Client</b><br>
      {e($clientNom)}<br>
      <div class="sigline"></div>
      <span class="small">Signature précédée de la mention “Lu et approuvé”</span>
    </div>
    <div style="margin-top:10px" class="small">
      Date projet : {e(substr($createdAt,0,10))} — Généré le {$today}
    </div>
  </div>

</div>
HTML;

// Attention: Dans HEREDOC on ne peut pas appeler directement e()/money() dans les {}.
// On remplace après :
$html = str_replace(
    ['{e($titre)}','{e($statutProjet ?: \'—\')}','{money($total)}','{money($acompte)}','{money($solde)}','{e($clientNom)}','{e(substr($createdAt,0,10))}'],
    [e($titre), e($statutProjet ?: '—'), money($total), money($acompte), money($solde), e($clientNom), e(substr($createdAt,0,10))],
    $html
);

// Render HTML
$pdf->writeHTML($html, true, false, true, false, '');

// Output
$fileName = 'Contrat_' . preg_replace('/[^a-zA-Z0-9_-]+/', '_', $contractNumber) . '.pdf';
$pdf->Output($fileName, 'I'); // I = inline dans le navigateur
exit;
