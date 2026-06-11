<?php
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier
if (session_status() === PHP_SESSION_NONE) session_start();
// Traitement validation commande (doit être AVANT tout output !)
if (isset($_POST['valider_commande']) && !empty($_SESSION['cart']) && isset($_SESSION['user_id'])) {
    require_once '../backend/db.php';
    $id_utilisateur = $_SESSION['user_id'];
    $cart = $_SESSION['cart'];
    $total = 0;
    foreach ($cart as $item) $total += $item['price'] * $item['qty'];
    $reduction = 0;
    if (!empty($_SESSION['used_promos'])) {
        // Applique la réduction si NEWS ou BYTE10
        if (in_array('NEWS', $_SESSION['used_promos'])) $reduction = round($total * 0.10, 2);
        elseif (in_array('BYTE10', $_SESSION['used_promos'])) $reduction = round($total * 0.05, 2);
    }
    $grandtotal_reduit = $total - $reduction;
    $acompte = round($grandtotal_reduit * 0.3, 2);
    $reste = $grandtotal_reduit - $acompte;
    $statut = 'en attente';
    // Insère la commande
    $stmt = $pdo->prepare('INSERT INTO commandes (id_utilisateur, total, statut) VALUES (?, ?, ?)');
    $stmt->execute([$id_utilisateur, $grandtotal_reduit, $statut]);
    $id_commande = $pdo->lastInsertId();
    // Insère chaque produit du panier avec try/catch pour debug
    try {
        foreach ($cart as $item) {
            // Vérifie si l'id produit existe en base
            $stmtProd = $pdo->prepare('SELECT id FROM produits WHERE id = ?');
            $stmtProd->execute([$item['id']]);
            $rowProd = $stmtProd->fetch();
            if (!$rowProd) {
                throw new Exception('Produit non trouvé en base pour id: ' . htmlspecialchars($item['id']));
            }
            $stmt = $pdo->prepare('INSERT INTO panier (id_utilisateur, id_produit, quantite, total, acompte, solde, remise, nom_remise, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $id_utilisateur,
                $item['id'],
                $item['qty'],
                $item['price'] * $item['qty'],
                round($item['price'] * $item['qty'] * 0.3, 2),
                round($item['price'] * $item['qty'] * 0.7, 2),
                $reduction,
                isset($item['promo']) ? $item['promo'] : '',
                'en_attente'
            ]);
        }
        unset($_SESSION['cart']);
        header('Location: profil.php');
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo 'Erreur lors de l\'insertion dans le panier : ' . $e->getMessage();
        exit;
    }
}

