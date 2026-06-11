<?php
require_once __DIR__ . '/../protect.php';
?>
<!DOCTYPE html>
<html lang="fr">
<?php include __DIR__ . '/../head.php'; ?>
    <style>
        body {
            background: url('../assets/img/boutique.png') center/cover no-repeat fixed;
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        main.products-main {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            justify-content: flex-start;
            background: transparent;
            margin-bottom: 2em;
        }
        .products-hero {
            padding: 4em 0 2em 0;
            text-align: center;
        }
        .products-hero h1 {
            font-size: 2.5em;
            font-weight: 900;
            color: #1a2347;
            margin-bottom: 0.3em;
        }
        .products-hero-desc {
            font-size: 1.2em;
            color: #333;
            margin-bottom: 1.2em;
        }
        .products-cta {
            display: inline-block;
            font-size: 1.15em;
            padding: 0.8em 2em;
            background: #1a2347;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 1.2em;
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px,1fr));
            gap: 2em;
            max-width: 1100px;
            margin: 2em auto 2em auto;
        }
        .product-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 12px #0001;
            padding: 1.5em 1em 1.2em 1em;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: transform 0.13s;
        }
        .product-card:hover {
            transform: scale(1.04);
        }
        .product-card img {
            width: 100%;
            max-width: 140px;
            border-radius: 8px;
            margin-bottom: 1em;
            object-fit: cover;
        }
        .product-title {
            font-weight: 700;
            color: #1a2347;
            margin-bottom: 0.5em;
            font-size: 1.1em;
        }
        .product-btn {
            font-size: 1em;
            padding: 0.5em 1.2em;
            background: #c8902e;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 0.7em;
            font-weight: 600;
            transition: background 0.15s;
        }
        .product-btn:hover {
            background: #1a2347;
        }
        @media (max-width: 700px) {
            .product-grid { grid-template-columns: 1fr; gap: 1.2em; }
        }
    </style>
