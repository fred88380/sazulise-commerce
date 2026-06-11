<?php
require_once __DIR__ . '/../../protect.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../backend/db.php';

$product = [
    'id' => 18,
    'name' => 'SEO Premium',
    'price' => 599.99,
    'img' => '../../assets/img/seo.png',
    'ref' => 'seo-premium',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $qty = max(1, intval($_POST['qty'] ?? 1));
    $cart_item = [
        'id' => $product['id'],
        'name' => $product['name'],
        'price' => $product['price'],
        'img' => $product['img'],
        'ref' => $product['ref'],
        'qty' => $qty,
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
    if (!$found) {
        $_SESSION['cart'][] = $cart_item;
    }
    header('Location: ../panier.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['name']) ?> - Sazulis</title>
    <meta name="description" content="Dominez votre marché sur Google avec notre Pack SEO Premium. Inclus : stratégie sur-mesure, optimisations techniques avancées, netlinking de premier plan et coaching expert.">
    <meta name="keywords" content="seo premium, strategie seo, netlinking expert, optimisation avancee, referencement entreprise, visibilite google, sazulis">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <?php
    $head_base = '../../';
    include '../../head.php';
    ?>
</head>
<body style="background: url('../../assets/img/unique.png') center/cover no-repeat fixed; margin:0; font-family:'Segoe UI', Arial, sans-serif; min-height:100vh; width:100vw; overflow-x:hidden;">
<?php include '../../navbar.php'; ?>
<main style="background:transparent;box-shadow:none;border-radius:0;margin-bottom:2em;">
    <section class="hero-clean" style="background: transparent;">
        <div style="background: transparent; box-shadow: none; border-radius: 0; padding: 2.5em 0 2em 0; text-align: center;">
            <div style="display:flex;flex-direction:column;align-items:center;gap:10px;">
                <h1 class="hero-clean-title" style="background: transparent; font-size:2.2em; color:#1a2347; font-weight:700; margin-bottom:0;">
                    <?= htmlspecialchars($product['name']) ?>
                </h1>
                <div style="background:#ffe066;color:#222;padding:8px 28px;border-radius:16px;font-weight:700;display:inline-block;font-size:1.25em;letter-spacing:1px;box-shadow:0 2px 8px #ffe06633;">
                    CROISSANCE &amp; DOMINATION GOOGLE
                </div>
            </div>
            <div style="margin:1.5em auto 1em auto; display:flex; justify-content:center; align-items:center;">
                <img src="<?= $product['img'] ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="max-width:220px;border-radius:16px;box-shadow:0 2px 8px #0001;background:#fff;">
            </div>
            <div class="hero-clean-desc" style="background: transparent; max-width:600px; margin:auto;">
                Pack SEO Premium pour une stratégie sur-mesure, des optimisations poussées et un accompagnement expert pour dominer votre marché.
            </div>
            <div style="margin-top:2em;display:flex;flex-direction:column;align-items:center;gap:0.5em;">
                <div style="display:flex;align-items:baseline;gap:1em;justify-content:center;flex-wrap:wrap;">
                    <span class="prix-normal" style="font-size:2em;font-weight:900;color:#d2691e;background:linear-gradient(90deg,#ffe9c6,#fffbe6 80%,#ffd700);padding:0.2em 0.8em;border-radius:1em;box-shadow:0 1px 8px #ffd70033;">
                        <?= number_format($product['price'], 2, ',', ' ') ?> € TTC
                    </span>
                </div>
                <form method="post" style="margin-top:0.5em;">
                    <input type="hidden" name="qty" value="1">
                    <button type="submit" name="add_to_cart" class="hero-clean-btn" style="background: transparent; color: #1a2347; border: 1px solid #c8902e; font-size:1.1em; padding:0.7em 2em; border-radius:8px; font-weight:700; cursor:pointer;">Ajouter au panier</button>
                </form>
            </div>
            <div style="margin-top:1em;color:#2a7ae2;font-size:1rem;">Réf : <?= htmlspecialchars($product['ref']) ?></div>
            <div style="margin-top:1em;display:flex;justify-content:center;gap:14px;align-items:center;">
                <img src="../../assets/img/visa.png" alt="Visa" style="height:28px;">
                <img src="https://upload.wikimedia.org/wikipedia/commons/0/04/Mastercard-logo.png" alt="Mastercard" style="height:28px;">
                <img src="https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg" alt="PayPal" style="height:28px;">
                <img src="../../assets/img/stripe.png" alt="Stripe" style="height:60px;">
                <img src="../../assets/img/virement.png" alt="Virement bancaire" style="height:80px;">
                <span style="color:#2a7ae2;font-weight:500;font-size:1rem;">Paiement 100% sécurisé</span>
            </div>
        </div>
    </section>
    <section style="background: none; padding: 2em 0 1em 0;">
        <ul class="hero-devweb-list" style="display:flex;flex-wrap:wrap;justify-content:center;gap:2em;list-style:none;padding:0;margin:0 auto 0 auto;max-width:900px;">
            <li>Stratégie SEO sur-mesure</li>
            <li>Optimisations techniques avancées</li>
            <li>Netlinking premium</li>
            <li>Rapports détaillés et analyses</li>
            <li>Coaching et accompagnement expert</li>
            <li>Support prioritaire</li>
        </ul>
    </section>
    <section style="max-width:700px;margin:2em auto 0;padding:20px;background:#f8f8f8;border-radius:10px;box-shadow:0 2px 8px #0001;">
        <h3 style="margin-top:0;font-size:1.2em;color:#1a2347;">Inclus dans le prix&nbsp;:</h3>
        <table style="width:100%;border-collapse:collapse;background:#fffaf0;border:1px solid #ffe066;color:#2a7ae2;font-size:1em;border-radius:8px;overflow:hidden;">
            <thead>
                <tr style="background:#ffe066;color:#222;">
                    <th style="padding:8px;border:1px solid #ffe066;text-align:left;">Service</th>
                    <th style="padding:8px;border:1px solid #ffe066;text-align:right;">Détail inclus</th>
                    <th style="padding:8px;border:1px solid #ffe066;text-align:right;">Prix inclus</th>
                    <th style="padding:8px;border:1px solid #ffe066;text-align:right;">Tarif sans offre (1 an)</th>
                </tr>
            </thead>
            <tbody>
                <tr style="background:#fffbe6;">
                    <td style="padding:8px;border:1px solid #ffe066;">Stratégie SEO personnalisée</td>
                    <td style="padding:8px;border:1px solid #ffe066;">1 stratégie incluse</td>
                    <td style="padding:8px;border:1px solid #ffe066;text-align:right;">299,99&nbsp;€</td>
                    <td style="padding:8px;border:1px solid #ffe066;text-align:right;">299,99&nbsp;€</td>
                </tr>
                <tr>
                    <td style="padding:8px;border:1px solid #ffe066;">Optimisations techniques avancées</td>
                    <td style="padding:8px;border:1px solid #ffe066;">Incluse</td>
                    <td style="padding:8px;border:1px solid #ffe066;text-align:right;">200,00&nbsp;€</td>
                    <td style="padding:8px;border:1px solid #ffe066;text-align:right;">300,00&nbsp;€</td>
                </tr>
                <tr style="background:#fffbe6;">
                    <td style="padding:8px;border:1px solid #ffe066;">Netlinking premium</td>
                    <td style="padding:8px;border:1px solid #ffe066;">Inclus</td>
                    <td style="padding:8px;border:1px solid #ffe066;text-align:right;">99,99&nbsp;€</td>
                    <td style="padding:8px;border:1px solid #ffe066;text-align:right;">199,99&nbsp;€</td>
                </tr>
            </tbody>
            <tfoot>
                <tr style="background:#ffe066;font-weight:bold;color:#222;">
                    <td colspan="2" style="padding:8px;border:1px solid #ffe066;text-align:right;">Total inclus</td>
                    <td style="padding:8px;border:1px solid #ffe066;text-align:right;">599,98&nbsp;€</td>
                    <td style="padding:8px;border:1px solid #ffe066;text-align:right;">799,98&nbsp;€</td>
                </tr>
            </tfoot>
        </table>
        <div style="background:#fffaf0;border:1px solid #ffe066;border-radius:8px;padding:14px 18px;margin-top:14px;color:#2a7ae2;font-size:1em;text-align:left;">
            Nos offres SEO Premium incluent une stratégie personnalisée, des optimisations avancées et un netlinking premium, pour des résultats exceptionnels et une domination sur votre marché. Le tarif “sans offre” correspond au prix standard si chaque service était acheté séparément. Packs conçus pour ambition, excellence et croissance maximale.
        </div>
    </section>
    <section style="display:flex;flex-wrap:wrap;gap:12px;margin:2em auto 0;justify-content:center;background:#fffbe6;padding:18px 0;border-radius:12px;max-width:900px;">
        <div style="background:linear-gradient(90deg,#ffe066,#ffb700);color:#222;padding:10px 18px;border-radius:8px;font-size:1rem;font-weight:500;box-shadow:0 2px 8px rgba(255,224,102,0.10);">Satisfait ou remboursé 30 jours</div>
        <div style="background:linear-gradient(90deg,#ffe066,#ffb700);color:#222;padding:10px 18px;border-radius:8px;font-size:1rem;font-weight:500;box-shadow:0 2px 8px rgba(255,224,102,0.10);">Support client 7j/7</div>
        <div style="background:linear-gradient(90deg,#ffe066,#ffb700);color:#222;padding:10px 18px;border-radius:8px;font-size:1rem;font-weight:500;box-shadow:0 2px 8px rgba(255,224,102,0.10);">Livraison digitale rapide</div>
        <div style="background:linear-gradient(90deg,#ffe066,#ffb700);color:#222;padding:10px 18px;border-radius:8px;font-size:1rem;font-weight:500;box-shadow:0 2px 8px rgba(255,224,102,0.10);">Paiement sécurisé &amp; données protégées</div>
    </section>
    <section style="background:#eaf4ff;border-radius:18px;padding:2em 1.5em;box-shadow:0 4px 24px rgba(42,122,226,0.08);margin:2em auto 2em auto;max-width:900px;">
        <h2 style="color:#2a7ae2;font-size:1.2em;font-weight:700;margin-bottom:12px;">Description détaillée</h2>
        <p style="color:#222;font-size:1.05rem;line-height:1.6;">
            Ce pack SEO Premium comprend une stratégie sur-mesure, des optimisations techniques avancées, du netlinking premium et un accompagnement expert. Idéal pour les entreprises ambitieuses qui veulent dominer leur secteur sur Google.
        </p>
    </section>
    <section style="background:#fffbe6;border-radius:18px;padding:2em 1.5em;box-shadow:0 4px 24px rgba(255,224,102,0.08);margin:2em auto 2em auto;max-width:900px;border:2px dashed #ffe066;">
        <h3 style="color:#e22a2a;font-size:1.1em;font-weight:700;margin-bottom:12px;">Non inclus dans ce pack</h3>
        <ul style="color:#e22a2a;font-size:1.05rem;line-height:1.6;list-style:disc inside;margin:0 0 0 18px;">
            <li>Campagnes publicitaires Google Ads</li>
            <li>Création de contenus premium</li>
            <li>Suivi SEO mensuel hors pack</li>
        </ul>
    </section>
</main>
<footer>
    <?php include '../../footer.php'; ?>
</footer>
</body>
</html>