$lang = 'fr';
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en', 'es', 'de', 'it'])) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $lang;
} elseif (isset($_SESSION['lang']) && in_array($_SESSION['lang'], ['fr', 'en', 'es', 'de', 'it'])) {
    $lang = $_SESSION['lang'];
}
$t = [
    'fr' => [
        'title' => 'Mon panier',
        'main' => 'Votre panier',
        'empty' => 'Votre panier est vide.',
        'product' => 'Produit',
        'price' => 'Prix',
        'qty' => 'Quantité',
        'total' => 'Total',
        'remove' => 'Retirer',
        'checkout' => 'Valider la commande',
        'continue' => 'Continuer mes achats',
        'clear' => 'Vider le panier',
        'summary' => 'Résumé de la commande',
        'subtotal' => 'Sous-total',
        'tax' => 'TVA',
        'grandtotal' => 'Total à payer',
        'empty_confirm' => 'Êtes-vous sûr de vouloir vider le panier ?'
    ],
    'en' => [
        'title' => 'My Cart',
        'main' => 'Your Cart',
        'empty' => 'Your cart is empty.',
        'product' => 'Product',
        'price' => 'Price',
        'qty' => 'Quantity',
        'total' => 'Total',
        'remove' => 'Remove',
        'checkout' => 'Checkout',
        'continue' => 'Continue Shopping',
        'clear' => 'Clear Cart',
        'summary' => 'Order Summary',
        'subtotal' => 'Subtotal',
        'tax' => 'Tax',
        'grandtotal' => 'Grand Total',
        'empty_confirm' => 'Are you sure you want to clear the cart?'
    ],
    'es' => [
        'title' => 'Mi carrito',
        'main' => 'Tu carrito',
        'empty' => 'Tu carrito está vacío.',
        'product' => 'Producto',
        'price' => 'Precio',
        'qty' => 'Cantidad',
        'total' => 'Total',
        'remove' => 'Eliminar',
        'checkout' => 'Finalizar compra',
        'continue' => 'Seguir comprando',
        'clear' => 'Vaciar carrito',
        'summary' => 'Resumen del pedido',
        'subtotal' => 'Subtotal',
        'tax' => 'IVA',
        'grandtotal' => 'Total a pagar',
        'empty_confirm' => '¿Seguro que quieres vaciar el carrito?'
    ],
    'de' => [
        'title' => 'Mein Warenkorb',
        'main' => 'Ihr Warenkorb',
        'empty' => 'Ihr Warenkorb ist leer.',
        'product' => 'Produkt',
        'price' => 'Preis',
        'qty' => 'Menge',
        'total' => 'Gesamt',
        'remove' => 'Entfernen',
        'checkout' => 'Zur Kasse',
        'continue' => 'Weiter einkaufen',
        'clear' => 'Warenkorb leeren',
        'summary' => 'Bestellübersicht',
        'subtotal' => 'Zwischensumme',
        'tax' => 'MwSt',
        'grandtotal' => 'Gesamtsumme',
        'empty_confirm' => 'Sind Sie sicher, dass Sie den Warenkorb leeren möchten?'
    ],
    'it' => [
        'title' => 'Il mio carrello',
        'main' => 'Il tuo carrello',
        'empty' => 'Il tuo carrello è vuoto.',
        'product' => 'Prodotto',
        'price' => 'Prezzo',
        'qty' => 'Quantità',
        'total' => 'Totale',
        'remove' => 'Rimuovi',
        'checkout' => 'Procedi all’ordine',
        'continue' => 'Continua lo shopping',
        'clear' => 'Svuota carrello',
        'summary' => 'Riepilogo ordine',
        'subtotal' => 'Subtotale',
        'tax' => 'IVA',
        'grandtotal' => 'Totale da pagare',
        'empty_confirm' => 'Sei sicuro di voler svuotare il carrello?'
    ]
];


// Gestion des boutons + et - pour la quantité
if (isset($_POST['update_qty_id']) && isset($_SESSION['cart'])) {
    $updateId = $_POST['update_qty_id'];
    foreach ($_SESSION['cart'] as $k => $item) {
        if ((string)$k === (string)$updateId || (isset($item['id']) && (string)$item['id'] === (string)$updateId)) {
            $qty = isset($_POST['qty']) ? intval($_POST['qty']) : intval($item['qty']);
            if (isset($_POST['qty_minus'])) {
                $qty = max(1, $qty - 1);
            } elseif (isset($_POST['qty_plus'])) {
                $qty = min(99, $qty + 1);
            }
            $_SESSION['cart'][$k]['qty'] = $qty;
            break;
        }
    }
    // Réindexe le tableau pour éviter les trous
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    header('Location: panier.php');
    exit;
}
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
// Suppression d'un article du panier (doit être AVANT tout output !)
if ((isset($_GET['remove']) || isset($_POST['remove'])) && isset($_SESSION['cart'])) {
    $removeId = isset($_GET['remove']) ? $_GET['remove'] : $_POST['remove'];
    foreach ($_SESSION['cart'] as $k => $item) {
        if ((string)$k === (string)$removeId || (isset($item['id']) && (string)$item['id'] === (string)$removeId)) {
            unset($_SESSION['cart'][$k]);
            break;
        }
    }
    // Réindexe le tableau pour éviter les trous
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    header('Location: panier.php');
    exit;
}

// Vider le panier
if ((isset($_GET['clear']) || isset($_POST['clear'])) && isset($_SESSION['cart'])) {
    unset($_SESSION['cart']);
    header('Location: panier.php');
    exit;
}
include '../navbar.php';
function formatPrice($price)
{
    return number_format($price, 2, ',', ' ') . ' €';
}
$subtotal = 0;
foreach ($cart as $item) {
    if (!is_array($item) || !isset($item['price'], $item['qty']) || !is_numeric($item['price']) || !is_numeric($item['qty'])) continue;
    $subtotal += $item['price'] * $item['qty'];
}
$grandtotal = $subtotal;

