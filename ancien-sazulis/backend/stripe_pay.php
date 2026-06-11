<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier

require_once __DIR__ . '/db.php';
// Stripe init centralisé (clé + Dotenv) -> doit faire Stripe::setApiKey(...)
require_once __DIR__ . '/../stripe_init.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
  http_response_code(500);
  echo json_encode(['error' => 'DB indisponible ($pdo).']);
  exit;
}

if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Non connecté.']);
  exit;
}

$userId = (int)$_SESSION['user_id'];

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '[]', true);
if (!is_array($data)) $data = [];

$projectId = isset($data['project_id']) ? (int)$data['project_id'] : 0;
$paymentType = isset($data['payment_type']) ? (string)$data['payment_type'] : 'acompte';
$paymentType = in_array($paymentType, ['acompte', 'solde'], true) ? $paymentType : 'acompte';

if ($projectId <= 0) {
  http_response_code(400);
  echo json_encode(['error' => 'project_id invalide.']);
  exit;
}

/**
 * Helpers safe (évite redeclare)
 */
if (!function_exists('tableExists')) {
  function tableExists(PDO $pdo, string $table): bool {
    try {
      $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
      $stmt->execute([$table]);
      return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
      return false;
    }
  }
}
if (!function_exists('columnExists')) {
  function columnExists(PDO $pdo, string $table, string $column): bool {
    try {
      $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
      $stmt->execute([$column]);
      return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
      return false;
    }
  }
}

