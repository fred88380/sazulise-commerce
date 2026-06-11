<?php
$products = isset($products) && is_array($products) ? $products : [];
$totalStock = isset($totalStock) ? (int) $totalStock : 0;
$orders = isset($orders) && is_array($orders) ? $orders : [];
$basePath = isset($basePath) ? (string) $basePath : '';
$proxyEndpoint = $basePath . '/public/proxy-ia.php';
$scraperPage = $basePath . '/public/scraper.php';
$totalUsers = isset($totalUsers) ? (int) $totalUsers : 0;
$totalOrders = isset($totalOrders) ? (int) $totalOrders : 0;
$totalRevenue = isset($totalRevenue) ? (float) $totalRevenue : 0.0;

$validatedAcompte = 0;
$validatedSolde = 0;
$pendingSignatures = 0;

foreach ($orders as $order) {
    if ((int) ($order['acompte_paye'] ?? 0) === 1) {
        $validatedAcompte++;
    }
    if ((int) ($order['solde_regle'] ?? 0) === 1) {
        $validatedSolde++;
    }
    if (empty($order['client_signature_path'])) {
        $pendingSignatures++;
    }
}
?>

<style>
.admin-shell {
  --d-bg: linear-gradient(160deg, #061225 0%, #0f2336 50%, #2d1f14 100%);
  --d-panel: rgba(9, 20, 33, 0.88);
  --d-panel-strong: rgba(12, 27, 43, 0.96);
  --d-line: rgba(255, 255, 255, 0.14);
  --d-text: #e8f2fb;
  --d-muted: #b3c7d9;
  --d-accent: #e8be58;
  --d-accent-2: #7ad3ff;
  --d-ok: #55d199;
  --d-wait: #ff9e5a;
  --d-danger: #f37d7d;
  margin-top: 1.4rem;
  display: grid;
  gap: 1.3rem;
  color: var(--d-text);
}

.admin-hero {
  position: relative;
  overflow: hidden;
  border: 1px solid var(--d-line);
  border-radius: 22px;
  padding: 1.7rem;
  background: var(--d-bg);
  box-shadow: 0 24px 48px rgba(2, 8, 14, 0.5);
}

.admin-hero::before {
  content: '';
  position: absolute;
  width: 300px;
  height: 300px;
  right: -120px;
  top: -120px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(232, 190, 88, 0.28), rgba(232, 190, 88, 0));
  pointer-events: none;
}

.admin-hero h1 {
  margin: 0;
  font-size: clamp(1.8rem, 3.8vw, 2.8rem);
  font-family: 'Bebas Neue', sans-serif;
  letter-spacing: 0.03em;
}

.admin-hero p {
  margin: 0.4rem 0 0;
  max-width: 68ch;
  color: var(--d-muted);
}

.admin-status-strip {
  margin-top: 1.2rem;
  display: flex;
  gap: 0.6rem;
  flex-wrap: wrap;
}

.admin-pill {
  border-radius: 999px;
  border: 1px solid var(--d-line);
  background: rgba(255, 255, 255, 0.06);
  padding: 0.35rem 0.75rem;
  font-size: 0.76rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-weight: 700;
}

.admin-pill.is-ok { color: var(--d-ok); border-color: rgba(85, 209, 153, 0.38); }
.admin-pill.is-wait { color: var(--d-wait); border-color: rgba(255, 158, 90, 0.4); }

.admin-kpis {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0.9rem;
}

.admin-kpi {
  border: 1px solid var(--d-line);
  border-radius: 18px;
  background: var(--d-panel);
  backdrop-filter: blur(2px);
  padding: 1rem;
  display: grid;
  gap: 0.2rem;
}

.admin-kpi span {
  color: var(--d-muted);
  font-size: 0.76rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.admin-kpi strong {
  font-size: clamp(1.2rem, 2.2vw, 1.9rem);
  line-height: 1.1;
  color: var(--d-text);
}

.admin-kpi small {
  color: var(--d-accent-2);
}

.admin-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.2rem;
}

.admin-panel {
  border: 1px solid var(--d-line);
  border-radius: 20px;
  background: var(--d-panel-strong);
  overflow: hidden;
}

.admin-panel-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.2rem;
  border-bottom: 1px solid var(--d-line);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0));
}

.admin-panel-head h2 {
  margin: 0;
  font-size: 1.02rem;
}

.admin-panel-head span {
  color: var(--d-muted);
  font-size: 0.8rem;
}

.admin-table-wrap {
  overflow: auto;
}

.admin-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 760px;
}

.admin-table th {
  text-align: left;
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--d-muted);
  padding: 0.75rem 1rem;
  background: rgba(255, 255, 255, 0.03);
}

