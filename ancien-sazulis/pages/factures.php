<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="../assets/img/sazulis-ico.ico">
<?php
include_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier
require_once '../backend/db.php';
session_start();

// Fonction pour générer un numéro unique de facture par client
function genererNumeroFacture($userId, $pdo) {
	$annee = date('Y');
	// On compte le nombre de factures déjà générées pour ce client cette année
	$stmt = $pdo->prepare('SELECT COUNT(f.id) FROM factures f JOIN commandes c ON f.id_commande = c.id WHERE c.id_utilisateur = ? AND YEAR(f.date_emission) = ?');
	$stmt->execute([$userId, $annee]);
	$count = $stmt->fetchColumn();
	$numero = sprintf('F%04d-%d-%03d', $userId, $annee, $count + 1);
	return $numero;
}

// Exemple de génération de facture (à adapter selon ton workflow)
if (isset($_SESSION['user_id']) && isset($_POST['id_commande']) && isset($_POST['montant'])) {
	$userId = $_SESSION['user_id'];
	$id_commande = (int)$_POST['id_commande'];
	$montant = (float)$_POST['montant'];
	$numero = genererNumeroFacture($userId, $pdo);
	$stmt = $pdo->prepare('INSERT INTO factures (id_commande, numero, montant) VALUES (?, ?, ?)');
	$stmt->execute([$id_commande, $numero, $montant]);
	echo '<div style="color:green;">Facture générée avec le numéro : <b>' . htmlspecialchars($numero) . '</b></div>';
}

// Formulaire simple pour tester la génération
if (isset($_SESSION['user_id'])) {
?>
<form method="post">
	<label>ID Commande : <input type="number" name="id_commande" required></label><br>
	<label>Montant : <input type="number" step="0.01" name="montant" required></label><br>
	<button type="submit">Générer la facture</button>
</form>
<?php }

// --- Affichage des factures de l'utilisateur ---
if (isset($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
    $stmt = $pdo->prepare('SELECT f.* FROM factures f INNER JOIN commandes c ON f.id_commande = c.id WHERE c.id_utilisateur = ? ORDER BY f.id DESC');
    $stmt->execute([$userId]);
    $factures = $stmt->fetchAll();
    echo '<div class="facture-list" style="max-width:700px;margin:2em auto;">';
    echo '<h2>Mes factures</h2>';
    if (empty($factures)) {
        echo '<div class="notice">Aucune facture trouvée.</div>';
    } else {
        foreach($factures as $f) {
            echo '<div class="facture-item" style="background:#fffbe6;border:1px solid #e7cf9c;border-radius:14px;padding:1.2em;margin-bottom:1em;box-shadow:0 2px 8px #ffd70022;">';
            echo '<strong style="color:#b8860b;">Facture '.htmlspecialchars($f['numero'] ?? ('#'.$f['id'])).'</strong> ';
            echo '<span>Date : '.htmlspecialchars($f['date_emission'] ?? $f['created_at'] ?? $f['date'] ?? '').'</span> ';
            echo '<span>Total : '.number_format((float)($f['montant'] ?? $f['total'] ?? 0),2,',',' ').' €</span> ';
            echo '<a href="facture_pdf.php?id='.urlencode($f['id']).'" target="_blank" class="facture-btn" style="background:#ffe9c6;color:#b8860b;border:none;border-radius:10px;padding:0.4em 1.2em;font-weight:600;text-decoration:none;margin-left:1em;">Ouvrir PDF</a>';
            echo '<a href="../factures/signature_facture.php?facture_id='.urlencode($f['id']).'" target="_blank" class="facture-btn" style="background:#ffe9c6;color:#b8860b;">Signer</a>';
            echo '</div>';
        }
    }
    echo '<div style="margin-top:2em;"><a href="profil.php" class="facture-btn" style="background:#ffe9c6;color:#b8860b;">Retour au profil</a></div>';
    echo '</div>';
}
?>
