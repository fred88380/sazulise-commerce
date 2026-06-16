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

<section class="catalog-info-grid">
    <div class="catalog-info-block">
        <h2>HTML ou Next.js&nbsp;: quelle difference&nbsp;?</h2>
        <div class="catalog-info-row">
            <div class="catalog-info-card">
                <span class="catalog-info-badge">HTML</span>
                <h3>Technologie traditionnelle</h3>
                <p>Un site HTML est construit avec les langages de base du web&nbsp;: HTML, CSS et JavaScript. Les pages sont generees cote serveur ou livrees en fichiers statiques. C'est une solution eprouvee, simple a maintenir, compatible partout et tres rapide a charger sur tout type de connexion.</p>
                <ul>
                    <li>Ideal pour les sites vitrines simples ou les boutiques standard</li>
                    <li>Hebergement facile et peu couteux</li>
                    <li>Temps de chargement rapide</li>
                    <li>Maintenance accessible</li>
                </ul>
            </div>
            <div class="catalog-info-card">
                <span class="catalog-info-badge catalog-info-badge-next">Next.js</span>
                <h3>Technologie moderne React</h3>
                <p>Next.js est un framework JavaScript haut de gamme base sur React. Il permet de creer des applications web ultra-performantes avec rendu hybride (serveur + navigateur), ideal pour les sites complexes, les boutiques a fort trafic et les applications metier. Les pages se chargent en une fraction de seconde grace au prefetching automatique.</p>
                <ul>
                    <li>Performances maximales et SEO natif optimise</li>
                    <li>Ideal pour les e-commerces, applications et projets ambitieux</li>
                    <li>Interface tres reactive, experience utilisateur premium</li>
                    <li>Architecture evolutive pour grandir avec votre activite</li>
                </ul>
            </div>
        </div>
        <p class="catalog-info-note">En resume&nbsp;: HTML convient parfaitement pour demarrer ou pour un projet sobre et efficace. Next.js est le choix des projets qui veulent viser haut des le depart. Dans les deux cas, Sazulis vous livre un site professionnel, pret a l'emploi.</p>
    </div>

    <div class="catalog-info-block catalog-info-block-ownership">
        <span class="catalog-info-badge catalog-info-badge-owner">Votre propriete</span>
        <h2>Le site vous appartient entierement</h2>
        <p>Pour toutes les prestations de creation de <strong>site vitrine</strong>, <strong>application web</strong>, <strong>e-commerce</strong> et <strong>site WordPress CMS</strong>, le site livre est votre propriete exclusive des le paiement integral. Vous recevez les codes source, les acces hebergement et le nom de domaine. Sazulis n'a aucun droit de regard ni de controle sur votre site une fois livre.</p>
        <ul class="catalog-info-ownership-list">
            <li>Code source livre integralement</li>
            <li>Acces hebergement et domaine transferes a votre nom</li>
            <li>Aucun abonnement oblige, aucune dependance envers Sazulis</li>
            <li>Liberte totale de faire evoluer votre site comme vous le souhaitez</li>
        </ul>
    </div>
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
