<?php
$basePath = isset($basePath) ? (string) $basePath : '';
$projects = [
  ['title' => 'Portfolio', 'url' => 'https://creations.sazulis.fr/index.html', 'img' => '/assets/img/sazulis-logo1.png', 'status' => 'online', 'desc' => "Maquette du portfolio Sazulis, vitrine de toutes les realisations. Code 100% HTML/CSS/JS/PHP."],
  ['title' => 'Vylmora', 'url' => 'https://vylmora.wstr.fr/', 'img' => '/assets/img/crea/vylmora.png', 'status' => 'online', 'desc' => "Site internet Vylmora, projet de jeu video."],
  ['title' => 'Hotellerie', 'url' => 'https://creations.sazulis.fr/hotellerie/index.html', 'img' => '/assets/img/crea/hotel.png', 'status' => 'mockup', 'desc' => "Maquette pour hotel: chambres, services et reservations."],
  ['title' => 'Tatoueur', 'url' => 'https://creations.sazulis.fr/incarn-studio/index.html', 'img' => '/assets/img/crea/tatoueur.png', 'status' => 'mockup', 'desc' => "Maquette pour studio tatouage: artistes et galeries."],
  ['title' => 'Restauration', 'url' => 'https://creations.sazulis.fr/restauration/index.html', 'img' => '/assets/img/crea/resto.png', 'status' => 'mockup', 'desc' => "Maquette restauration: carte, menu et services."],
  ['title' => 'Foodtruck', 'url' => 'https://creations.sazulis.fr/foodtruck/index.html', 'img' => '/assets/img/crea/foodtruck.png', 'status' => 'mockup', 'desc' => "Maquette foodtruck: menu, concept et univers."],
  ['title' => 'Boucherie', 'url' => 'https://creations.sazulis.fr/boucherie/index.html', 'img' => '/assets/img/crea/boucherie.png', 'status' => 'mockup', 'desc' => "Maquette boucherie artisanale: produits et savoir-faire."],
  ['title' => 'Educateur canin', 'url' => 'https://creations.sazulis.fr/ethozen/index.html', 'img' => '/assets/img/crea/educateur-canin.png', 'status' => 'mockup', 'desc' => "Maquette educateur canin: methodes et resultats."],
  ['title' => 'Maison de retraite', 'url' => 'https://creations.sazulis.fr/retraite/index.html', 'img' => '/assets/img/crea/retraite.png', 'status' => 'mockup', 'desc' => "Maquette maison de retraite: services et activites."],
  ['title' => 'Garagiste', 'url' => 'https://creations.sazulis.fr/automobile/index.html', 'img' => '/assets/img/crea/garage.png', 'status' => 'mockup', 'desc' => "Maquette garage automobile: prestations et tarifs."],
  ['title' => 'Masseur', 'url' => 'https://creations.sazulis.fr/massage/index.html', 'img' => '/assets/img/crea/masseur.png', 'status' => 'mockup', 'desc' => "Maquette pour masseur: soins, tarifs et prise de rendez-vous."],
  ['title' => 'Yoga', 'url' => 'https://creations.sazulis.fr/yoga/index.html', 'img' => '/assets/img/crea/yoga.png', 'status' => 'mockup', 'desc' => "Maquette studio yoga: cours, horaires et informations."],
  ['title' => 'Piscine naturelle', 'url' => 'https://creations.sazulis.fr/piscine-naturelle/index.html', 'img' => '/assets/img/crea/piscine-naturelle.png', 'status' => 'mockup', 'desc' => "Maquette piscine naturelle: realisations et technique."],
  ['title' => 'Nettoyage sepulture', 'url' => 'https://creations.sazulis.fr/renov-stele/index.html', 'img' => '/assets/img/crea/renov-stele.png', 'status' => 'mockup', 'desc' => "Maquette nettoyage de sepulture: prestations et tarifs."],
  ['title' => 'Wedding planner', 'url' => 'https://creations.sazulis.fr/weeding-planner/index.html', 'img' => '/assets/img/crea/weeding-planner.png', 'status' => 'mockup', 'desc' => "Maquette organisation mariage: planning, prestataires, conseils."],
  ['title' => 'Service menage', 'url' => 'https://creations.sazulis.fr/nettoyage/index.html', 'img' => '/assets/img/crea/nettoyage.png', 'status' => 'mockup', 'desc' => "Maquette service menage: offres et prise de contact."],
  ['title' => 'Revetement de sol', 'url' => 'https://creations.sazulis.fr/revetement-de-sol/index.html', 'img' => '/assets/img/crea/encours.png', 'status' => 'mockup', 'desc' => "Maquette revetement de sol: materiaux et realisations."],
  ['title' => 'Epoxy', 'url' => 'https://creations.sazulis.fr/epoxy/index.html', 'img' => '/assets/img/crea/epoxy.png', 'status' => 'mockup', 'desc' => "Maquette travaux epoxy: rendus et techniques."],
  ['title' => 'DJ', 'url' => 'https://creations.sazulis.fr/dj/index.html', 'img' => '/assets/img/crea/dj.png', 'status' => 'mockup', 'desc' => "Maquette DJ: prestations et evenements."],
  ['title' => 'Web application', 'url' => 'https://creations.sazulis.fr/web-application/index.html', 'img' => '/assets/img/crea/web-application.png', 'status' => 'mockup', 'desc' => "Maquette application web: interfaces dynamiques."],
  ['title' => 'WordPress', 'url' => 'https://creations.sazulis.fr/wordpress/index.html', 'img' => '/assets/img/crea/wordpress.png', 'status' => 'mockup', 'desc' => "Maquette WordPress personnalisee: design et fonctionnalites."],
];
?>

