<?php
$basePath = isset($basePath) ? (string) $basePath : '';
$error    = isset($error)    ? (string) $error    : '';
?>
<section class="auth-wrap">
    <div class="catalog-head">
        <h1>Verification 2FA</h1>
        <p>Entrez le code a 6 chiffres genere par votre application d'authentification.</p>
    </div>

    <form class="checkout-form" method="post" action="<?= htmlspecialchars($basePath . '/2fa/verify', ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="__csrf" value="<?= htmlspecialchars((string) ($csrfToken ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <div class="twofa-verify-icon">🔐</div>
        <label>Code a 6 chiffres
            <input
                type="text"
                name="totp_code"
                inputmode="numeric"
                pattern="[0-9]{6}"
                maxlength="6"
                required
                autocomplete="one-time-code"
                placeholder="123456"
                autofocus
            >
        </label>
        <?php if ($error !== ''): ?>
            <p class="feedback" style="color:#ff8d8d;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <button class="btn btn-primary" type="submit">Valider</button>
        <p style="color:var(--muted);font-size:0.85rem;margin-top:8px;">
            Le code change toutes les 30 secondes. Si le code est refuse, verifie l'heure de ton telephone.
        </p>
    </form>
</section>
