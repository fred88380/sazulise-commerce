<?php include '../navbar.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Partenariat Sazulis & Byteminds | Sazulis</title>
    <meta name="description" content="Profitez de -10% chez Byteminds avec le code SAZU10, réservé aux clients Sazulis. Offre exclusive de partenariat web, création de sites, SEO, maintenance et accompagnement technique.">
    <meta name="keywords" content="partenariat, byteminds, sazulis, code promo, réduction, web, création site, SEO, maintenance, freelance, agence">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../assets/img/sazulis-ico.ico">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: url('../assets/img/unique.png') center/cover no-repeat fixed;
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        main {
            max-width: 700px;
            margin: 3em auto 2em auto;
            padding: 2.5em 2em;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px #d2691e22;
            text-align: center;
        }
        
        /* Bandeau d'en-tête */
        .hero-banner {
            background: linear-gradient(120deg, #ffe066 0%, #ffb700 60%, #fffbe6 100%);
            border-radius: 18px 18px 0 0;
            padding: 2.2em 2em 1.5em 2em;
            display: flex;
            align-items: center;
            gap: 2em;
            margin-bottom: 2em;
            box-shadow: 0 4px 24px #ffe06633;
            position: relative;
            overflow: hidden;
            text-align: left;
        }
        .hero-banner::after {
            content: "";
            position: absolute;
            right: -60px;
            top: -60px;
            width: 180px;
            height: 180px;
            background: radial-gradient(circle, #ffe066 60%, #fff0 100%);
            z-index: 1;
        }
        .partenaire-logo {
            width: 120px;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 2px 8px #ffe06655;
            z-index: 2;
        }
        .hero-content {
            flex: 1;
            z-index: 2;
        }
        h1 {
            margin: 0;
            font-size: 2.3em;
            font-weight: 900;
            color: #1a2347;
            letter-spacing: 0.01em;
        }
        .hero-subtitle {
            font-size: 1.18em;
            color: #222;
            font-weight: 600;
            margin-top: 0.3em;
        }
        .badge-container {
            margin-top: 1.2em;
            display: flex;
            gap: 1em;
            flex-wrap: wrap;
        }
        .badge-sazulis {
            background: #fffbe6;
            color: #d2691e;
            font-weight: 700;
            padding: 0.5em 1.2em;
            border-radius: 8px;
            box-shadow: 0 2px 8px #ffd70033;
            font-size: 1.08em;
        }
        .badge-promo {
            background: #fff;
            color: #2a7ae2;
            font-weight: 700;
            padding: 0.5em 1.2em;
            border-radius: 8px;
            box-shadow: 0 2px 8px #2a7ae233;
            font-size: 1.08em;
        }

        /* Section Storytelling & Code */
        .story-text {
            font-size: 1.18em;
            color: #444;
            text-align: center;
            max-width: 650px;
            margin: 0 auto 1.2em auto;
            line-height: 1.6;
        }
        .sazulis-color {
            color: #d2691e;
            font-weight: 700;
        }
        .promo-wrapper {
            display: inline-block;
            margin-top: 0.7em;
            font-size: 1.08em;
            color: #2a7ae2;
            font-weight: 700;
        }
        .promo-code {
            background: linear-gradient(90deg, #ffe066, #ffb700);
            color: #222;
            font-size: 1.3em;
            font-weight: 900;
            border-radius: 10px;
            padding: 0.6em 1.5em;
            display: inline-block;
            box-shadow: 0 2px 8px #ffe06633;
            margin-left: 0.5em;
        }
        #copy-btn {
            margin: 0.7em 0 1.2em 0;
            background: #2a7ae2;
            color: #fff;
            font-weight: 700;
            border: none;
            border-radius: 8px;
            padding: 0.7em 2em;
            font-size: 1.1em;
            cursor: pointer;
            transition: background 0.2s;
        }
        #copy-btn:hover {
            background: #1e5cb0;
        }

        /* Section Avantages / Features */
        .section-title-orange {
            color: #d2691e;
            font-size: 1.35em;
            font-weight: 900;
            margin-bottom: 1.1em;
            text-align: center;
            letter-spacing: 0.01em;
        }
        .cards-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1.5em;
        }
        .card {
            min-width: 210px;
            max-width: 260px;
            border-radius: 12px;
            padding: 1.3em 1em;
            text-align: center;
        }
        .card-yellow {
            background: linear-gradient(120deg, #fffbe6, #ffe066 80%);
            box-shadow: 0 2px 8px #ffe06633;
        }
        .card-blue {
            background: linear-gradient(120deg, #fff, #f8fafc 80%);
            box-shadow: 0 2px 8px #2a7ae233;
        }
        .card-icon {
            font-size: 1.7em;
        }
        .card-title {
            font-weight: 700;
            color: #1a2347;
            margin-bottom: 0.3em;
        }
        .card-desc {
            font-size: 1em;
            color: #444;
        }

        /* Témoignages */
        .section-title-blue {
            color: #2a7ae2;
            font-size: 1.15em;
            font-weight: 800;
            margin-bottom: 0.7em;
            text-align: center;
        }
        blockquote {
            background: #f8fafc;
            border-left: 5px solid #ffe066;
            padding: 1.2em 1.5em;
            margin: 0 auto 1.2em auto;
            max-width: 520px;
            font-style: italic;
            color: #444;
            box-shadow: 0 2px 8px #0001;
            border-radius: 10px;
            text-align: left;
        }

        /* Bouton d'action principal */
        .byteminds-btn {
            background: linear-gradient(90deg, #ffe066, #ffb700);
            color: #222;
            font-size: 1.22em;
            font-weight: 900;
            border: none;
            border-radius: 8px;
            padding: 1.1em 2.8em;
            box-shadow: 0 2px 8px #ffe06633;
            text-decoration: none;
            transition: background 0.2s;
            display: inline-block;
            margin-bottom: 1.2em;
            letter-spacing: 0.01em;
        }
        .byteminds-btn:hover {
            background: #ffe066;
        }

        /* Responsive */
        @media (max-width: 600px) {
            main {
                padding: 1em;
                margin-top: 1.5em;
            }
            .hero-banner {
                flex-direction: column;
                text-align: center;
                padding: 1.5em 1em;
            }
            .badge-container {
                justify-content: center;
            }
            h1 {
                font-size: 1.8em;
            }
            .promo-wrapper {
                display: block;
                margin-bottom: 0.5em;
            }
            .promo-code {
                margin-left: 0;
                margin-top: 0.5em;
            }
        }
    </style>
</head>
<body>
<main>
    <div class="hero-banner">
        <img src="../assets/img/bytemind.png" alt="Byteminds" class="partenaire-logo">
        <div class="hero-content">
            <h1>Sazulis & Byteminds</h1>
            <div class="hero-subtitle">L’alliance de deux experts pour propulser votre présence digitale.</div>
            <div class="badge-container">
                <span class="badge-sazulis">Offre exclusive Sazulis</span>
                <span class="badge-promo">-10% sur tous les services</span>
            </div>
        </div>
    </div>

    <section style="margin-bottom:2.2em;">
        <p class="story-text">
            Imaginez : un site web qui vous ressemble, visible, performant, sécurisé, et un accompagnement humain à chaque étape.<br>
            Grâce au partenariat <b>Sazulis x Byteminds</b>, vous bénéficiez d’un savoir-faire reconnu et d’une offre <span class="sazulis-color">réservée aux clients Sazulis</span>.<br>
            <span class="promo-wrapper">Votre code exclusif : <span class="promo-code" id="promo-code">SAZU10</span></span>
        </p>
        <button id="copy-btn" onclick="navigator.clipboard.writeText('SAZU10');this.innerText='Copié !';setTimeout(()=>this.innerText='Copier le code',1200);">
            Copier le code
        </button>
        <div style="margin-bottom:1.5em; font-size:1.08em; color:#333;">
            Utilisez ce code lors de votre commande sur <a href="https://byteminds.fr/" target="_blank" style="color:#2a7ae2; font-weight:600; text-decoration:underline;">byteminds.fr</a> et bénéficiez d'une remise immédiate sur tous les services !
        </div>
    </section>

    <section style="margin-bottom:2.5em;">
        <h2 class="section-title-orange">Ce que vous gagnez concrètement</h2>
        <div class="cards-container">
            <div class="card card-yellow">
                <div class="card-icon">🚀</div>
                <div class="card-title">Visibilité accrue</div>
                <div class="card-desc">Votre site attire plus de clients grâce à un SEO et un design sur-mesure.</div>
            </div>
            <div class="card card-blue">
                <div class="card-icon">🔒</div>
                <div class="card-title">Sécurité & sérénité</div>
                <div class="card-desc">Maintenance, sécurité et support technique inclus, pour une tranquillité totale.</div>
            </div>
            <div class="card card-yellow">
                <div class="card-icon">🤝</div>
                <div class="card-title">Accompagnement humain</div>
                <div class="card-desc">Un expert dédié, à l’écoute de vos besoins, pour chaque étape de votre projet.</div>
            </div>
        </div>
    </section>

    <section style="margin-bottom:2.5em;">
        <h2 class="section-title-blue">Ils ont choisi Byteminds</h2>
        <blockquote style="margin: 0 auto;">
            « Grâce à Byteminds, notre site est passé à la vitesse supérieure : plus de visibilité, plus de clients, et un accompagnement vraiment humain. »<br>
            <span style="display:block; margin-top:0.7em; font-weight:700; color:#d2691e;">— Client Sazulis</span>
        </blockquote>
    </section>

    <section style="text-align:center;">
        <a href="https://byteminds.fr/" target="_blank" class="byteminds-btn">
            Découvrir Byteminds.fr
        </a>
        <div style="margin-top:2em; color:#888; font-size:0.98em;">
            Offre réservée aux clients Sazulis. Code valable une seule fois par client chez Byteminds.
        </div>
    </section>
</main>
<?php include '../footer.php'; ?>
</body>
</html>