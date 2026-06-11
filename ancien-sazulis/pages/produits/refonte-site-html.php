<?php
require_once __DIR__ . '/../../protect.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? $_COOKIE['lang'] ?? 'fr';
if (!in_array($lang, ['fr', 'en', 'es', 'it', 'de'])) $lang = 'fr';
$langs = include __DIR__ . '/../../lang/' . $lang . '.php';
require_once __DIR__ . '/../../backend/db.php';

$product = [
    'id' => 15,
    'name' => $langs['refonte_html_title'] ?? 'Refonte site HTML',
    'price' => 250.00,
    'img' => '../../assets/img/refonte-site-internet.jpg',
    'ref' => $langs['refonte_html_ref'] ?? 'refonte-site-html',
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
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['name']) ?> - Sazulis</title>
    <meta name="description" content="<?= htmlspecialchars($langs['refonte_html_meta_desc'] ?? 'Modernisez votre site vitrine ou statique. Refonte HTML complète, optimisation responsive mobile, conformité SEO et vitesse d’affichage accrue par Sazulis.') ?>">
    <meta name="keywords" content="<?= htmlspecialchars($langs['refonte_html_meta_keywords'] ?? 'refonte site html, moderniser site web, developpement html5 css3, site internet responsive, optimisation seo, sazulis') ?>">
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
                    OPTIMISATION &amp; MODERNISATION
                </div>
            </div>
            <div style="margin:1.5em auto 1em auto; display:flex; justify-content:center; align-items:center;">
                <img src="<?= $product['img'] ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="max-width:220px;border-radius:16px;box-shadow:0 2px 8px #0001;background:#fff;">
            </div>
            <div class="hero-clean-desc" style="background: transparent; max-width:600px; margin:auto;">
                <?= $langs['refonte_html_why'] ?? 'Modernisez votre site HTML pour une compatibilité optimale, rapidité d’affichage et sécurité renforcée.' ?>
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
            <li><?= $langs['refonte_html_inclus1'] ?? 'Refonte HTML sur-mesure' ?></li>
            <li><?= $langs['refonte_html_inclus2'] ?? 'Optimisation mobile et responsive' ?></li>
            <li><?= $langs['refonte_html_inclus3'] ?? 'Accessibilité améliorée' ?></li>
            <li><?= $langs['refonte_html_inclus4'] ?? 'Optimisation SEO de base' ?></li>
            <li><?= $langs['refonte_html_inclus5'] ?? 'Sécurité renforcée' ?></li>
            <li><?= $langs['refonte_html_inclus6'] ?? 'Support technique 6 mois' ?></li>
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
                    <td style="padding:8px;border:1px solid #ffe066;">Refonte HTML complète</td>
                    <td style="padding:8px;border:1px solid #ffe066;">1 site refondu</td>
                    <td style="padding:8px;border:1px solid #ffe066;text-align:right;">250,00&nbsp;€</td>
                    <td style="padding:8px;border:1px solid #ffe066;text-align:right;">350,00&nbsp;€</td>
                </tr>
                <tr>
                    <td style="padding:8px;border:1px solid #ffe066;">Optimisation mobile</td>
                    <td style="padding:8px;border:1px solid #ffe066;">Incluse</td>
                    <td style="padding:8px;border:1px solid #ffe066;text-align:right;">0,00&nbsp;€</td>
                    <td style="padding:8px;border:1px solid #ffe066;text-align:right;">50,00&nbsp;€</td>
                </tr>
                <tr style="background:#fffbe6;">
                    <td style="padding:8px;border:1px solid #ffe066;">Support technique</td>
                    <td style="padding:8px;border:1px solid #ffe066;">6 mois inclus</td>
                    <td style="padding:8px;border:1px solid #ffe066;text-align:right;">0,00&nbsp;€</td>
                    <td style="padding:8px;border:1px solid #ffe066;text-align:right;">60,00&nbsp;€</td>
                </tr>
            </tbody>
            <tfoot>
                <tr style="background:#ffe066;font-weight:bold;color:#222;">
                    <td colspan="2" style="padding:8px;border:1px solid #ffe066;text-align:right;">Total inclus</td>
                    <td style="padding:8px;border:1px solid #ffe066;text-align:right;">250,00&nbsp;€</td>
                    <td style="padding:8px;border:1px solid #ffe066;text-align:right;">460,00&nbsp;€</td>
                </tr>
            </tfoot>
        </table>
        <div style="background:#fffaf0;border:1px solid #ffe066;border-radius:8px;padding:14px 18px;margin-top:14px;color:#2a7ae2;font-size:1em;text-align:left;">
            Cette offre inclut la refonte HTML complète, l’optimisation mobile, l’accessibilité et le support technique. Le tarif “sans offre” correspond au prix standard si chaque service était acheté séparément. Packs conçus pour modernité, performance et tranquillité d’esprit.
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
            <?= $langs['refonte_html_desc'] ?? 'Refonte complète HTML, optimisation mobile, accessibilité, sécurité et support technique. Idéal pour donner une nouvelle vie à votre site web et garantir une expérience utilisateur optimale.' ?>
        </p>
    </section>
    <section style="background:#fffbe6;border-radius:18px;padding:2em 1.5em;box-shadow:0 4px 24px rgba(255,224,102,0.08);margin:2em auto 2em auto;max-width:900px;border:2px dashed #ffe066;">
        <h3 style="color:#e22a2a;font-size:1.1em;font-weight:700;margin-bottom:12px;">Non inclus dans ce pack</h3>
        <ul style="color:#e22a2a;font-size:1.05rem;line-height:1.6;list-style:disc inside;margin:0 0 0 18px;">
            <li><?= $langs['refonte_html_non_inclus1'] ?? 'Hébergement du site' ?></li>
            <li><?= $langs['refonte_html_non_inclus2'] ?? 'Création de contenu rédactionnel' ?></li>
        </ul>
    </section>
</main>
<footer>
    <?php include '../../footer.php'; ?>
</footer>
</body>
</html>