<?php
// Inclure les headers de sécurité AVANT tout output ou session_start
require_once __DIR__ . '/security_headers.php';

session_start();

// Configuration des métadonnées SEO spécifiques à cette page
$page_canonical = "https://www.sazulis.fr/"; // Mets ta vraie URL de prod
$og_title = "Sazulis | Développeur Web Freelance à Épinal & Vosges";
$og_description = "Création de sites web, e-commerce, applications et SEO sur mesure à Épinal et dans les Vosges.";
$og_image = "https://www.sazulis.fr/assets/img/boutique.png"; // Optionnel : une image de partage
?>
<!DOCTYPE html>
<html lang="fr">
<?php include __DIR__ . '/head.php'; ?>
    <style>
        body {
            background: url('assets/img/unique.png') center/cover no-repeat fixed;
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        main {
            background: transparent !important;
            box-shadow: none !important;
            border-radius: 0;
            margin-bottom: 2em;
        }
        /* ...existing code... */
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
            font-size: 1.15em;
            padding: 0.8em 2em;
            background: #1a2347;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 1.2em;
        }
        .services-list {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 2em;
            max-width: 900px;
            margin: 2em auto 2em auto;
        }
        .service-item {
            min-width: 180px;
            font-size: 1.1em;
            color: #222;
        }
        .portfolio-section {
            padding: 3em 0 2em 0;
            text-align: center;
        }
        .portfolio-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px,1fr));
            gap: 2em;
            max-width: 1100px;
            margin: auto;
        }
        .portfolio-item img {
            width: 100%;
            max-width: 220px;
            border-radius: 8px;
        }
        .portfolio-item-title {
            font-weight: 700;
            color: #1a2347;
            margin-top: 0.7em;
        }
        .stats-section {
            background: #f8fafc;
            padding: 2.5em 0 1.5em 0;
            text-align: center;
        }
        .stats-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 2em;
            max-width: 900px;
            margin: auto;
        }
        .stat-block {
            flex: 1 1 200px;
            min-width: 200px;
            background: #fff;
            border-radius: 14px;
            padding: 2em 1em;
            text-align: center;
            box-shadow: 0 2px 12px #0001;
        }
        .stat-value {
            font-size: 2em;
            font-weight: 700;
            color: #1a2347;
        }
        .stat-label {
            color: #555;
        }
        .testimonials-section {
            background: #f8fafc;
            padding: 3em 0 2em 0;
        }
        .testimonials-list {
            display: flex;
            gap: 2em;
            flex-wrap: wrap;
            justify-content: center;
            max-width: 900px;
            margin: auto;
        }
        .testimonial {
            flex: 1 1 260px;
            min-width: 220px;
            background: #fff;
            border-radius: 14px;
            padding: 2em 1.2em;
            box-shadow: 0 2px 12px #0001;
            margin-bottom: 1em;
        }
        .testimonial-quote {
            font-size: 1.1em;
            color: #222;
            font-style: italic;
        }
        .testimonial-author {
            margin-top: 1em;
            font-weight: 700;
            color: #c8902e;
        }
        .cta-final {
            background: #fff;
            padding: 3em 0 2em 0;
            text-align: center;
        }
        .cta-final a {
            display: inline-block;
            font-size: 1.15em;
            padding: 0.8em 2em;
            background: #1a2347;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
        }
        @media (max-width: 700px) {
            .services-list, .stats-row, .testimonials-list { flex-direction: column; gap: 1.2em; }
        }
    </style>
    </head>
