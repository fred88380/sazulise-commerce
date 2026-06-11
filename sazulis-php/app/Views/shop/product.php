<?php
$basePath = isset($basePath) ? (string) $basePath : '';
$product = isset($product) && is_array($product) ? $product : [
    'id' => 0,
    'name' => '',
    'description' => '',
    'price' => 0,
    'stock' => 0,
    'image' => '',
];
$image = (string) ($product['image'] ?? '');
$imageUrl = str_starts_with($image, '/assets/') ? ($basePath . '/public' . $image) : $image;
?>
<?php if ($notFound ?? false): ?>
<section class="empty-state">
    <h1>Produit introuvable</h1>
    <a class="btn btn-primary" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/shop">Retour au shop</a>
</section>
<?php else: ?>
<section class="product-page">
    <img src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
    <div>
        <p class="tag">DROP SAZULIS</p>
        <h1><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8') ?></p>
        <p class="price-xl"><?= number_format((float) $product['price'], 2, ',', ' ') ?> EUR</p>
        <p>Stock live: <?= (int) $product['stock'] ?> unites</p>
        <div class="hero-actions">
            <button
                class="btn btn-primary add-to-cart"
                data-id="<?= (int) $product['id'] ?>"
                data-name="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>"
                data-price="<?= (float) $product['price'] ?>"
            >Ajouter au panier</button>
            <a class="btn btn-ghost" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/checkout">Commander maintenant</a>
        </div>
    </div>
</section>
<?php endif; ?>
