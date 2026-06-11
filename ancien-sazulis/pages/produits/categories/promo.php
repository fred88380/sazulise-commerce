
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../../../navbar.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Promotions - Packs Starter</title>
    <link rel="stylesheet" href="../../../assets/css/style.css">
<?php require_once __DIR__ . '/../../../protect.php'; ?>
<body>
<main>
    <h1 style="text-align:center;margin:1em 0;">
        Promotions : Packs Starter
    </h1>
    <div style="max-width:600px;margin:0 auto 2em auto;font-size:1.08em;color:#555;text-align:center;background:#fffbe6;border-radius:10px;padding:1.2em 1em 1em 1em;box-shadow:0 2px 12px #ffe06622;">
        Profitez de nos offres spéciales et packs à prix réduit pour lancer votre projet web au meilleur tarif. Idéal pour démarrer rapidement avec un site professionnel tout compris.
    </div>
    <div class="product-grid">
        <?php
        // Tableau des prix fixes pour éviter les erreurs d'inclusion
        $prix_promo = [
            'html' => 1316.98,
            'nextjs' => 2899.10
        ];
        ?>
        <div class="product-card">
            <img src="../../../assets/img/promo.png" alt="Pack Starter Vitrine HTML">
            <div class="product-title">Site vitrine pack-starter (HTML)</div>
            <div style="font-size:0.98em;color:#555;margin-bottom:0.5em;">
                Un site vitrine professionnel, prêt à l’emploi, idéal pour lancer votre activité rapidement à prix réduit.
            </div>
            <div style="font-size:1.05em;color:#d2691e;font-weight:700;margin-bottom:0.3em;">
                <?php echo number_format($prix_promo['html'], 2, ',', ' '); ?>&nbsp;€ TTC
            </div>
            <a href="../site-vitrine-pack-starter-html.php"><button class="product-btn-promo">Voir la fiche</button></a>
        </div>
        <div class="product-card">
            <img src="../../../assets/img/promo.png" alt="Pack Starter Vitrine Next.js">
            <div class="product-title">Site vitrine pack-starter (Next.js)</div>
            <div style="font-size:0.98em;color:#555;margin-bottom:0.5em;">
                La puissance de Next.js pour un site vitrine moderne, rapide et optimisé, à un tarif promotionnel exceptionnel.
            </div>
            <div style="font-size:1.05em;color:#d2691e;font-weight:700;margin-bottom:0.3em;">
                <?php echo number_format($prix_promo['nextjs'], 2, ',', ' '); ?>&nbsp;€ TTC
            </div>
            <a href="../site-vitrine-pack-starter-nextjs.php"><button class="product-btn-promo">Voir la fiche</button></a>
        </div>
    </div>
</main>
<?php include '../../../footer.php'; ?>
</body>
</html>