<body>
    <?php include 'navbar.php'; ?>
    <main>
        <!-- HERO SECTION: Clean, modern headline and CTA -->
        <section class="hero-clean" style="background: transparent;">
            <div style="background: transparent; box-shadow: none; border-radius: 0; padding: 2.5em 0 2em 0; text-align: center;">
                <h1 class="hero-clean-title" style="background: transparent;">Développeur web dans les Vosges</h1>
                <div class="hero-clean-desc" style="background: transparent;">Création de sites web professionnels, e-commerce et applications sur-mesure.<br>Accompagnement humain, local et réactif.<br><span style="color:#c8902e;font-weight:600;">Basé à Arches, disponible partout&nbsp;!</span></div>
                <!-- <a href="pages/products.php" class="hero-clean-btn">Voir la boutique</a> -->
            </div>
        </section>

        <!-- SERVICES: Modern, simple list -->
        <section style="background: none; padding: 2em 0 1em 0;">
            <ul class="hero-devweb-list" style="display:flex;flex-wrap:wrap;justify-content:center;gap:2em;list-style:none;padding:0;margin:0 auto 0 auto;max-width:900px;">
                <li>Création de sites vitrines</li>
                <li>E-commerce sur-mesure</li>
                <li>Applications web</li>
                <li>Refonte & optimisation</li>
                <li>SEO & performance</li>
                <li>Maintenance & sécurité</li>
            </ul>
        </section>

        <!-- PORTFOLIO SLIDER: Version unique, fond transparent -->
        <section id="slider-realisations" style="margin:2.5em auto 2em auto;max-width:1100px;padding:0 1em;background:transparent;">
            <h2 style="font-size:2em;font-weight:700;text-align:center;margin-bottom:1em;color:#1a2347;">Réalisations récentes</h2>
            <div style="display:flex;align-items:center;justify-content:center;gap:1.5em;background:transparent;width:100%;">
                <button style="background:none;border:none;font-size:2.5em;cursor:pointer;color:#23408e;transition:color 0.2s;" id="sliderPrev" onmouseover="this.style.color='#4a6edb'" onmouseout="this.style.color='#23408e'">&#8592;</button>
                <div id="sliderTrack" style="display:flex;gap:2em;align-items:center;overflow:hidden;max-width:700px;width:100%;justify-content:center;">
                    <img src="assets/img/crea/boucherie.png" alt="Boucherie" style="height:180px;border-radius:14px;background:transparent;flex-shrink:0;">
                    <img src="assets/img/crea/dj.png" alt="DJ" style="height:180px;border-radius:14px;background:transparent;flex-shrink:0;">
                    <img src="assets/img/crea/educateur-canin.png" alt="Educateur canin" style="height:180px;border-radius:14px;background:transparent;flex-shrink:0;">
                    <img src="assets/img/crea/encours.png" alt="En cours" style="height:180px;border-radius:14px;background:transparent;flex-shrink:0;">
                    <img src="assets/img/crea/epoxy.png" alt="Epoxy" style="height:180px;border-radius:14px;background:transparent;flex-shrink:0;">
                    <img src="assets/img/crea/foodtruck.png" alt="Foodtruck" style="height:180px;border-radius:14px;background:transparent;flex-shrink:0;">
                    <img src="assets/img/crea/garage.png" alt="Garage" style="height:180px;border-radius:14px;background:transparent;flex-shrink:0;">
                    <img src="assets/img/crea/masseur.png" alt="Masseur" style="height:180px;border-radius:14px;background:transparent;flex-shrink:0;">
                    <img src="assets/img/crea/nettoyage.png" alt="Nettoyage" style="height:180px;border-radius:14px;background:transparent;flex-shrink:0;">
                    <img src="assets/img/crea/piscine-naturelle.png" alt="Piscine naturelle" style="height:180px;border-radius:14px;background:transparent;flex-shrink:0;">
                    <img src="assets/img/crea/renov-stele.png" alt="Renov Stele" style="height:180px;border-radius:14px;background:transparent;flex-shrink:0;">
                    <img src="assets/img/crea/resto.png" alt="Resto" style="height:180px;border-radius:14px;background:transparent;flex-shrink:0;">
                    <img src="assets/img/crea/retraite.png" alt="Retraite" style="height:180px;border-radius:14px;background:transparent;flex-shrink:0;">
                    <img src="assets/img/crea/tatoueur.png" alt="Tatoueur" style="height:180px;border-radius:14px;background:transparent;flex-shrink:0;">
                    <img src="assets/img/crea/vylmora.png" alt="Vylmora" style="height:180px;border-radius:14px;background:transparent;flex-shrink:0;">
                    <img src="assets/img/crea/web-application.png" alt="Web application" style="height:180px;border-radius:14px;background:transparent;flex-shrink:0;">
                    <img src="assets/img/crea/weeding-planner.png" alt="Weeding planner" style="height:180px;border-radius:14px;background:transparent;flex-shrink:0;">
                    <img src="assets/img/crea/wordpress.png" alt="Wordpress" style="height:180px;border-radius:14px;background:transparent;flex-shrink:0;">
                    <img src="assets/img/crea/yoga.png" alt="Yoga" style="height:180px;border-radius:14px;background:transparent;flex-shrink:0;">
                </div>
                <button style="background:none;border:none;font-size:2.5em;cursor:pointer;color:#23408e;transition:color 0.2s;" id="sliderNext" onmouseover="this.style.color='#4a6edb'" onmouseout="this.style.color='#23408e'">&#8594;</button>
            </div>
        </section>

        <!-- STATS: Version unique, fond transparent -->
        <section style="background:transparent;padding:2em 0 1em 0;text-align:center;max-width:900px;margin:auto;">
            <h2 style="font-size:1.5em;font-weight:700;margin-bottom:1em;color:#1a2347;">Quelques chiffres clés</h2>
            <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:2.5em;background:transparent;">
                <div style="min-width:120px;margin-bottom:1em;background:transparent;">
                    <div style="font-size:2em;font-weight:700;color:#1a2347;background:transparent;">21</div>
                    <div style="color:#555;font-size:1em;background:transparent;">Projets réalisés</div>
                </div>
                <div style="min-width:120px;margin-bottom:1em;background:transparent;">
                    <div style="font-size:2em;font-weight:700;color:#1a2347;background:transparent;">6</div>
                    <div style="color:#555;font-size:1em;background:transparent;">Clients accompagnés</div>
                </div>
                <div style="min-width:120px;margin-bottom:1em;background:transparent;">
                    <div style="font-size:2em;font-weight:700;color:#1a2347;background:transparent;">2</div>
                    <div style="color:#555;font-size:1em;background:transparent;">Années d'expérience</div>
                </div>
                <div style="min-width:120px;margin-bottom:1em;background:transparent;">
                    <div style="font-size:2em;font-weight:700;color:#1a2347;background:transparent;">100%</div>
                    <div style="color:#555;font-size:1em;background:transparent;">Projets à temps</div>
                </div>
                <div style="min-width:120px;margin-bottom:1em;background:transparent;">
                    <div style="font-size:2em;font-weight:700;color:#1a2347;background:transparent;">SSL</div>
                    <div style="color:#555;font-size:1em;background:transparent;">Paiement sécurisé</div>
                </div>
            </div>
        </section>

        <!-- AVIS CLIENTS : Version unique, fond transparent -->
        <section style="background:transparent;padding:2em 0 1em 0;text-align:center;max-width:900px;margin:auto;">
            <h2 style="font-size:1.5em;font-weight:700;margin-bottom:1em;color:#1a2347;">Ils nous font confiance</h2>
            <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:2em;background:transparent;">
                <div style="min-width:180px;font-style:italic;color:#222;background:transparent;">"Super accompagnement, site livré rapidement et conforme à mes attentes. Je recommande !"<br><span style="font-weight:700;color:#c8902e;background:transparent;">— Julie, entrepreneure</span></div>
                <div style="min-width:180px;font-style:italic;color:#222;background:transparent;">"Design moderne, conseils pertinents et très bon suivi. Merci pour votre professionnalisme."<br><span style="font-weight:700;color:#c8902e;background:transparent;">— Marc, artisan</span></div>
                <div style="min-width:180px;font-style:italic;color:#222;background:transparent;">"Site e-commerce performant, équipe à l'écoute et disponible. Je recommande vivement !"<br><span style="font-weight:700;color:#c8902e;background:transparent;">— Sophie, commerçante</span></div>
            </div>
        </section>

        <!-- FINAL CTA: Version unique, fond transparent -->
        <section style="background:transparent;padding:2em 0 1em 0;text-align:center;max-width:900px;margin:auto;">
            <h2 style="font-size:1.5em;font-weight:700;margin-bottom:1em;color:#1a2347;">Prêt à booster votre présence en ligne&nbsp;?</h2>
            <!-- <a href="/pages/products.php" class="hero-devweb-btn">Voir la boutique</a> -->
        </section>
    <!-- Script unique pour le slider -->
    <script>
    // Slider navigation uniquement avec les flèches
    (function() {
        const sliderTrack = document.getElementById('sliderTrack');
        const prevBtn = document.getElementById('sliderPrev');
        const nextBtn = document.getElementById('sliderNext');
        const scrollStep = 220; // Largeur d'une image + gap
        if (sliderTrack && prevBtn && nextBtn) {
            prevBtn.addEventListener('click', () => {
                sliderTrack.scrollBy({ left: -scrollStep, behavior: 'smooth' });
            });
            nextBtn.addEventListener('click', () => {
                sliderTrack.scrollBy({ left: scrollStep, behavior: 'smooth' });
            });
            // Masquer la barre de scroll
            sliderTrack.style.scrollbarWidth = 'none';
            sliderTrack.style.msOverflowStyle = 'none';
            sliderTrack.style.overflowX = 'hidden';
            sliderTrack.addEventListener('wheel', e => e.preventDefault());
        }
    })();
    </script>
    </main>

        <!-- COMPARATIF / VALEUR AJOUTÉE -->
        <section class="comparatif-sazulis" style="display:flex;flex-direction:column;align-items:center;justify-content:center;margin:2em 0;">
            <h2 style="font-size:2.2em;font-weight:700;text-align:center;margin-bottom:0.2em;">
                Comparatif Sazulis : pourquoi nous choisir ?
            </h2>
            <p style="font-size:1.2em;color:#555;text-align:center;margin-bottom:1.5em;">
                Découvrez les avantages de Sazulis face aux solutions classiques : accompagnement humain, technologies modernes, sécurité, et performance sur-mesure.
            </p>
            <img src="assets/img/rapport.png" alt="Comparatif Sazulis" style="max-width:1000px;width:100%; ">
        </section>

        <!-- PRÉSENTATION BOUTIQUE & CTA -->
        <section class="expertise-responsive" itemscope itemtype="https://schema.org/ProfessionalService">
            <div class="expertise-img-wrap">
                <img src="assets/img/boutique.png" alt="Boutique Sazulis - Création site internet Vosges" class="expertise-img" itemprop="image">
            </div>
            <div class="expertise-content">
                <h2 itemprop="name">
                    Développeur web dans les Vosges : votre projet, notre expertise
                </h2>
                <p itemprop="description">
                    <strong>Développeur web freelance à Épinal, Vosges</strong>, j’accompagne les entreprises et indépendants dans la <strong>création de site internet professionnel</strong>, <strong>site e-commerce sécurisé</strong> et <strong>application web sur-mesure</strong>.<br>
                    Chaque projet est conçu avec des technologies modernes : <strong>WordPress, Next.js, PHP, JavaScript, HTML5, CSS3, Tailwind CSS</strong>.<br>
                    <span style="color:#1a2347;font-weight:600;">Basé à Arches, disponible partout en France.</span>
                <div class="expertise-badges">
                    <span class="badge" title="Paiement sécurisé">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" style="vertical-align:middle;margin-right:0.4em;">
                            <rect x="3" y="10" width="18" height="9" rx="2" fill="#c8902e"/>
                            <rect x="7" y="7" width="10" height="6" rx="3" fill="#fffaf0" stroke="#c8902e" stroke-width="1.5"/>
                            <circle cx="12" cy="15" r="1.5" fill="#fffaf0"/>
                        </svg>
                        Paiement sécurisé
                    </span>
                    <span class="badge" title="Satisfaction client">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" style="vertical-align:middle;margin-right:0.4em;">
                            <polygon points="12,2 15,9 22,9.5 17,14 18.5,21 12,17.5 5.5,21 7,14 2,9.5 9,9" fill="#ffd27d" stroke="#c8902e" stroke-width="1.2"/>
                        </svg>
                        100% clients satisfaits
                    </span>
                    <span class="badge" title="Support réactif">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" style="vertical-align:middle;margin-right:0.4em;">
                            <circle cx="12" cy="12" r="10" fill="#f2e3c3" stroke="#c8902e" stroke-width="1.2"/>
                            <rect x="8" y="11" width="8" height="2" rx="1" fill="#c8902e"/>
                            <rect x="11" y="8" width="2" height="8" rx="1" fill="#c8902e"/>
                        </svg>
                        Support réactif
                    </span>
                    <span class="badge" title="Hébergement France">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" style="vertical-align:middle;margin-right:0.4em;">
                            <ellipse cx="12" cy="12" rx="10" ry="7" fill="#fffaf0" stroke="#c8902e" stroke-width="1.2"/>
                            <rect x="7" y="10" width="10" height="4" rx="2" fill="#23408e"/>
                            <rect x="11" y="10" width="2" height="4" fill="#c8902e"/>
                        </svg>
                        Hébergement en France
                    </span>
                </div>
                <h3 style="margin-top:1.2em;">Pourquoi choisir Sazulis&nbsp;?</h3>
                <ul>
                    <li><strong>Expertise locale</strong> : développeur web basé à Épinal, Vosges</li>
                    <li><strong>Sites internet sur-mesure</strong> : vitrine, e-commerce, application web</li>
                    <li><strong>Référencement naturel (SEO)</strong> optimisé pour Google</li>
                    <li><strong>Maintenance et sécurité</strong> incluses</li>
                    <li><strong>Hébergement sécurisé</strong> et performant</li>
                    <li><strong>Accompagnement humain</strong> et conseils personnalisés</li>
                </ul>
                <p style="margin-top:1em;">
                    <strong>Technologies utilisées</strong> : WordPress, Next.js, PHP, JavaScript, HTML5, CSS3, Stripe, PayPal, hébergement sécurisé.
                </p>
                <blockquote class="expertise-quote">
                    Vous ne choisissez pas seulement un site internet,<br>vous investissez dans un outil digital performant et fiable, pensé pour le <strong>référencement Google</strong> et la <strong>sécurité</strong>.
                </blockquote>
                <div class="expertise-btn-wrap">
                    <a href="/pages/products.php" class="expertise-btn" itemprop="url">
                        <svg xmlns="http://www.w3.org/2000/svg" width="260" height="70" viewBox="0 0 260 70">
                            <defs>
                                <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#fffaf0" />
                                    <stop offset="100%" stop-color="#f2e3c3" />
                                </linearGradient>
                                <linearGradient id="gold" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" stop-color="#ffd27d" />
                                    <stop offset="100%" stop-color="#c8902e" />
                                </linearGradient>
                                <filter id="glow" x="-20%" y="-20%" width="140%" height="140%">
                                    <feGaussianBlur stdDeviation="3" result="blur" />
                                    <feMerge>
                                        <feMergeNode in="blur" />
                                        <feMergeNode in="SourceGraphic" />
                                    </feMerge>
                                </filter>
                            </defs>
                            <rect x="5" y="5" rx="35" ry="35" width="250" height="60" fill="url(#bg)" stroke="url(#gold)" stroke-width="3" filter="url(#glow)" />
                            <text x="130" y="43" text-anchor="middle" font-size="22" font-family="Arial, sans-serif" fill="#5b3a14">
                                Voir la boutique
                            </text>
                        </svg>
                    </a>
                </div>
            </div>
        </section>
        <style>
        .expertise-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 1em;
            margin: 1em 0 0.5em 0;
        }
        .badge {
            background: #f8fafc;
            border-radius: 20px;
            padding: 0.4em 1em 0.4em 0.7em;
            font-size: 1em;
            color: #1a2347;
            display: flex;
            align-items: center;
            box-shadow: 0 1px 6px #0001;
            font-weight: 500;
        }
        </style>
        <style>
        .expertise-responsive {
            display: flex;
            flex-wrap: wrap;
            align-items: stretch;
            justify-content: center;
            gap: 2em;
            margin: 2em 0;
            background: transparent;
        }
        .expertise-img-wrap {
            flex: 1 1 320px;
            min-width: 260px;
            max-width: 520px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .expertise-img {
            width: 100%;
            max-width: 480px;
            border-radius: 12px;
            box-shadow: 0 2px 16px #0002;
            object-fit: cover;
        }
        .expertise-content {
            flex: 1 1 320px;
            min-width: 260px;
            max-width: 520px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: transparent;
        }
        .expertise-content h2 {
            margin-top: 0;
            font-size: 2em;
            color: #1a2347;
        }
        .expertise-content ul {
            margin-left: 1em;
            margin-bottom: 1em;
        }
        .expertise-content li {
            margin-bottom: 0.4em;
        }
        .expertise-quote {
            font-style: italic;
            color: #007bff;
            margin-top: 1em;
        }
        .expertise-btn-wrap {
            display: flex;
            justify-content: center;
            margin-top: 2em;
        }
        .expertise-btn {
            display: block;
            width: 220px;
            transition: transform 0.15s;
        }
        .expertise-btn:hover {
            transform: scale(1.04);
        }
        @media (max-width: 900px) {
            .expertise-responsive {
                flex-direction: column;
                gap: 1.5em;
            }
            .expertise-img-wrap, .expertise-content {
                max-width: 100%;
            }
            .expertise-img {
                max-width: 100%;
            }
        }
        @media (max-width: 600px) {
            .expertise-content h2 {
                font-size: 1.3em;
            }
            .expertise-content {
                font-size: 1em;
            }
            .expertise-btn {
                width: 100%;
            }
        }
        </style>
    </main>
    <!-- Autres scripts éventuels ici -->
    <script>
    // Synchronise la hauteur de la box image sur celle de la box texte
    function syncHeroBoxHeight() {
            const imgBox = document.querySelector('.hero-carte-img');
            const txtBox = document.querySelector('.hero-carte-texte');
            if (imgBox && txtBox) {
                imgBox.style.height = txtBox.offsetHeight + 'px';
                imgBox.style.display = 'flex';
                imgBox.style.alignItems = 'center';
                imgBox.style.justifyContent = 'center';
            }
        }
        window.addEventListener('load', syncHeroBoxHeight);
        window.addEventListener('resize', syncHeroBoxHeight);
        </script>
        <?php include 'footer.php'; ?>
</body>
</html>