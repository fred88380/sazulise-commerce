<?php
require_once __DIR__ . '/../../protect.php';
require_once __DIR__ . '/../../backend/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!function_exists('e')) {
    function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

// Récupère la ref depuis l'URL : /pages/produits/application-web-html
// Compatible avec ?ref=... ou URL propre via le nom du fichier appelé
$ref = trim($_GET['ref'] ?? basename($_SERVER['PHP_SELF'], '.php'));
$ref = preg_replace('/[^a-zA-Z0-9\-_]/', '', $ref); // sécurité

// Récupère le produit en BDD via la ref (colonne nom)
$stmt = $pdo->prepare('SELECT * FROM produits WHERE nom = ? LIMIT 1');
$stmt->execute([$ref]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(404);
    die('<h1>Produit introuvable</h1><a href="/pages/products.php">Retour à la boutique</a>');
}

// Nom affiché = description de la BDD, ref = nom de la BDD
$displayName = $product['description'] ?: $product['nom'];
$price       = (float)$product['prix'];
$imgSrc      = '../../' . ltrim($product['image'], '/');
$refProduit  = $product['nom'];
$productId   = (int)$product['id'];

// Ajouter au panier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $qty = max(1, intval($_POST['qty'] ?? 1));
    $cart_item = [
        'id'    => $productId,
        'name'  => $displayName,
        'price' => $price,
        'img'   => '../../' . ltrim($product['image'], '/'),
        'ref'   => $refProduit,
        'qty'   => $qty,
    ];
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] === $cart_item['id']) {
            $item['qty'] += $qty;
            $found = true;
            break;
        }
    }
    unset($item);
    if (!$found) $_SESSION['cart'][] = $cart_item;
    header('Location: ../panier.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<?php include '../../head.php'; ?>
<body style="background: url('../../assets/img/unique.png') center/cover no-repeat fixed; margin:0; font-family:'Segoe UI', Arial, sans-serif; min-height:100vh; overflow-x:hidden;">
<?php include '../../navbar.php'; ?>
<main style="background:transparent;margin-bottom:2em;">

    <!-- HERO -->
    <section style="padding:3em 0 2em 0;text-align:center;">
        <h1 style="font-size:2.2em;color:#1a2347;font-weight:900;margin-bottom:0.3em;">
            <?= e($displayName) ?>
        </h1>
        <div style="margin:1.2em auto;display:flex;justify-content:center;">
            <img src="<?= e($imgSrc) ?>" alt="<?= e($displayName) ?>"
                 style="max-width:220px;border-radius:16px;box-shadow:0 2px 8px #0001;background:#fff;">
        </div>
        <div style="font-size:2em;font-weight:900;color:#1a2347;margin-bottom:0.5em;">
            <?= number_format($price, 2, ',', ' ') ?> € TTC
        </div>
        <form method="post" style="margin-top:0.5em;">
            <input type="hidden" name="qty" value="1">
            <button type="submit" name="add_to_cart"
                    style="background:#1a2347;color:#fff;border:none;border-radius:10px;padding:0.8em 2.5em;font-size:1.1em;font-weight:700;cursor:pointer;box-shadow:0 2px 8px #1a234733;transition:background .2s;">
                Ajouter au panier
            </button>
        </form>
        <div style="margin-top:0.8em;color:#888;font-size:0.95em;">Réf : <?= e($refProduit) ?></div>

        <!-- Logos paiement -->
        <div style="margin-top:1.2em;display:flex;justify-content:center;gap:14px;align-items:center;flex-wrap:wrap;">
            <img src="../../assets/img/visa.png" alt="Visa" style="height:24px;">
            <img src="https://upload.wikimedia.org/wikipedia/commons/0/04/Mastercard-logo.png" alt="Mastercard" style="height:24px;">
            <img src="https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg" alt="PayPal" style="height:24px;">
            <img src="../../assets/img/stripe.png" alt="Stripe" style="height:40px;">
            <img src="../../assets/img/virement.png" alt="Virement" style="height:50px;">
            <span style="color:#1a6b3a;font-weight:600;font-size:0.95em;">🔒 Paiement 100% sécurisé</span>
        </div>
    </section>

</main>
<footer><?php include '../../footer.php'; ?></footer>
</body>
</html>