// Gestion des codes promo
$reduction = 0;
$promo_message = '';
if (!isset($_SESSION['used_promos'])) $_SESSION['used_promos'] = [];
if (isset($_POST['promo_code'])) {
    $code = strtoupper(trim($_POST['promo_code']));
    if ($code === 'NEWS') {
        if (!in_array('NEWS', $_SESSION['used_promos'])) {
            $reduction = round($grandtotal * 0.10, 2);
            $_SESSION['used_promos'][] = 'NEWS';
            $promo_message = '<span style="color:green;font-weight:600;">Code NEWS appliqué : -10% (utilisable une seule fois)</span>';
        } else {
            $promo_message = '<span style="color:red;font-weight:600;">Le code NEWS a déjà été utilisé sur ce compte.</span>';
        }
    } else if ($code === 'BYTE10') {
        if (!in_array('BYTE10', $_SESSION['used_promos'])) {
            $reduction = round($grandtotal * 0.05, 2);
            $_SESSION['used_promos'][] = 'BYTE10';
            $promo_message = '<span style="color:green;font-weight:600;">Code BYTE10 appliqué : -5% (utilisable une seule fois)</span>';
        } else {
            $promo_message = '<span style="color:red;font-weight:600;">Le code BYTE10 a déjà été utilisé sur ce compte.</span>';
        }
    } else {
        $promo_message = '<span style="color:red;font-weight:600;">Code invalide</span>';
    }
}
$grandtotal_reduit = $grandtotal - $reduction;
$acompte = round($grandtotal_reduit * 0.3, 2);
$reste = $grandtotal_reduit - $acompte;

