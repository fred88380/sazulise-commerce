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
$longDescription = trim((string) ($product['long_description'] ?? ''));
$highlights = array_values(array_filter((array) ($product['highlights'] ?? []), static fn ($item): bool => trim((string) $item) !== ''));
$included = array_values(array_filter((array) ($product['included'] ?? []), static fn ($item): bool => trim((string) $item) !== ''));
$notIncluded = array_values(array_filter((array) ($product['not_included'] ?? []), static fn ($item): bool => trim((string) $item) !== ''));
$unlimitedStock = (bool) ($product['unlimited_stock'] ?? false);
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

        <?php if (!empty($included)): ?>
            <div class="product-detail-block product-detail-block-included">
                <h3>Inclus dans l'offre</h3>
                <ul>
                    <?php foreach ($included as $item): ?>
                        <li><?= htmlspecialchars((string) $item, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($longDescription !== ''): ?>
            <p class="product-long-description"><?= htmlspecialchars($longDescription, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if (!empty($highlights)): ?>
            <div class="product-detail-block">
                <h3>Points cles</h3>
                <ul>
                    <?php foreach ($highlights as $item): ?>
                        <li><?= htmlspecialchars((string) $item, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($notIncluded)): ?>
            <div class="product-detail-block product-detail-block-warning">
                <h3>Non inclus</h3>
                <ul>
                    <?php foreach ($notIncluded as $item): ?>
                        <li><?= htmlspecialchars((string) $item, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if ($unlimitedStock): ?>
            <p>Stock: Illimite</p>
        <?php else: ?>
            <p>Stock live: <?= (int) $product['stock'] ?> unites</p>
        <?php endif; ?>
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