<style>
.creations-wrap { max-width: 1150px; margin: 0 auto; }
.creations-hero {
  margin-bottom: 1.4em;
  text-align: center;
  background: rgba(255,255,255,.93);
  border: 1px solid rgba(255,255,255,.45);
  box-shadow: 0 4px 24px rgba(0,0,0,.12);
  border-radius: 20px;
  padding: 1.6em;
}
.creations-hero h1 {
  color: #1a2347;
  margin: 0 0 .4em;
  font-size: 2rem;
}
.creations-hero p { color: #334155; margin: 0; line-height: 1.65; }
.creations-hero .note { color: #d2691e; font-weight: 700; margin-top: .5em; }

.creations-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.1em;
}

.crea-card {
  background: rgba(255,255,255,.78);
  backdrop-filter: blur(7px);
  -webkit-backdrop-filter: blur(7px);
  border-radius: 18px;
  border: 1.5px solid rgba(220,220,220,.35);
  box-shadow: 0 4px 24px rgba(0,0,0,.09);
  padding: 1.1em;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-decoration: none;
  transition: box-shadow .18s, transform .18s, background .18s;
  position: relative;
  overflow: hidden;
}

.crea-card-ribbon {
  position: absolute;
  top: 18px;
  right: -42px;
  width: 160px;
  padding: .42em 0;
  text-align: center;
  font-size: .74rem;
  font-weight: 800;
  letter-spacing: .08em;
  text-transform: uppercase;
  transform: rotate(45deg);
  color: #fff;
  box-shadow: 0 8px 18px rgba(0,0,0,.16);
  z-index: 2;
}

.crea-card-ribbon.online {
  background: linear-gradient(90deg, #16a34a, #22c55e);
}

.crea-card-ribbon.mockup {
  background: linear-gradient(90deg, #ea580c, #f59e0b);
}

.crea-card:hover {
  box-shadow: 0 8px 32px rgba(0,0,0,.16);
  background: rgba(255,255,255,.9);
  transform: translateY(-4px) scale(1.02);
}

.crea-card img {
  width: 180px;
  height: 120px;
  object-fit: cover;
  border-radius: 8px;
  margin-bottom: .9em;
  box-shadow: 0 1px 6px rgba(0,0,0,.14);
}

.crea-card-title {
  font-weight: 800;
  color: #1a2347;
  margin-bottom: .45em;
  font-size: 1.05rem;
}

.crea-card-desc {
  color: #334155;
  font-size: .95rem;
  line-height: 1.5;
}

@media (max-width: 650px) {
  .creations-hero h1 { font-size: 1.45rem; }
}
</style>

<div class="creations-wrap">
  <section class="creations-hero">
    <h1>Mes realisations</h1>
    <p>
      Decouvre une selection de projets web realises pour des clients de tous horizons: sites vitrines,
      e-commerce, portfolios, applications metiers et plus encore.
    </p>
    <p class="note">Clique sur une realisation pour la decouvrir en detail.</p>
  </section>

  <section class="creations-grid">
    <?php foreach ($projects as $project): ?>
      <?php
      $imgUrl = htmlspecialchars($basePath . '/public' . $project['img'], ENT_QUOTES, 'UTF-8');
      $status = $project['status'] ?? 'mockup';
      $statusLabel = $status === 'online' ? 'En ligne' : 'Maquette';
      ?>
      <a class="crea-card" href="<?= htmlspecialchars($project['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
        <span class="crea-card-ribbon <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
        <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8') ?>">
        <div class="crea-card-title"><?= htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="crea-card-desc"><?= htmlspecialchars($project['desc'], ENT_QUOTES, 'UTF-8') ?></div>
      </a>
    <?php endforeach; ?>
  </section>
</div>
