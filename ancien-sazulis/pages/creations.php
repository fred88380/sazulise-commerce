<?php
require_once __DIR__ . '/../protect.php';
include '../navbar.php';

$projects = [
    [ 'title' => 'portfolio', 'url' => 'https://creations.sazulis.fr/index.html', 'img' => '../assets/img/sazulis-logo1.png', 'desc' => "Maquette du portfolio Sazulis, vitrine de toutes les réalisations. Codé 100% HTML/CSS/JS/PHP. Permet de visualiser l'ensemble des projets." ],    
    [ 'title' => 'hotellerie', 'url' => 'https://creations.sazulis.fr/hotellerie/index.html', 'img' => '../assets/img/crea/hotel.png', 'desc' => "Maquette pour hôtel. Présente les chambres, services et réservations. Codé en HTML/CSS/JS/PHP." ],
    [ 'title' => 'Tatoueur', 'url' => 'https://creations.sazulis.fr/incarn-studio/index.html', 'img' => '../assets/img/crea/tatoueur.png', 'desc' => "Maquette pour studio de tatouage. Présente les artistes et galeries. Codé en HTML/CSS/JS/PHP." ],
    [ 'title' => 'Restauration', 'url' => 'https://creations.sazulis.fr/restauration/index.html', 'img' => '../assets/img/crea/resto.png', 'desc' => "Maquette d'un site de restauration. Présente le menu, la carte, et les services. Codé en HTML/CSS/JS/PHP." ],
    [ 'title' => 'Foodtruck', 'url' => 'https://creations.sazulis.fr/foodtruck/index.html', 'img' => '../assets/img/crea/foodtruck.png', 'desc' => "Maquette pour foodtruck. Présente le menu et le concept. Codé en HTML/CSS/JS/PHP." ],
    [ 'title' => 'Boucherie', 'url' => 'https://creations.sazulis.fr/boucherie/index.html', 'img' => '../assets/img/crea/boucherie.png', 'desc' => "Maquette pour boucherie artisanale. Présente les produits et savoir-faire. Codé en HTML/CSS/JS/PHP." ],
    [ 'title' => 'Educateur canin', 'url' => 'https://creations.sazulis.fr/ethozen/index.html', 'img' => '../assets/img/crea/educateur-canin.png', 'desc' => "Maquette pour éducateur canin. Présente les méthodes et résultats. Codé en HTML/CSS/JS/PHP." ],
    [ 'title' => 'maison de retraite', 'url' => 'https://creations.sazulis.fr/retraite/index.html', 'img' => '../assets/img/crea/retraite.png', 'desc' => "Maquette pour maison de retraite. Présente les services et activités. Codé en HTML/CSS/JS/PHP." ],
    [ 'title' => 'garagiste', 'url' => 'https://creations.sazulis.fr/automobile/index.html', 'img' => '../assets/img/crea/garage.png', 'desc' => "Maquette pour garage automobile. Présente les prestations et tarifs. Codé en HTML/CSS/JS/PHP." ],
    [ 'title' => 'vylmora', 'url' => 'https://vylmora.wstr.fr/', 'img' => '../assets/img/crea/vylmora.png', 'desc' => "site internet Vylmora, projet de jeu vidéo. Présente les mécaniques et univers. Codé en HTML/CSS/JS/PHP." ],
    [ 'title' => 'Masseur', 'url' => 'https://creations.sazulis.fr/massage/index.html', 'img' => '../assets/img/crea/masseur.png', 'desc' => "Maquette pour masseur professionnel. Présente les soins et tarifs. Codé en HTML/CSS/JS/PHP." ],
    [ 'title' => 'Yoga', 'url' => 'https://creations.sazulis.fr/yoga/index.html', 'img' => '../assets/img/crea/yoga.png', 'desc' => "Maquette pour un studio de yoga. Affiche les cours, horaires et infos. Réalisé en HTML/CSS/JS/PHP." ],
    [ 'title' => 'Piscine naturelle', 'url' => 'https://creations.sazulis.fr/piscine-naturelle/index.html', 'img' => '../assets/img/crea/piscine-naturelle.png', 'desc' => "Maquette pour piscine naturelle. Présente les réalisations et infos techniques. Codé en HTML/CSS/JS/PHP." ],
    [ 'title' => 'Nettoyage de sépulture', 'url' => 'https://creations.sazulis.fr/renov-stele/index.html', 'img' => '../assets/img/crea/renov-stele.png', 'desc' => "Maquette d'un service de nettoyage de sépulture. Présente les prestations et tarifs. Codé en HTML/CSS/JS/PHP." ],
    [ 'title' => 'Weeding planner', 'url' => 'https://creations.sazulis.fr/weeding-planner/index.html', 'img' => '../assets/img/crea/weeding-planner.png', 'desc' => "Maquette d'organisation de mariage. Planning, prestataires, conseils. Codé en HTML/CSS/JS/PHP." ],
    [ 'title' => 'Femme ou homme de ménage', 'url' => 'https://creations.sazulis.fr/nettoyage/index.html', 'img' => '../assets/img/crea/nettoyage.png', 'desc' => "Maquette pour service de ménage. Présente les offres et la prise de contact. Codé en HTML/CSS/JS/PHP." ],
    [ 'title' => 'Revetement de sol', 'url' => 'https://creations.sazulis.fr/revetement-de-sol/index.html', 'img' => '../assets/img/crea/encours.png', 'desc' => "Maquette pour revêtement de sol. Présente les matériaux et réalisations. Codé en HTML/CSS/JS/PHP." ],
    [ 'title' => 'Epoxy', 'url' => 'https://creations.sazulis.fr/epoxy/index.html', 'img' => '../assets/img/crea/epoxy.png', 'desc' => "Maquette pour travaux epoxy. Présente les réalisations et techniques. Codé en HTML/CSS/JS/PHP." ],
    [ 'title' => 'DJ', 'url' => 'https://creations.sazulis.fr/dj/index.html', 'img' => '../assets/img/crea/dj.png', 'desc' => "Maquette pour DJ professionnel. Présente les prestations et événements. Codé en HTML/CSS/JS/PHP." ],
    [ 'title' => 'Web application', 'url' => 'https://creations.sazulis.fr/web-application/index.html', 'img' => '../assets/img/crea/web-application.png', 'desc' => "Maquette d'une application web. Démonstration d'interfaces dynamiques. Codé en HTML/CSS/JS/PHP." ],
    [ 'title' => 'Wordpress', 'url' => 'https://creations.sazulis.fr/wordpress/index.html', 'img' => '../assets/img/crea/wordpress.png', 'desc' => "Maquette d'un site WordPress personnalisé. Présente les fonctionnalités et le design. Codé en HTML/CSS/JS/PHP." ],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php
    // 1. Mots-clés et balises uniques pour le Portfolio de Réalisations (SEO Local)
    $page_title = "Portfolio & Réalisations Web | Sazulis Freelance Épinal";
    $page_description = "Découvrez le catalogue des créations de Sazulis : maquettes et sites web vitrines, e-commerce et applications sur-mesure développés à Épinal et dans les Vosges.";

    // 2. Inclusion de ton head dynamique
    include __DIR__ . '/../head.php'; 
    ?>
    <style>
        body {
            background: url('../assets/img/unique.png') center/cover no-repeat fixed;
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        main.creations-main {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            justify-content: flex-start;
            background: transparent;
            margin-bottom: 2em;
        }
        .creations-hero {
            padding: 4em 0 2em 0;
            text-align: center;
        }
        .creations-hero h1 {
            font-size: 2.5em;
            font-weight: 900;
            color: #1a2347;
            margin-bottom: 0.3em;
        }
        .creations-hero-desc {
            font-size: 1.2em;
            color: #333;
            margin-bottom: 1.2em;
        }
        .creations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px,1fr));
            gap: 2em;
            max-width: 1100px;
            margin: 2em auto 2em auto;
        }
        .crea-card {
            background: rgba(255,255,255,0.72);
            backdrop-filter: blur(7px);
            -webkit-backdrop-filter: blur(7px);
            border-radius: 18px;
            border: 1.5px solid rgba(220,220,220,0.35);
            box-shadow: 0 4px 24px #0001;
            padding: 1.5em 1em 1.2em 1em;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none; /* Évite que le texte soit souligné à cause du lien */
            transition: box-shadow 0.18s, transform 0.18s, background 0.18s;
        }
        .crea-card:hover {
            box-shadow: 0 8px 32px #0002;
            background: rgba(255,255,255,0.88);
            transform: translateY(-4px) scale(1.03);
        }
        .crea-card img {
            width: 180px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 1em;
            box-shadow: 0 1px 6px #0001;
        }
        .crea-card-title {
            font-weight: 700;
            color: #1a2347;
            margin-bottom: 0.5em;
            font-size: 1.15em;
        }
        .crea-card-desc {
            color: #444;
            font-size: 1em;
            margin-bottom: 0.7em;
        }
        @media (max-width: 900px) {
            .creations-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 600px) {
            .creations-grid {
                grid-template-columns: 1fr;
            }
            .creations-hero h1 {
                font-size: 1.5em;
            }
        }
    </style>
</head>
<body>
<main class="creations-main">
    <section class="creations-hero">
        <h1>Mes réalisations</h1>
        <div class="creations-hero-desc">
            Découvrez ici une sélection de projets web réalisés pour des clients de tous horizons : sites vitrines, e-commerce, portfolios, applications métiers, et plus encore.<br>
            Chaque création est conçue sur-mesure, avec un souci du détail, de la performance et de l’accompagnement personnalisé.<br>
            <span style="color:#d2691e;font-weight:600;">Cliquez sur une réalisation pour la découvrir en détail.</span>
        </div>
    </section>
    <section>
        <div class="creations-grid">
            <?php foreach($projects as $p): ?>
                <a class="crea-card" href="<?= htmlspecialchars($p['url']) ?>" target="_blank" rel="noopener">
                    <img src="<?= htmlspecialchars($p['img']) ?>" alt="<?= htmlspecialchars($p['title']) ?>">
                    <div class="crea-card-title"><?= htmlspecialchars(ucfirst($p['title'])) ?></div>
                    <div class="crea-card-desc"><?= htmlspecialchars($p['desc']) ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<?php include '../footer.php'; ?>
</body>
</html>