try {
  // 1) Récupère le projet (source de vérité)
  $stmt = $pdo->prepare("
    SELECT
      id, id_utilisateur, titre, statut,
      total, acompte, solde,
      acompte_recu, contrat_signe, avancement
    FROM projets
    WHERE id = ?
    LIMIT 1
  ");
  $stmt->execute([$projectId]);
  $p = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$p) {
    http_response_code(404);
    echo json_encode(['error' => 'Projet introuvable.']);
    exit;
  }

  if ((int)($p['id_utilisateur'] ?? 0) !== $userId) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé à ce projet.']);
    exit;
  }

  $statut = (string)($p['statut'] ?? '');
  if ($statut !== 'accepté' && $statut !== 'accepte') {
    http_response_code(400);
    echo json_encode(['error' => 'Projet non accepté : paiement impossible.']);
    exit;
  }

  $total   = (float)($p['total'] ?? 0);
  $acompte = (float)($p['acompte'] ?? 0);

  // solde : soit champ solde, soit recalcul
  $solde = isset($p['solde']) ? (float)$p['solde'] : max(0, $total - $acompte);

  $acompteRecu  = (int)($p['acompte_recu'] ?? 0);
  $contratSigne = (int)($p['contrat_signe'] ?? 0);
  $avancement   = (int)($p['avancement'] ?? 0);

  $readyForDev = ($acompteRecu === 1 && $contratSigne === 1);

  // 2) Détermine le montant selon payment_type
  if ($paymentType === 'solde') {
    if (!$readyForDev || $avancement < 100) {
      http_response_code(403);
      echo json_encode(['error' => "Solde non payable tant que acompte+contrat ne sont pas validés et avancement = 100%."]);
      exit;
    }
    $amountToPay = $solde;
    $payLabel = 'Solde';
  } else {
    $amountToPay = $acompte;
    $payLabel = 'Acompte';
  }

  if ($amountToPay <= 0) {
    http_response_code(400);
    echo json_encode(['error' => $payLabel . ' invalide ou nul.']);
    exit;
  }

  // 3) Récupère un numéro de contrat/facture si possible (robuste)
  $contratNum = null;
  $factureNum = null;

  // Contrats : essaye id_projet puis projet_id
  if (tableExists($pdo, 'contrats')) {
    $colProjet = null;
    if (columnExists($pdo, 'contrats', 'id_projet')) $colProjet = 'id_projet';
    elseif (columnExists($pdo, 'contrats', 'projet_id')) $colProjet = 'projet_id';

    if ($colProjet) {
      $colNumero = columnExists($pdo, 'contrats', 'numero') ? 'numero' : null;
      $sql = $colNumero
        ? "SELECT id, `$colNumero` AS numero FROM contrats WHERE `$colProjet` = ? LIMIT 1"
        : "SELECT id FROM contrats WHERE `$colProjet` = ? LIMIT 1";
      $stmtC = $pdo->prepare($sql);
      $stmtC->execute([$projectId]);
      $contrat = $stmtC->fetch(PDO::FETCH_ASSOC);

      if ($contrat) {
        if (!empty($contrat['numero'])) $contratNum = (string)$contrat['numero'];
        elseif (!empty($contrat['id'])) $contratNum = (string)$contrat['id'];
      }
    }
  }

  /**
   * Factures :
   * - Dans TON profil.php tu fais : factures f INNER JOIN commandes c ON f.id_commande = c.id
   *   donc souvent factures est liée à commandes (id_commande), pas forcément à projets.
   *
   * On tente dans cet ordre :
   *  A) factures.id_projet / factures.projet_id si ça existe
   *  B) sinon via commandes : commandes.id_utilisateur = userId (dernier) OU si tu as commandes.id_projet
   */
  if (tableExists($pdo, 'factures')) {
    $colProjetF = null;
    if (columnExists($pdo, 'factures', 'id_projet')) $colProjetF = 'id_projet';
    elseif (columnExists($pdo, 'factures', 'projet_id')) $colProjetF = 'projet_id';

    // A) direct
    if ($colProjetF) {
      $colNumeroF = columnExists($pdo, 'factures', 'numero') ? 'numero' : null;
      $sqlF = $colNumeroF
        ? "SELECT id, `$colNumeroF` AS numero FROM factures WHERE `$colProjetF` = ? ORDER BY id DESC LIMIT 1"
        : "SELECT id FROM factures WHERE `$colProjetF` = ? ORDER BY id DESC LIMIT 1";
      $stmtF = $pdo->prepare($sqlF);
      $stmtF->execute([$projectId]);
      $facture = $stmtF->fetch(PDO::FETCH_ASSOC);
      if ($facture) {
        if (!empty($facture['numero'])) $factureNum = (string)$facture['numero'];
        elseif (!empty($facture['id'])) $factureNum = (string)$facture['id'];
      }
    }

    // B) via commandes
    if (!$factureNum && tableExists($pdo, 'commandes') && columnExists($pdo, 'factures', 'id_commande')) {
      // si commandes a id_projet/projet_id, on essaie le match exact projet
      $colCmdProjet = null;
      if (columnExists($pdo, 'commandes', 'id_projet')) $colCmdProjet = 'id_projet';
      elseif (columnExists($pdo, 'commandes', 'projet_id')) $colCmdProjet = 'projet_id';

      if ($colCmdProjet) {
        $sql = "
          SELECT f.id, f.numero
          FROM factures f
          INNER JOIN commandes c ON f.id_commande = c.id
          WHERE c.`$colCmdProjet` = ?
          ORDER BY f.id DESC
          LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$projectId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
          $factureNum = !empty($row['numero']) ? (string)$row['numero'] : (string)$row['id'];
        }
      } else {
        // fallback : dernière facture du user (moins précis, mais évite crash)
        if (columnExists($pdo, 'commandes', 'id_utilisateur')) {
          $sql = "
            SELECT f.id, f.numero
            FROM factures f
            INNER JOIN commandes c ON f.id_commande = c.id
            WHERE c.id_utilisateur = ?
            ORDER BY f.id DESC
            LIMIT 1
          ";
          $stmt = $pdo->prepare($sql);
          $stmt->execute([$userId]);
          $row = $stmt->fetch(PDO::FETCH_ASSOC);
          if ($row) {
            $factureNum = !empty($row['numero']) ? (string)$row['numero'] : (string)$row['id'];
          }
        }
      }
    }
  }

  // 4) Label Stripe
  $titre = (string)($p['titre'] ?? '');
  $baseLabel = $titre !== '' ? $titre : ("Projet #{$projectId}");

  $label = "{$payLabel} - {$baseLabel}";
  if ($contratNum) $label .= " | Contrat #{$contratNum}";
  if ($factureNum) $label .= " | Facture #{$factureNum}";

  // 5) URLs retour
  $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

  // Optionnel : tu peux ajouter &type=solde/acompte pour afficher un message côté profil
  $successUrl = $protocol . '://' . $host . '/pages/profil.php?payment=success&type=' . urlencode($paymentType);
  $cancelUrl  = $protocol . '://' . $host . '/pages/profil.php?payment=cancel&type=' . urlencode($paymentType);

  // 6) Stripe Checkout
  $session = \Stripe\Checkout\Session::create([
    'payment_method_types' => ['card'],
    'line_items' => [[
      'price_data' => [
        'currency' => 'eur',
        'product_data' => [
          'name' => $label,
          'description' => 'Paiement ' . $paymentType . ' - Projet #' . $projectId,
        ],
        'unit_amount' => (int) round($amountToPay * 100),
      ],
      'quantity' => 1,
    ]],
    'mode' => 'payment',
    'metadata' => [
      'project_id'    => (string)$projectId,
      'user_id'       => (string)$userId,
      'payment_type'  => (string)$paymentType, // ✅ important
    ],
    'success_url' => $successUrl,
    'cancel_url'  => $cancelUrl,
  ]);

  echo json_encode(['checkoutUrl' => $session->url]);
  exit;

} catch (\Stripe\Exception\ApiErrorException $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Stripe API: ' . $e->getMessage()]);
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Serveur: ' . $e->getMessage()]);
  exit;
}