.admin-table td {
  padding: 0.9rem 1rem;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  vertical-align: top;
}

.admin-table tr:hover td {
  background: rgba(122, 211, 255, 0.04);
}

.admin-amount {
  font-weight: 700;
  color: #fff;
}

.admin-status {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.24rem 0.62rem;
  border-radius: 999px;
  border: 1px solid;
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 700;
  white-space: nowrap;
}

.admin-status::before {
  content: '';
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

.admin-status.ok {
  color: var(--d-ok);
  border-color: rgba(85, 209, 153, 0.35);
  background: rgba(85, 209, 153, 0.12);
}

.admin-status.wait {
  color: var(--d-wait);
  border-color: rgba(255, 158, 90, 0.35);
  background: rgba(255, 158, 90, 0.12);
}

.admin-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.48rem;
}

.admin-action-form {
  display: inline-flex;
}

.admin-action-btn {
  border: 1px solid rgba(232, 190, 88, 0.48);
  background: rgba(232, 190, 88, 0.18);
  color: #ffecc0;
  padding: 0.42rem 0.72rem;
  border-radius: 9px;
  font-weight: 700;
  font-size: 0.75rem;
  cursor: pointer;
  transition: transform .16s ease, filter .16s ease;
}

.admin-action-btn:hover {
  transform: translateY(-1px);
  filter: brightness(1.08);
}

.admin-action-btn.alt {
  border-color: rgba(122, 211, 255, 0.45);
  background: rgba(122, 211, 255, 0.16);
  color: #dff6ff;
}

.admin-action-btn.danger {
  border-color: rgba(243, 125, 125, 0.45);
  background: rgba(243, 125, 125, 0.18);
  color: #ffdede;
}

.admin-empty {
  padding: 1.1rem 1rem;
  color: var(--d-muted);
}

.prospector-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0.7rem;
  padding: 1rem 1.2rem;
  border-bottom: 1px solid var(--d-line);
}

.prospector-grid input,
.prospector-grid select {
  width: 100%;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid var(--d-line);
  color: var(--d-text);
  border-radius: 10px;
  padding: 0.58rem 0.7rem;
  outline: none;
}

.prospector-grid input::placeholder {
  color: #91aac0;
}

.prospector-actions {
  display: flex;
  gap: 0.55rem;
  align-items: center;
}

.prospector-results {
  padding: 1rem 1.2rem 1.2rem;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.8rem;
}

.prospector-card {
  border: 1px solid var(--d-line);
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.03);
  padding: 0.85rem;
}

.prospector-card h3 {
  margin: 0;
  font-size: 0.95rem;
}

.prospector-card p {
  margin: 0.35rem 0 0;
  color: var(--d-muted);
  font-size: 0.84rem;
}

.prospector-card a {
  color: #f6d48b;
  text-decoration: none;
  word-break: break-all;
}

.prospector-meta {
  margin-top: 0.5rem;
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
}

.prospector-tag {
  border-radius: 999px;
  border: 1px solid rgba(122, 211, 255, 0.4);
  background: rgba(122, 211, 255, 0.12);
  color: #d7f4ff;
  padding: 0.18rem 0.48rem;
  font-size: 0.7rem;
  font-weight: 700;
}

.prospector-empty {
  grid-column: 1 / -1;
  color: var(--d-muted);
  border: 1px dashed var(--d-line);
  border-radius: 12px;
  padding: 0.9rem;
}