// Calcul acompte 30% et reste dû
$acompte = round($grandtotal * 0.3, 2);
$reste = $grandtotal - $acompte;
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <title><?= $t[$lang]['title'] ?> | Sazulis</title>
    <meta name="description" content="<?= $t[$lang]['main'] ?> - <?= $t[$lang]['summary'] ?>" />
    <meta name="keywords" content="sazulis, panier, cart, ecommerce, achat, commande, web, services, shop, boutique" />
    <link rel="icon" type="image/x-icon" href="../assets/img/sazulis-ico.ico">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: url('../assets/img/unique.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        /* === HERO === */
        .hero-section {
            padding: 4em 0 2em 0;
            text-align: center;
        }
        .hero-section h1 {
            font-size: 2.7em;
            font-weight: 900;
            color: #1a2347;
            margin-bottom: 0.3em;
        }
        .hero-section .subtitle {
            font-size: 1.3em;
            color: #333;
            margin-bottom: 1.2em;
        }
        .hero-section .cta {
            display: inline-block;
            font-size: 1.1em;
            padding: 0.8em 2em;
            background: #1a2347;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 0.5em;
            transition: background .2s;
        }
        .hero-section .cta:hover { background: #2e4080; }

        /* === LAYOUT PRINCIPAL === */
        .cart-wrapper {
            max-width: 1200px;
            margin: 0 auto 4em auto;
            padding: 0 1.5em;
            display: flex;
            gap: 2.5em;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        /* === LISTE DES ARTICLES === */
        .cart-items-panel {
            flex: 2 1 480px;
            min-width: 320px;
        }

        .cart-empty {
            background: rgba(255,255,255,0.97);
            border-radius: 20px;
            box-shadow: 0 4px 24px #1a234711;
            border: 2px solid #e0e7ff;
            padding: 3em 2em;
            text-align: center;
            color: #1a2347;
            font-size: 1.4em;
            font-weight: 700;
        }

        .cart-item-card {
            display: flex;
            align-items: center;
            gap: 1.5em;
            background: rgba(255,255,255,0.97);
            border-radius: 18px;
            box-shadow: 0 4px 20px #1a234711;
            border: 2px solid #e0e7ff;
            margin-bottom: 1.2em;
            padding: 1.2em 1.5em;
            transition: box-shadow .15s, transform .15s;
        }
        .cart-item-card:hover {
            box-shadow: 0 8px 32px #1a234722;
            transform: translateY(-2px);
        }

        .cart-item-img {
            width: 90px; height: 90px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 2px 8px #1a234722;
            flex-shrink: 0;
        }

        .cart-item-name {
            font-weight: 800;
            font-size: 1.1em;
            color: #1a2347;
            margin-bottom: 0.3em;
            line-height: 1.2;
        }
        .cart-item-ref { color: #888; font-size: 0.95em; }

        .cart-item-price { font-size: 1.05em; font-weight: 700; color: #333; min-width: 100px; text-align: center; }
        .cart-item-total { font-size: 1.1em; font-weight: 900; color: #1a2347; min-width: 100px; text-align: center; }

        .qty-btn {
            background: #e0e7ff; border: none; border-radius: 50%;
            width: 28px; height: 28px; font-size: 1.2em;
            color: #1a2347; cursor: pointer; transition: background .2s;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .qty-btn:hover { background: #1a2347; color: #fff; }
        .qty-input {
            width: 38px; text-align: center;
            border: 1.5px solid #1a234733; border-radius: 6px;
            padding: 0.2em 0.1em; font-size: 1em;
            background: #f5f7ff;
        }

        .remove-btn {
            background: none; border: none; cursor: pointer;
            color: #c8902e; padding: 0.3em;
            border-radius: 8px; transition: background .2s;
        }
        .remove-btn:hover { background: #ffeedd; }

        /* === ACTIONS PANIER === */
        .cart-actions {
            display: flex;
            gap: 1em;
            flex-wrap: nowrap;
            margin-top: 2em;
            align-items: center;
        }
        .btn-cart {
            display: inline-flex; align-items: center; gap: .5em;
            background: linear-gradient(90deg, #1a2347, #2e4080);
            color: #fff; border: none; border-radius: 10px;
            padding: 0.8em 1.8em; font-size: 1em; font-weight: 700;
            cursor: pointer; text-decoration: none;
            box-shadow: 0 2px 8px #1a234733;
            transition: opacity .2s, transform .15s;
        }
        .btn-cart:hover { opacity: .87; transform: translateY(-1px); }
        .btn-cart.danger { background: linear-gradient(90deg, #c0392b, #e74c3c); }
        .btn-cart.success { background: linear-gradient(90deg, #1a6b3a, #27ae60); }

        /* === RÉSUMÉ === */
        .cart-summary {
            flex: 1 1 320px;
            min-width: 280px;
            max-width: 400px;
            background: rgba(255,255,255,0.97);
            border-radius: 20px;
            box-shadow: 0 4px 24px #1a234711;
            border: 2px solid #e0e7ff;
            padding: 2em 1.8em 1.8em 1.8em;
            position: sticky;
            top: 2em;
            align-self: flex-start;
        }
        .cart-summary h3 {
            color: #1a2347;
            font-size: 1.35em;
            font-weight: 900;
            margin-bottom: 1.3em;
            text-align: center;
            letter-spacing: 0.01em;
        }
        .promo-form {
            display: flex; gap: 0.5em; margin-bottom: 1.2em;
        }
        .promo-input {
            flex: 1; padding: 0.6em 1em; border-radius: 8px;
            border: 1.5px solid #1a234733; font-size: 1em;
            background: #f5f7ff;
        }
        .promo-input:focus { outline: none; border-color: #1a2347; }
        .promo-btn {
            background: #1a2347; color: #fff; border: none;
            border-radius: 8px; padding: 0.6em 1.2em;
            font-weight: 700; cursor: pointer; transition: background .2s;
        }
        .promo-btn:hover { background: #2e4080; }

        .summary-row {
            display: flex; justify-content: space-between;
            margin-bottom: 0.8em; font-size: 1.05em; color: #444;
        }
        .summary-row.reduction { color: #27ae60; font-weight: 600; }
        .summary-divider { border: none; border-top: 1.5px solid #e0e7ff; margin: 1em 0; }
        .summary-row.grand-total {
            font-size: 1.25em; font-weight: 900;
            color: #1a2347; margin-top: 0.5em;
        }
        .summary-row.acompte { color: #c8902e; font-weight: 700; }

        /* Logos paiement */
        .payment-logos {
            margin-top: 1.5em;
            display: flex; flex-direction: column; gap: .8em; align-items: center;
        }
        .payment-logos-row {
            background: #f5f7ff; border-radius: 12px;
            padding: 0.8em 1.5em;
            display: flex; align-items: center; gap: 1.2em;
            justify-content: center; width: 100%;
        }
        .payment-logos-row img { height: 22px; object-fit: contain; filter: drop-shadow(0 0 2px #ccc); }
        .payment-logos-row img.tall { height: 38px; }
        .secure-label {
            color: #1a6b3a; font-weight: 700; font-size: 0.95em;
            text-align: center; margin-top: 0.5em;
        }

        /* === MODAL LOGIN === */
        .modal-login {
            display: none; position: fixed; z-index: 9999;
            inset: 0; align-items: center; justify-content: center;
        }
        .modal-login.open { display: flex; }
        .modal-login-backdrop {
            position: absolute; inset: 0;
            background: rgba(10,15,40,0.55);
            backdrop-filter: blur(3px);
        }
        .modal-login-content {
            position: relative; z-index: 1;
            background: #fff; border-radius: 18px;
            padding: 2.5em 2em; max-width: 400px; width: 90vw;
            box-shadow: 0 8px 40px #1a234744;
            text-align: center;
            animation: modalIn .25s cubic-bezier(.4,2,.6,1) 1;
        }
        @keyframes modalIn { from { transform: translateY(30px) scale(.97); opacity: 0; } to { transform: none; opacity: 1; } }
        .modal-login-close {
            position: absolute; top: 12px; right: 16px;
            background: none; border: none; font-size: 1.5em;
            cursor: pointer; color: #1a2347; line-height: 1;
        }
        .modal-login-close:hover { color: #c8902e; }
        .modal-login-content h2 { color: #1a2347; margin-bottom: .5em; }
        .modal-login-content a {
            display: inline-block; margin-top: 1em;
            background: #1a2347; color: #fff;
            padding: 0.7em 2em; border-radius: 8px;
            font-weight: 700; text-decoration: none;
            transition: background .2s;
        }
        .modal-login-content a:hover { background: #2e4080; }

        @media (max-width: 800px) {
            .cart-wrapper { flex-direction: column; }
            .cart-summary { max-width: 100%; position: static; }
        }
    </style>
</head>

<body>

    <!-- HERO -->
    <section class="hero-section">
        <h1>Votre panier</h1>
        <div class="subtitle">
            Retrouvez ici tous vos produits sélectionnés avant de finaliser votre commande.<br>
            <span style="color:#c8902e;font-weight:600;">Paiement 100% sécurisé</span>
        </div>
        <a href="/pages/products.php" class="cta">← Retour à la boutique</a>
    </section>

    <div class="cart-wrapper">

        <!-- LISTE DES ARTICLES -->
        <div class="cart-items-panel">
            <?php if (empty($cart)) { ?>
                <div class="cart-empty">🛒 <?= $t[$lang]['empty'] ?></div>
            <?php } else { ?>

                <?php foreach ($cart as $id => $item) {
                    if (!is_array($item) || !isset($item['price'], $item['qty'])) continue;
                ?>
                    <div class="cart-item-card">
                        <!-- Image -->
                        <div style="flex-shrink:0;">
                            <?php if (!empty($item['img'])): ?>
                                <img class="cart-item-img"
                                     src="<?= htmlspecialchars($item['img']) ?>"
                                     alt="<?= htmlspecialchars($item['name'] ?? '') ?>">
                            <?php endif; ?>
                        </div>

                        <!-- Nom + ref -->
                        <div style="flex:1;min-width:120px;">
                            <div class="cart-item-name">
                                <?php
                                $nomProduit = '';
                                if (!empty($item['id'])) {
                                    require_once '../backend/db.php';
                                    $stmt = $pdo->prepare('SELECT nom FROM produits WHERE id = ?');
                                    $stmt->execute([$item['id']]);
                                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                                    if ($row && !empty($row['nom'])) {
                                        $nomProduit = strip_tags($row['nom']);
                                    }
                                }
                                echo htmlspecialchars($nomProduit ?: 'Produit');
                                ?>
                            </div>
                            <div class="cart-item-ref">Ref: <?= htmlspecialchars($item['ref'] ?? '') ?></div>
                        </div>

                        <!-- Prix / Quantité -->
                        <div class="cart-item-price">
                            <?php
                            $showQty = !empty($nomProduit)
                                && stripos($nomProduit, 'Maintenance') === 0
                                && stripos($nomProduit, 'Urgente') === false;
                            if ($showQty): ?>
                                <form method="post" action="" style="display:inline-flex;align-items:center;gap:4px;">
                                    <input type="hidden" name="update_qty_id" value="<?= $id ?>">
                                    <button type="submit" name="qty_minus" class="qty-btn">–</button>
                                    <input type="number" name="qty" value="<?= intval($item['qty']) ?>"
                                           min="1" max="99" class="qty-input">
                                    <button type="submit" name="qty_plus" class="qty-btn">+</button>
                                </form>
                            <?php else: ?>
                                <?= isset($item['price']) && is_numeric($item['price']) ? formatPrice($item['price']) : '0,00 €' ?>
                            <?php endif; ?>
                        </div>

                        <!-- Total ligne -->
                        <div class="cart-item-total">
                            <?= (isset($item['price'], $item['qty']) && is_numeric($item['price']) && is_numeric($item['qty']))
                                ? formatPrice($item['price'] * $item['qty']) : '0,00 €' ?>
                        </div>

                        <!-- Supprimer -->
                        <div>
                            <form method="post" action="?remove=<?= $id ?>" style="display:inline;">
                                <button type="submit" class="remove-btn" title="<?= $t[$lang]['remove'] ?>">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                                        <rect x="5" y="5" width="14" height="14" rx="3" fill="#ffeedd" stroke="#c8902e" stroke-width="2"/>
                                        <path d="M9 9L15 15M15 9L9 15" stroke="#c8902e" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php } // fin foreach ?>

                <!-- Actions -->
                <div class="cart-actions">
                    <a href="/pages/products.php" class="btn-cart">← <?= $t[$lang]['continue'] ?></a>

                    <button type="button" class="btn-cart danger"
                            onclick="if(confirm('<?= $t[$lang]['empty_confirm'] ?>')) { document.getElementById('form-clear').submit(); }">
                        🗑️ <?= $t[$lang]['clear'] ?>
                    </button>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button type="button" class="btn-cart success"
                                onclick="document.getElementById('form-valider').submit()">
                            <?= $t[$lang]['checkout'] ?> →
                        </button>
                    <?php else: ?>
                        <a href="/pages/connexion.php" class="btn-cart success">
                            <?= $t[$lang]['checkout'] ?> →
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Forms cachés -->
                <form id="form-clear" method="post" action="?clear=1" style="display:none;"></form>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form id="form-valider" method="post" style="display:none;">
                        <input type="hidden" name="valider_commande" value="1">
                    </form>
                <?php endif; ?>

            <?php } // fin if empty cart ?>
        </div>

        <!-- RÉSUMÉ COMMANDE -->
        <aside class="cart-summary">
            <h3><?= $t[$lang]['summary'] ?></h3>

            <!-- Code promo -->
            <form method="post" class="promo-form">
                <input type="text" name="promo_code" class="promo-input"
                       placeholder="Code de réduction"
                       value="<?= htmlspecialchars($_POST['promo_code'] ?? '') ?>">
                <button type="submit" class="promo-btn">OK</button>
            </form>
            <?php if ($promo_message) echo '<div style="margin-bottom:1em;font-size:.95em;">' . $promo_message . '</div>'; ?>

            <!-- Lignes résumé -->
            <div class="summary-row">
                <span><?= $t[$lang]['subtotal'] ?></span>
                <span><?= formatPrice($subtotal) ?></span>
            </div>
            <?php if ($reduction > 0): ?>
                <div class="summary-row reduction">
                    <span>Réduction</span>
                    <span>-<?= formatPrice($reduction) ?></span>
                </div>
            <?php endif; ?>
            <hr class="summary-divider">
            <div class="summary-row acompte">
                <span>Acompte (30%)</span>
                <span><?= formatPrice($acompte) ?></span>
            </div>
            <div class="summary-row">
                <span>Reste dû</span>
                <span><?= formatPrice($reste) ?></span>
            </div>
            <hr class="summary-divider">
            <div class="summary-row grand-total">
                <span><?= $t[$lang]['grandtotal'] ?></span>
                <span><?= formatPrice($grandtotal_reduit) ?></span>
            </div>

            <!-- Logos paiement -->
            <div class="payment-logos">
                <div class="payment-logos-row">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/4/41/Visa_Logo.png" alt="Visa">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/0/04/Mastercard-logo.png" alt="Mastercard">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg" alt="PayPal">
                </div>
                <div class="payment-logos-row">
                    <img src="../assets/img/stripe.png" alt="Stripe" class="tall">
                    <img src="../assets/img/virement.png" alt="Virement bancaire" class="tall">
                </div>
                <div class="secure-label">🔒 Paiement 100% sécurisé</div>
            </div>
        </aside>

    </div><!-- /.cart-wrapper -->

    <!-- MODAL LOGIN -->
    <div id="modal-login" class="modal-login">
        <div class="modal-login-backdrop"
             onclick="document.getElementById('modal-login').classList.remove('open')"></div>
        <div class="modal-login-content">
            <button class="modal-login-close"
                    onclick="document.getElementById('modal-login').classList.remove('open')"
                    title="Fermer">✕</button>
            <h2>Connexion requise</h2>
            <p style="font-size:1.1em;margin-bottom:1em;color:#555;">
                Merci de vous connecter pour valider votre commande.
            </p>
            <a href="../pages/connexion.php">Se connecter</a>
        </div>
    </div>

    <script>
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape')
            document.getElementById('modal-login').classList.remove('open');
    });
    </script>

    <?php include '../footer.php'; ?>
</body>
</html>