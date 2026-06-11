<?php
$products = isset($products) && is_array($products) ? $products : [];
$totalStock = isset($totalStock) ? (int) $totalStock : 0;
?>
<section class="admin-head">
    <h1>Backoffice Sazulis</h1>
    <p>Pilotage rapide des produits et du stock.</p>
</section>

<section class="stats-row">
    <article>
        <h3>Produits actifs</h3>
        <strong><?= count($products) ?></strong>
    </article>
    <article>
        <h3>Stock total</h3>
        <strong><?= (int) $totalStock ?></strong>
    </article>
    <article>
        <h3>Etat plateforme</h3>
        <strong>Operational</strong>
    </article>
</section>

<section class="table-wrap">
    <table>
        <thead>
        <tr>
            <th>Produit</th>
            <th>Prix</th>
            <th>Stock</th>
            <th>Slug</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $product): ?>
            <tr>
                <td><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= number_format((float) $product['price'], 2, ',', ' ') ?> EUR</td>
                <td><?= (int) $product['stock'] ?></td>
                <td><?= htmlspecialchars($product['slug'], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