<body>
    <?php include '../navbar.php'; ?>
    <main class="products-main">
        <section class="products-hero">
            <h1>Boutique Sazulis&nbsp;: nos offres web</h1>
            <div class="products-hero-desc">
                Découvrez toutes nos solutions pour la création de site vitrine, e-commerce, application web, refonte, SEO, maintenance et hébergement.<br>
                <strong>Des packs adaptés à chaque besoin, des prix transparents, un accompagnement humain et local.</strong>
            </div>
            <div style="max-width:700px;margin:1.5em auto 0 auto;padding:1.2em 1.5em;background:#f7f8fa;border-radius:12px;border-left:5px solid #c8902e;text-align:left;font-size:1.08em;box-shadow:0 2px 8px #0001;">
                <strong>HTML</strong> : C’est la façon la plus simple de créer un site internet. Tout fonctionne partout, c’est fiable, rapide et s’adapte à tous les écrans.<br><br>
                <strong>Next.js</strong> : C’est une nouvelle façon de créer des sites : encore plus rapides et modernes, avec des possibilités avancées.
            </div>
            <a href="#categories-grid" class="products-cta">Voir les catégories</a>
        </section>

        <section style="max-width:1100px;margin:auto;">
            <h2 style="font-size:2em;font-weight:700;text-align:center;margin-bottom:1em;color:#1a2347;">Nos catégories de produits</h2>
            <div class="product-grid" id="categories-grid">
                <div class="product-card" data-category="promotions">
                    <img src="../assets/img/promo.png" alt="Promotions web Sazulis">
                    <div class="product-title">Promotions</div>
                    <div style="font-size:0.98em;color:#555;margin-bottom:0.5em;">Profitez de nos offres spéciales et packs à prix réduit pour lancer votre projet web au meilleur tarif.</div>
                    <button class="product-btn" type="button">Voir la sélection</button>
                </div>
                <div class="product-card" data-category="site-vitrine">
                    <img src="../assets/img/vitrine-site.png" alt="Site vitrine professionnel">
                    <div class="product-title">Site vitrine</div>
                    <div style="font-size:0.98em;color:#555;margin-bottom:0.5em;">Idéal pour présenter votre activité, votre entreprise ou votre association avec un site moderne et efficace.</div>
                    <button class="product-btn" type="button">Voir la sélection</button>
                </div>
                <div class="product-card" data-category="e-commerce">
                    <img src="../assets/img/E-commerce.png" alt="E-commerce sur-mesure">
                    <div class="product-title">E-commerce</div>
                    <div style="font-size:0.98em;color:#555;margin-bottom:0.5em;">Vendez vos produits ou services en ligne avec une boutique sécurisée, performante et adaptée à vos besoins.</div>
                    <button class="product-btn" type="button">Voir la sélection</button>
                </div>
                <div class="product-card" data-category="application-web">
                    <img src="../assets/img/application.png" alt="Application web personnalisée">
                    <div class="product-title">Application web</div>
                    <div style="font-size:0.98em;color:#555;margin-bottom:0.5em;">Solutions sur-mesure pour automatiser, gérer ou digitaliser votre activité avec des outils web performants.</div>
                    <button class="product-btn" type="button">Voir la sélection</button>
                </div>
                <div class="product-card" data-category="wordpress">
                    <img src="../assets/img/wordpress.jpg" alt="WordPress CMS">
                    <div class="product-title">WordPress</div>
                    <div style="font-size:0.98em;color:#555;margin-bottom:0.5em;">Sites administrables, blogs ou vitrines propulsés par WordPress, le CMS le plus utilisé au monde.</div>
                    <button class="product-btn" type="button">Voir la sélection</button>
                </div>
                <div class="product-card" data-category="refonte">
                    <img src="../assets/img/refonte-site-internet.jpg" alt="Refonte site internet">
                    <div class="product-title">Refonte</div>
                    <div style="font-size:0.98em;color:#555;margin-bottom:0.5em;">Modernisez votre site existant pour booster son impact, son design et son référencement.</div>
                    <button class="product-btn" type="button">Voir la sélection</button>
                </div>
                <div class="product-card" data-category="hebergement">
                    <img src="../assets/img/hebergement.jpg" alt="Hébergement web France">
                    <div class="product-title">Hébergement</div>
                    <div style="font-size:0.98em;color:#555;margin-bottom:0.5em;">Des solutions d’hébergement fiables, sécurisées et adaptées à tous vos projets web, en France.</div>
                    <button class="product-btn" type="button">Voir la sélection</button>
                </div>
                <div class="product-card" data-category="seo">
                    <img src="../assets/img/seo.png" alt="SEO référencement naturel">
                    <div class="product-title">SEO</div>
                    <div style="font-size:0.98em;color:#555;margin-bottom:0.5em;">Optimisez votre visibilité sur Google grâce à nos offres de référencement naturel et d’audit SEO.</div>
                    <button class="product-btn" type="button">Voir la sélection</button>
                </div>
                <div class="product-card" data-category="maintenance">
                    <img src="../assets/img/maintenance.jpg" alt="Maintenance site web">
                    <div class="product-title">Maintenance</div>
                    <div style="font-size:0.98em;color:#555;margin-bottom:0.5em;">Gardez un site sécurisé, à jour et performant grâce à nos packs de maintenance adaptés à tous les besoins.</div>
                    <button class="product-btn" type="button">Voir la sélection</button>
                </div>
                <div class="product-card" data-category="urgent">
                    <img src="../assets/img/urgent.png" alt="Maintenance urgente">
                    <div class="product-title">Urgent</div>
                    <div style="font-size:0.98em;color:#555;margin-bottom:0.5em;">Intervention rapide en cas de problème critique, piratage ou besoin de dépannage urgent sur votre site.</div>
                    <button class="product-btn" type="button">Voir la sélection</button>
                </div>
            </div>
            <div id="category-products-container" style="margin-top:2em;"></div>
        </section>

        <section style="max-width:1100px;margin:2em auto 2em auto;">
            <h2 style="font-size:1.5em;font-weight:700;margin-bottom:1em;color:#1a2347;text-align:center;">Pourquoi choisir Sazulis&nbsp;?</h2>
            <ul style="max-width:700px;margin:auto  auto 1.5em auto;font-size:1.1em;line-height:1.7;">
                <li><strong>Expertise locale</strong> : développeur web basé à Épinal, Vosges</li>
                <li><strong>Sites internet sur-mesure</strong> : vitrine, e-commerce, application web</li>
                <li><strong>Référencement naturel (SEO)</strong> optimisé pour Google</li>
                <li><strong>Maintenance et sécurité</strong> incluses</li>
                <li><strong>Hébergement sécurisé</strong> et performant</li>
                <li><strong>Accompagnement humain</strong> et conseils personnalisés</li>
            </ul>
            <blockquote style="font-style:italic;color:#007bff;text-align:center;max-width:700px;margin:auto;">Vous ne choisissez pas seulement un site internet,<br>vous investissez dans un outil digital performant et fiable, pensé pour le <strong>référencement Google</strong> et la <strong>sécurité</strong>.</blockquote>
        </section>
    </main>
    <script>
    // Données produits par catégorie (identique à avant, mais tout en français)
    const produitsParCategorie = {
        'promotions': [
            {
                nom: 'Site vitrine pack-starter (HTML)',
                img: '../assets/img/promo.png',
                ttc: '1 316,98 € TTC',
                lien: '../pages/produits/site-vitrine-pack-starter-html.php',
                description: "Un site vitrine professionnel, prêt à l’emploi, idéal pour lancer votre activité rapidement à prix réduit."
            },
            {
                nom: 'Site vitrine pack-starter (Next.js)',
                img: '../assets/img/promo.png',
                ttc: '2 899,10 € TTC',
                lien: '../pages/produits/site-vitrine-pack-starter-nextjs.php',
                description: "La puissance de Next.js pour un site vitrine moderne, rapide et optimisé, à un tarif promotionnel exceptionnel."
            }
        ],
        'site-vitrine': [
            {
                nom: 'Site Vitrine HTML',
                img: '../assets/img/site-vitrine.png',
                ttc: '4 326,87 € TTC',
                lien: '../pages/produits/site-vitrine-html.php',
                description: "Un site vitrine moderne et sur-mesure pour présenter votre activité et attirer de nouveaux clients."
            },
            {
                nom: 'Site Vitrine Next.js',
                img: '../assets/img/site-vitrine.png',
                ttc: '5 399,10 € TTC',
                lien: '../pages/produits/site-vitrine-nextjs.php',
                description: "La technologie Next.js pour un site vitrine ultra-rapide, évolutif et optimisé SEO."
            }
        ],
        'e-commerce': [
            {
                nom: 'E-commerce HTML',
                img: '../assets/img/e-commerce2.png',
                ttc: '6 373,63 € TTC',
                lien: '../pages/produits/e-commerce-html.php',
                description: "Vendez en ligne avec une boutique HTML performante, sécurisée et facile à gérer."
            },
            {
                nom: 'E-commerce Next.js',
                img: '../assets/img/e-commerce2.png',
                ttc: '8 299,00 € TTC',
                lien: '../pages/produits/e-commerce-nextjs.php',
                description: "Une boutique Next.js pour une expérience d’achat fluide, rapide et évolutive."
            }
        ],
        'application-web': [
            {
                nom: 'Application Web HTML',
                img: '../assets/img/application-web.png',
                ttc: '5 015,13 € TTC',
                lien: '../pages/produits/application-web-html.php',
                description: "Automatisez et digitalisez votre activité avec une application web HTML sur-mesure."
            },
            {
                nom: 'Application Web Next.js',
                img: '../assets/img/application-web.png',
                ttc: '6 087,36 € TTC',
                lien: '../pages/produits/application-web-nextjs.php',
                description: "Des outils métiers puissants et évolutifs grâce à Next.js, adaptés à vos besoins."
            }
        ],
        'wordpress': [
            {
                nom: 'WordPress CMS HTML',
                img: '../assets/img/wordpress.jpg',
                ttc: '3 603,64 € TTC',
                lien: '../pages/produits/wordpress-cms-html.php',
                description: "Créez un site administrable, blog ou vitrine avec WordPress, simple et efficace."
            },
            {
                nom: 'WordPress CMS Next.js',
                img: '../assets/img/wordpress.jpg',
                ttc: '4 675,87 € TTC',
                lien: '../pages/produits/wordpress-cms-nextjs.php',
                description: "La puissance de Next.js alliée à WordPress pour un site rapide et flexible."
            }
        ],
        'refonte': [
            {
                nom: 'Refonte site HTML',
                img: '../assets/img/refonte-site-internet.jpg',
                ttc: '250,00 € TTC',
                lien: '../pages/produits/refonte-site-html.php',
                description: "Modernisez votre site existant pour booster son impact, son design et son référencement."
            },
            {
                nom: 'Refonte site Next.js',
                img: '../assets/img/refonte-site-internet.jpg',
                ttc: '600,00 € TTC',
                lien: '../pages/produits/refonte-site-nextjs.php',
                description: "Passez à Next.js pour une refonte performante, moderne et évolutive."
            }
        ],
        'hebergement': [
            {
                nom: 'Hébergement Basic HTML',
                img: '../assets/img/hebergement.jpg',
                ttc: '149,99 € TTC',
                lien: '../pages/produits/hebergement-basic-html.php',
                description: "Hébergement fiable et sécurisé pour votre site HTML, en France."
            },
            {
                nom: 'Hébergement Basic Next.js',
                img: '../assets/img/hebergement.jpg',
                ttc: '299,99 € TTC',
                lien: '../pages/produits/hebergement-basic-nextjs.php',
                description: "Hébergement optimisé pour vos sites Next.js, performance et tranquillité."
            },
            {
                nom: 'Hébergement Business HTML',
                img: '../assets/img/hebergement.jpg',
                ttc: '289,99 € TTC',
                lien: '../pages/produits/hebergement-business-html.php',
                description: "Pour les sites HTML à fort trafic ou besoins professionnels."
            },
            {
                nom: 'Hébergement Business Next.js',
                img: '../assets/img/hebergement.jpg',
                ttc: '579,98 € TTC',
                lien: '../pages/produits/hebergement-business-nextjs.php',
                description: "Hébergement Next.js business, sécurité et ressources accrues."
            },
            {
                nom: 'Hébergement Premium HTML',
                img: '../assets/img/hebergement.jpg',
                ttc: '579,99 € TTC',
                lien: '../pages/produits/hebergement-premium-html.php',
                description: "Le top pour votre site HTML, rapidité, sauvegardes et support prioritaire."
            },
            {
                nom: 'Hébergement Premium Next.js',
                img: '../assets/img/hebergement.jpg',
                ttc: '1 159,96 € TTC',
                lien: '../pages/produits/hebergement-premium-nextjs.php',
                description: "Hébergement premium Next.js, pour les projets les plus exigeants."
            }
        ],
        'seo': [
            {
                nom: 'SEO Basic',
                img: '../assets/img/seo.png',
                ttc: '299,99 € TTC',
                lien: '../pages/produits/seo-basic.php',
                description: "Boostez votre visibilité Google avec un pack SEO essentiel."
            },
            {
                nom: 'SEO Business',
                img: '../assets/img/seo.png',
                ttc: '459,98 € TTC',
                lien: '../pages/produits/seo-business.php',
                description: "Un accompagnement SEO complet pour faire décoller votre site."
            },
            {
                nom: 'SEO Premium',
                img: '../assets/img/seo.png',
                ttc: '599,99 € TTC',
                lien: '../pages/produits/seo-premium.php',
                description: "Le meilleur du SEO pour dominer votre marché sur Google."
            }
        ],
        'maintenance': [
            {
                nom: 'Maintenance Basic',
                img: '../assets/img/maintenance.jpg',
                ttc: '350,00 € TTC',
                lien: '../pages/produits/maintenance-basic.php',
                description: "Gardez votre site à jour et sécurisé avec l’essentiel de la maintenance."
            },
            {
                nom: 'Maintenance Business',
                img: '../assets/img/maintenance.jpg',
                ttc: '650,00 € TTC',
                lien: '../pages/produits/maintenance-business.php',
                description: "Un suivi technique renforcé pour les sites à fort enjeu."
            },
            {
                nom: 'Maintenance Premium',
                img: '../assets/img/maintenance.jpg',
                ttc: '950,00 € TTC',
                lien: '../pages/produits/maintenance-premium.php',
                description: "Support prioritaire, interventions rapides et sécurité maximale."
            },
            {
                nom: 'Maintenance Urgente',
                img: '../assets/img/urgent.png',
                ttc: '200,00 € TTC',
                lien: '../pages/produits/maintenance-urgente.php',
                description: "Dépannage express en cas de problème critique ou piratage."
            }
        ],
        'urgent': [
            {
                nom: 'Maintenance Urgente',
                img: '../assets/img/urgent.png',
                ttc: '200,00 € TTC',
                lien: '../pages/produits/maintenance-urgente.php',
                description: "Intervention rapide pour remettre votre site en ligne sans attendre."
            }
        ]
    };
    // Gestion du clic sur une catégorie
    document.querySelectorAll('#categories-grid .product-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const card = this.closest('.product-card');
            const cat = card.getAttribute('data-category');
            const produits = produitsParCategorie[cat] || [];
            let html = '';
            if (produits.length > 0) {
                html += `<h2 style="text-align:center;">Produits de la catégorie : <span style='color:#d4af37;'>${card.querySelector('.product-title').textContent}</span></h2>`;
                html += '<div class="product-grid">';
                produits.forEach(p => {
                    html += `<div class='product-card'>` +
                        `<img src='${p.img}' alt='${p.nom}'>` +
                        `<div class='product-title'>${p.nom}</div>` +
                        (p.description ? `<div style='font-size:0.98em;color:#555;margin-bottom:0.5em;'>${p.description}</div>` : '') +
                        `<div style='font-size:1.05em;color:#d2691e;font-weight:700;margin-bottom:0.3em;'>${p.ttc}</div>` +
                        `<button class='product-btn fiche-btn' data-lien='${p.lien}'>Voir la fiche</button>` +
                    `</div>`;
                });
                html += '</div>';
            } else {
                html = '<div style="text-align:center;color:#888;">Aucun produit dans cette catégorie pour le moment.</div>';
            }
            document.getElementById('category-products-container').innerHTML = html;
            window.scrollTo({ top: document.getElementById('category-products-container').offsetTop - 60, behavior: 'smooth' });
            // Ajout du handler pour redirection sur fiche produit (après rendu)
            document.querySelectorAll('.fiche-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    const lien = this.getAttribute('data-lien');
                    if (lien) {
                        window.location.href = lien;
                    }
                });
            });
        });
    });
    </script>
    <?php include '../footer.php'; ?>
</body>
</html>