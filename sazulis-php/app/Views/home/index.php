<?php
$products = isset($products) && is_array($products) ? $products : [];
$basePath = isset($basePath) ? (string) $basePath : '';
$showcase = [
    ['title' => 'Boucherie', 'img' => 'https://sazulis.fr/assets/img/crea/boucherie.png', 'url' => 'https://creations.sazulis.fr/boucherie/index.html'],
    ['title' => 'DJ', 'img' => 'https://sazulis.fr/assets/img/crea/dj.png', 'url' => 'https://creations.sazulis.fr/dj/index.html'],
    ['title' => 'Educateur canin', 'img' => 'https://sazulis.fr/assets/img/crea/educateur-canin.png', 'url' => 'https://creations.sazulis.fr/ethozen/index.html'],
    ['title' => 'Foodtruck', 'img' => 'https://sazulis.fr/assets/img/crea/foodtruck.png', 'url' => 'https://creations.sazulis.fr/foodtruck/index.html'],
    ['title' => 'Garage', 'img' => 'https://sazulis.fr/assets/img/crea/garage.png', 'url' => 'https://creations.sazulis.fr/automobile/index.html'],
    ['title' => 'Web application', 'img' => 'https://sazulis.fr/assets/img/crea/web-application.png', 'url' => 'https://creations.sazulis.fr/web-application/index.html'],
    ['title' => 'WordPress', 'img' => 'https://sazulis.fr/assets/img/crea/wordpress.png', 'url' => 'https://creations.sazulis.fr/wordpress/index.html'],
    ['title' => 'Yoga', 'img' => 'https://sazulis.fr/assets/img/crea/yoga.png', 'url' => 'https://creations.sazulis.fr/yoga/index.html'],
];
?>
<section class="hero">
    <div class="hero-copy">
        <p class="tag">SAZULIS WEB STUDIO</p>
        <h1>Developpeur web dans les Vosges</h1>
        <p>Creation de sites web professionnels, e-commerce et applications sur-mesure. Accompagnement humain, local et reactif. Base a Arches, disponible partout.</p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/shop">Explorer le shop</a>
            <a class="btn btn-ghost" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin">Voir le backoffice</a>
        </div>
    </div>
    <div class="hero-panel">
        <div class="kpi">
            <strong>+38%</strong>
            <span>Conversion cible</span>
        </div>
        <div class="kpi">
            <strong>90s</strong>
            <span>Checkout complet</span>
        </div>
        <div class="kpi">
            <strong>24/7</strong>
            <span>Automations actives</span>
        </div>
    </div>
</section>

<section class="services-strip">
    <h2>Developpeur web dans les Vosges</h2>
    <ul>
        <li>Creation de sites vitrines</li>
        <li>E-commerce sur-mesure</li>
        <li>Applications web</li>
        <li>Refonte et optimisation</li>
        <li>SEO et performance</li>
        <li>Maintenance et securite</li>
    </ul>
</section>

<section class="portfolio-block">
    <div class="section-head">
        <h2>Maquettes et sites en ligne</h2>
        <div class="section-head-actions">
            <span class="tag">Portfolio live</span>
            <a class="btn btn-ghost" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/creations">Voir les creations</a>
        </div>
    </div>
    <div class="portfolio-slider">
        <button type="button" id="portfolio-prev" class="slider-nav" aria-label="Precedent">&#8592;</button>
        <div class="portfolio-track" id="portfolio-track">
            <?php foreach ($showcase as $case): ?>
                <a class="portfolio-item" href="<?= htmlspecialchars($case['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                    <img src="<?= htmlspecialchars($case['img'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($case['title'], ENT_QUOTES, 'UTF-8') ?>">
                    <h3><?= htmlspecialchars($case['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                </a>
            <?php endforeach; ?>
        </div>
        <button type="button" id="portfolio-next" class="slider-nav" aria-label="Suivant">&#8594;</button>
    </div>
</section>

<section class="trust-grid">
    <article>
        <h3>Quelques chiffres cles</h3>
        <div class="mini-stats">
            <div><strong>21</strong><span>Projets realises</span></div>
            <div><strong>6</strong><span>Clients accompagnes</span></div>
            <div><strong>2</strong><span>Annees d'experience</span></div>
            <div><strong>100%</strong><span>Projets a temps</span></div>
        </div>
    </article>
    <article>
        <h3>Ils nous font confiance</h3>
        <div class="quotes">
            <blockquote>"Super accompagnement, site livre rapidement et conforme." <cite>Julie, entrepreneure</cite></blockquote>
            <blockquote>"Design moderne, conseils pertinents et excellent suivi." <cite>Marc, artisan</cite></blockquote>
            <blockquote>"Equipe a l'ecoute, e-commerce performant." <cite>Sophie, commercante</cite></blockquote>
        </div>
    </article>
</section>

<section class="compare-block">
    <div>
        <h2>Comparatif Sazulis : pourquoi nous choisir ?</h2>
        <p>Accompagnement humain, stack moderne, securite, performance et SEO operationnel des la mise en ligne.</p>
        <ul>
            <li>Expertise locale a Epinal et Vosges</li>
            <li>Sites sur-mesure: vitrine, e-commerce, application</li>
            <li>SEO optimise pour Google</li>
            <li>Maintenance et support reactif</li>
        </ul>
    </div>
    <img src="https://sazulis.fr/assets/img/rapport.png" alt="Comparatif Sazulis">
</section>

<section class="featured">
    <div class="section-head">
        <h2>Drops en vedette</h2>
        <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/shop">Tout voir</a>
    </div>
    <div class="grid products-grid">
        <?php foreach ($products as $product): ?>
            <?php
            $image = (string) ($product['image'] ?? '');
            $imageUrl = str_starts_with($image, '/assets/') ? ($basePath . '/public' . $image) : $image;
            ?>
            <article class="product-card reveal">
                <img src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="product-meta">
                    <h3><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p><?= htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="product-row">
                        <strong><?= number_format((float) $product['price'], 2, ',', ' ') ?> EUR</strong>
                        <a class="link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/shop/<?= urlencode($product['slug']) ?>">Voir</a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="expertise-block">
    <img src="https://sazulis.fr/assets/img/boutique.png" alt="Boutique Sazulis">
    <div>
        <h2>Votre projet, notre expertise</h2>
        <p>Developpeur web freelance a Arches, disponible partout en France, nous livrons des plateformes robustes avec PHP, JavaScript, WordPress et architectures modernes.</p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/shop">Voir la boutique</a>
            <a class="btn btn-ghost" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/checkout">Demarrer un projet</a>
        </div>
    </div>
</section>
