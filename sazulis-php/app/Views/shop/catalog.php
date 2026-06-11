<?php
$products = isset($products) && is_array($products) ? $products : [];
$basePath = isset($basePath) ? (string) $basePath : '';
$badgeOptions = [];
foreach ($products as $product) {
    foreach ((array) ($product['badges'] ?? []) as $badge) {
        $badge = trim((string) $badge);
        if ($badge !== '') {
            $badgeOptions[$badge] = true;
        }
    }
}
ksort($badgeOptions, SORT_NATURAL | SORT_FLAG_CASE);
?>
<section class="catalog-head">
    <h1>Catalogue Sazulis</h1>
    <p>Selection curatee. Pieces premium. Delivery intelligente.</p>
</section>

<section class="catalog-controls" aria-label="Filtres du catalogue">
    <label>
        Categorie
        <select id="catalog-badge-filter">
            <option value="">Toutes</option>
            <?php foreach (array_keys($badgeOptions) as $badge): ?>
                <option value="<?= htmlspecialchars(mb_strtolower($badge), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($badge, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>
        Trier par
        <select id="catalog-sort">
            <option value="default">Par defaut</option>
            <option value="price-asc">Prix croissant</option>
            <option value="price-desc">Prix decroissant</option>
            <option value="name-asc">Nom A-Z</option>
        </select>
    </label>
</section>

<section class="grid products-grid">
    <?php foreach ($products as $product): ?>
        <?php
        $image = (string) ($product['image'] ?? '');
        $imageUrl = str_starts_with($image, '/assets/') ? ($basePath . '/public' . $image) : $image;
        $badges = (array) ($product['badges'] ?? []);
        $badgesLower = array_map(static fn (string $badge): string => mb_strtolower($badge), $badges);
        ?>
        <article
            class="product-card reveal"
            data-name="<?= htmlspecialchars(mb_strtolower((string) ($product['name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
            data-price="<?= (float) ($product['price'] ?? 0) ?>"
            data-badges="<?= htmlspecialchars(implode('|', $badgesLower), ENT_QUOTES, 'UTF-8') ?>"
        >
            <div class="badges">
                <?php foreach ($badges as $badge): ?>
                    <span><?= htmlspecialchars($badge, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endforeach; ?>
            </div>
            <img src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="product-meta">
                <h3><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p><?= htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8') ?></p>
                <div class="product-row">
                    <strong><?= number_format((float) $product['price'], 2, ',', ' ') ?> EUR</strong>
                    <button
                        class="btn btn-primary add-to-cart"
                        data-id="<?= (int) $product['id'] ?>"
                        data-name="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>"
                        data-price="<?= (float) $product['price'] ?>"
                    >Ajouter</button>
                </div>
                <a class="link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/shop/<?= urlencode($product['slug']) ?>">Voir details</a>
            </div>
        </article>
    <?php endforeach; ?>
</section>
