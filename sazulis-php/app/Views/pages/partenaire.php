<?php $basePath = isset($basePath) ? (string) $basePath : ''; ?>

<style>
.partner-wrap { max-width: 980px; margin: 0 auto; }
.partner-shell {
  background: rgba(255,255,255,.96);
  border-radius: 18px;
  box-shadow: 0 4px 24px rgba(210, 105, 30, 0.16);
  padding: 2em;
  color: #1a2347;
}
.partner-hero {
  background: linear-gradient(120deg, #ffe066 0%, #ffb700 60%, #fffbe6 100%);
  border-radius: 16px;
  padding: 1.6em;
  display: flex;
  align-items: center;
  gap: 1.2em;
  margin-bottom: 1.5em;
}
.partner-logo {
  width: 110px;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 2px 8px rgba(255, 224, 102, 0.35);
}
.partner-hero h1 { margin: 0; font-size: 2rem; color: #1a2347; }
.partner-subtitle { margin-top: .35em; font-size: 1.02rem; color: #222; }
.partner-badges { margin-top: .85em; display: flex; gap: .7em; flex-wrap: wrap; }
.partner-badge {
  display: inline-block;
  padding: .45em .9em;
  border-radius: 999px;
  font-weight: 700;
  font-size: .92rem;
}
.partner-badge.gold { background: #fffbe6; color: #d2691e; }
.partner-badge.blue { background: #fff; color: #2a7ae2; }

.partner-story {
  text-align: center;
  color: #334155;
  font-size: 1.06rem;
  line-height: 1.6;
  margin-bottom: 1.7em;
}
.partner-story .highlight { color: #d2691e; font-weight: 800; }
.promo-code {
  display: inline-block;
  margin-top: .4em;
  background: linear-gradient(90deg, #ffe066, #ffb700);
  color: #222;
  padding: .55em 1.2em;
  border-radius: 10px;
  font-size: 1.15rem;
  font-weight: 900;
}
.copy-btn {
  margin-top: .85em;
  background: #2a7ae2;
  color: #fff;
  border: 0;
  border-radius: 10px;
  padding: .7em 1.5em;
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
}

.partner-title-orange {
  color: #d2691e;
  text-align: center;
  font-size: 1.26rem;
  margin: 0 0 .9em;
}
.partner-title-blue {
  color: #2a7ae2;
  text-align: center;
  font-size: 1.1rem;
  margin: 0 0 .8em;
}
.partner-cards {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1em;
  margin-bottom: 1.6em;
}
.partner-card {
  border-radius: 12px;
  padding: 1em;
  text-align: center;
}
.partner-card.gold { background: linear-gradient(120deg, #fffbe6, #ffe066 80%); }
.partner-card.blue { background: linear-gradient(120deg, #fff, #f8fafc 80%); }
.partner-card .icon { font-size: 1.5rem; }
.partner-card h3 { margin: .45em 0 .3em; font-size: 1rem; }
.partner-card p { margin: 0; color: #334155; font-size: .95rem; }

.partner-quote {
  background: #f8fafc;
  border-left: 5px solid #ffe066;
  padding: 1em 1.2em;
  border-radius: 10px;
  color: #334155;
  margin-bottom: 1.5em;
}

.partner-cta { text-align: center; }
.partner-cta .btn {
  background: linear-gradient(90deg, #ffe066, #ffb700);
  color: #222;
  font-weight: 900;
}
.partner-note {
  margin-top: 1.1em;
  color: #64748b;
  font-size: .92rem;
}

@media (max-width: 900px) {
  .partner-hero { flex-direction: column; text-align: center; }
  .partner-cards { grid-template-columns: 1fr; }
}
</style>

<div class="partner-wrap">
  <section class="partner-shell">
    <div class="partner-hero">
      <img src="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/public/assets/img/bytemind.png" alt="Byteminds" class="partner-logo">
      <div>
        <h1>Sazulis x Byteminds</h1>
        <p class="partner-subtitle">L'alliance de deux experts pour accelerer votre presence digitale.</p>
        <div class="partner-badges">
          <span class="partner-badge gold">Offre exclusive Sazulis</span>
          <span class="partner-badge blue">-10% sur tous les services</span>
        </div>
      </div>
    </div>

    <div class="partner-story">
      Grace au partenariat <strong>Sazulis x Byteminds</strong>, tu beneficies d'un accompagnement technique complet et d'une offre
      <span class="highlight">reservee aux clients Sazulis</span>.<br>
      <span class="promo-code" id="promo-code">SAZU10</span><br>
      <button id="copy-btn" class="copy-btn" type="button">Copier le code</button>
      <p style="margin-top:.9em;">Utilise ce code sur <a class="link" href="https://byteminds.fr/" target="_blank" rel="noopener">byteminds.fr</a> pour obtenir la remise immediate.</p>
    </div>

    <h2 class="partner-title-orange">Ce que vous gagnez concretement</h2>
    <div class="partner-cards">
      <article class="partner-card gold">
        <div class="icon">🚀</div>
        <h3>Visibilite accrue</h3>
        <p>Site plus visible, SEO optimise, design adapte a ton activite.</p>
      </article>
      <article class="partner-card blue">
        <div class="icon">🔒</div>
        <h3>Securite et serenite</h3>
        <p>Maintenance, suivi et fiabilite technique pour ton projet.</p>
      </article>
      <article class="partner-card gold">
        <div class="icon">🤝</div>
        <h3>Accompagnement humain</h3>
        <p>Un interlocuteur dedie, du cadrage au lancement.</p>
      </article>
    </div>

    <h2 class="partner-title-blue">Ils ont choisi Byteminds</h2>
    <blockquote class="partner-quote">
      "Grace a Byteminds, notre site a passe un cap: plus de visibilite, plus de clients et un vrai accompagnement humain."<br>
      <strong style="color:#d2691e;">- Client Sazulis</strong>
    </blockquote>

    <div class="partner-cta">
      <a href="https://byteminds.fr/" target="_blank" rel="noopener" class="btn">Decouvrir Byteminds.fr</a>
      <p class="partner-note">Offre reservee aux clients Sazulis. Code valable une seule fois par client.</p>
    </div>
  </section>
</div>

<script>
(() => {
  const copyBtn = document.getElementById('copy-btn');
  if (!copyBtn) return;
  copyBtn.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText('SAZU10');
      copyBtn.textContent = 'Copie !';
      setTimeout(() => { copyBtn.textContent = 'Copier le code'; }, 1200);
    } catch {
      copyBtn.textContent = 'Impossible de copier';
      setTimeout(() => { copyBtn.textContent = 'Copier le code'; }, 1200);
    }
  });
})();
</script>