@media (max-width: 1020px) {
  .admin-kpis {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .prospector-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .prospector-results {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 680px) {
  .admin-shell {
    margin-top: 1rem;
  }

  .admin-hero {
    padding: 1.2rem;
  }

  .admin-kpis {
    grid-template-columns: 1fr;
  }

  .prospector-grid {
    grid-template-columns: 1fr;
  }

  .prospector-actions {
    flex-wrap: wrap;
  }
}
</style>

<div class="admin-shell">
  <section class="admin-hero">
    <h1>Dashboard Administration Sazulis</h1>
    <p>Centre de pilotage des ventes, signatures et paiements. Tu peux valider ou retirer un statut en un clic, avec impact direct sur les documents clients.</p>
    <div class="admin-status-strip">
      <span class="admin-pill is-ok">Workflow PDF actif</span>
      <span class="admin-pill is-ok">Signatures en ligne actives</span>
      <span class="admin-pill is-wait"><?= $pendingSignatures ?> signature(s) en attente</span>
    </div>
  </section>

  <section class="admin-kpis">
    <article class="admin-kpi">
      <span>Produits actifs</span>
      <strong><?= count($products) ?></strong>
      <small>Catalogue en ligne</small>
    </article>
    <article class="admin-kpi">
      <span>Clients inscrits</span>
      <strong><?= $totalUsers ?></strong>
      <small>Comptes utilisateurs</small>
    </article>
    <article class="admin-kpi">
      <span>Commandes</span>
      <strong><?= $totalOrders ?></strong>
      <small>Historique global</small>
    </article>
    <article class="admin-kpi">
      <span>CA encaissé</span>
      <strong><?= number_format($totalRevenue, 2, ',', ' ') ?> EUR</strong>
      <small>Statut paid/completed</small>
    </article>
    <article class="admin-kpi">
      <span>Stock total</span>
      <strong><?= $totalStock ?></strong>
      <small>Unités disponibles</small>
    </article>
    <article class="admin-kpi">
      <span>Acomptes validés</span>
      <strong><?= $validatedAcompte ?></strong>
      <small>Contrats sécurisés</small>
    </article>
    <article class="admin-kpi">
      <span>Soldes réglés</span>
      <strong><?= $validatedSolde ?></strong>
      <small>Commandes finalisées</small>
    </article>
    <article class="admin-kpi">
      <span>Plateforme</span>
      <strong>Operational</strong>
      <small>Monitoring live</small>
    </article>
  </section>

  <section class="admin-grid">
    <article class="admin-panel">
      <header class="admin-panel-head">
        <h2>Prospection IA</h2>
        <span>Recherche de sites a optimiser</span>
      </header>
      <div class="prospector-grid">
        <input id="prospector-sector" type="text" placeholder="Secteur (ex: restaurant, coiffeur)">
        <input id="prospector-city" type="text" placeholder="Ville / Zone (ex: Nancy)">
        <select id="prospector-limit">
          <option value="8">8 leads</option>
          <option value="12" selected>12 leads</option>
          <option value="20">20 leads</option>
        </select>
        <div class="prospector-actions">
          <button id="prospector-run" type="button" class="admin-action-btn alt">Lancer</button>
          <a href="<?= htmlspecialchars($scraperPage, ENT_QUOTES, 'UTF-8') ?>" class="admin-action-btn">Mode complet</a>
        </div>
      </div>
      <div id="prospector-results" class="prospector-results">
        <div class="prospector-empty">Aucun resultat pour l'instant. Lance une recherche pour recuperer des prospects.</div>
      </div>
    </article>

    <article class="admin-panel">
      <header class="admin-panel-head">
        <h2>Produits</h2>
        <span>Vue rapide du catalogue</span>
      </header>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
          <tr>
            <th>Produit</th>
            <th>Prix</th>
            <th>Stock</th>
            <th>Slug</th>
          </tr>
          </thead>
          <tbody>
          <?php if (empty($products)): ?>
            <tr><td class="admin-empty" colspan="4">Aucun produit trouvé.</td></tr>
          <?php else: ?>
            <?php foreach ($products as $product): ?>
              <tr>
                <td><?= htmlspecialchars((string) ($product['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td class="admin-amount"><?= number_format((float) ($product['price'] ?? 0), 2, ',', ' ') ?> EUR</td>
                <td><?= (int) ($product['stock'] ?? 0) ?></td>
                <td><?= htmlspecialchars((string) ($product['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </article>

    <article class="admin-panel">
      <header class="admin-panel-head">
        <h2>Commandes Clients</h2>
        <span>Validation acompte, solde et suivi des signatures</span>
      </header>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
          <tr>
            <th>Référence</th>
            <th>Client</th>
            <th>Total</th>
            <th>Signature</th>
            <th>Acompte</th>
            <th>Solde</th>
            <th>Actions</th>
          </tr>
          </thead>
          <tbody>
          <?php if (empty($orders)): ?>
            <tr>
              <td class="admin-empty" colspan="7">Aucune commande disponible.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($orders as $order): ?>
              <?php
              $orderId = (int) ($order['id'] ?? 0);
              $acompte = (int) ($order['acompte_paye'] ?? 0) === 1;
              $solde = (int) ($order['solde_regle'] ?? 0) === 1;
              $hasSignature = !empty($order['client_signature_path']);
              ?>
              <tr>
                <td><?= htmlspecialchars((string) ($order['order_ref'] ?? 'CMD-' . $orderId), ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                  <?= htmlspecialchars((string) ($order['customer_name'] ?? 'Client'), ENT_QUOTES, 'UTF-8') ?><br>
                  <small style="color:#b3c7d9;"><?= htmlspecialchars((string) ($order['customer_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                </td>
                <td class="admin-amount"><?= number_format((float) ($order['total'] ?? 0), 2, ',', ' ') ?> EUR</td>
                <td>
                  <span class="admin-status <?= $hasSignature ? 'ok' : 'wait' ?>">
                    <?= $hasSignature ? 'Signée' : 'En attente' ?>
                  </span>
                </td>
                <td>
                  <span class="admin-status <?= $acompte ? 'ok' : 'wait' ?>">
                    <?= $acompte ? 'Validé' : 'En attente' ?>
                  </span>
                </td>
                <td>
                  <span class="admin-status <?= $solde ? 'ok' : 'wait' ?>">
                    <?= $solde ? 'Validé' : 'En attente' ?>
                  </span>
                </td>
                <td>
                  <div class="admin-actions">
                    <?php if (!$acompte): ?>
                      <form class="admin-action-form" method="post" action="<?= htmlspecialchars((string) ($basePath ?? ''), ENT_QUOTES, 'UTF-8') ?>/admin/orders/<?= $orderId ?>/validate/acompte">
                        <button type="submit" class="admin-action-btn">Valider acompte</button>
                      </form>
                    <?php else: ?>
                      <form class="admin-action-form" method="post" action="<?= htmlspecialchars((string) ($basePath ?? ''), ENT_QUOTES, 'UTF-8') ?>/admin/orders/<?= $orderId ?>/validate/acompte">
                        <input type="hidden" name="state" value="0">
                        <button type="submit" class="admin-action-btn danger">Retirer acompte</button>
                      </form>
                    <?php endif; ?>

                    <?php if (!$solde): ?>
                      <form class="admin-action-form" method="post" action="<?= htmlspecialchars((string) ($basePath ?? ''), ENT_QUOTES, 'UTF-8') ?>/admin/orders/<?= $orderId ?>/validate/solde">
                        <button type="submit" class="admin-action-btn alt">Valider solde</button>
                      </form>
                    <?php else: ?>
                      <form class="admin-action-form" method="post" action="<?= htmlspecialchars((string) ($basePath ?? ''), ENT_QUOTES, 'UTF-8') ?>/admin/orders/<?= $orderId ?>/validate/solde">
                        <input type="hidden" name="state" value="0">
                        <button type="submit" class="admin-action-btn danger">Retirer solde</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </article>
  </section>
</div>

<script>
(function () {
  const btn = document.getElementById('prospector-run');
  const sectorEl = document.getElementById('prospector-sector');
  const cityEl = document.getElementById('prospector-city');
  const limitEl = document.getElementById('prospector-limit');
  const box = document.getElementById('prospector-results');
  const endpoint = <?= json_encode($proxyEndpoint, JSON_UNESCAPED_SLASHES) ?>;

  if (!btn || !sectorEl || !cityEl || !limitEl || !box) {
    return;
  }

  const renderCards = (items) => {
    if (!Array.isArray(items) || items.length === 0) {
      box.innerHTML = '<div class="prospector-empty">Aucun site obsolet detecte pour ce filtre.</div>';
      return;
    }

    box.innerHTML = items.map((it) => {
      const emails = Array.isArray(it.emails) ? it.emails.join(', ') : '';
      const safeName = (it.nom || 'Prospect').replace(/</g, '&lt;').replace(/>/g, '&gt;');
      const safeUrl = (it.url || '').replace(/"/g, '&quot;');
      const score = Number(it.score || 0);
      return '<article class="prospector-card">'
        + '<h3>' + safeName + '</h3>'
        + '<p><a href="' + safeUrl + '" target="_blank" rel="noopener">' + safeUrl + '</a></p>'
        + '<p>' + (emails || 'Email non detecte') + '</p>'
        + '<div class="prospector-meta">'
        + '<span class="prospector-tag">Score ' + score + '/100</span>'
        + '<span class="prospector-tag">' + (it.plateforme || 'Inconnu') + '</span>'
        + '</div>'
        + '</article>';
    }).join('');
  };

  btn.addEventListener('click', async () => {
    const sector = sectorEl.value.trim();
    const city = cityEl.value.trim();
    const limit = limitEl.value;
    if (!sector) {
      alert('Indique au moins un secteur.');
      return;
    }

    box.innerHTML = '<div class="prospector-empty">Recherche en cours...</div>';

    try {
      const data = new FormData();
      data.append('action', 'scraper_auto');
      data.append('secteur', sector);
      data.append('ville', city);
      data.append('limit', limit);

      const res = await fetch(endpoint, { method: 'POST', body: data });
      const json = await res.json();
      if (json.error) {
        box.innerHTML = '<div class="prospector-empty">' + json.error + '</div>';
        return;
      }
      renderCards(json.results || []);
    } catch (e) {
      box.innerHTML = '<div class="prospector-empty">Erreur reseau lors de la prospection.</div>';
    }
  });
})();
</script>
