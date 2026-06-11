<?php $user = isset($user) && is_array($user) ? $user : null; ?>
<section class="checkout-grid">
    <div>
        <h1>Checkout intelligent</h1>
        <p>Paiement rapide, recap live, confirmation instantanee.</p>
        <?php if ($user): ?>
            <p class="tag">Connecte en tant que <?= htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        <?php else: ?>
            <p class="tag">Mode invite actif - compte optionnel</p>
        <?php endif; ?>
        <div id="checkout-lines" class="checkout-lines"></div>
    </div>
    <form id="checkout-form" class="checkout-form">
        <label>Email
            <input type="email" name="email" placeholder="client@email.com" value="<?= htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
        </label>
        <label>Nom complet
            <input type="text" name="name" placeholder="Nom Prenom" value="<?= htmlspecialchars((string) ($user['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
        </label>
        <label>Adresse livraison
            <textarea name="address" rows="3" placeholder="Rue, ville, code postal" required></textarea>
        </label>
        <button class="btn btn-primary" type="submit">Valider la commande</button>
        <p id="checkout-feedback" class="feedback"></p>
    </form>
</section>
