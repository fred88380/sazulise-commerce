<?php
$basePath    = isset($basePath)   ? (string) $basePath   : '';
$secret      = isset($secret)     ? (string) $secret     : '';
$otpauthUri  = isset($otpauthUri) ? (string) $otpauthUri : '';
$error       = isset($error)      ? (string) $error      : '';
?>

<!-- Modal disclaimer – affiché immédiatement, QR masqué tant que non accepté -->
<div id="twofa-modal-overlay" class="twofa-overlay" role="dialog" aria-modal="true" aria-label="Configuration double authentification">
    <div class="twofa-modal">
        <span class="twofa-modal-icon">🔐</span>
        <h2>Double authentification (2FA)</h2>
        <p class="twofa-modal-intro">Securisez votre compte avec une application d'authentification (Google Authenticator, Aegis, Authy, etc.). A chaque connexion, un code temporaire vous sera demande en plus de votre mot de passe.</p>

        <div class="twofa-modal-disclaimer">
            <strong>Avertissement de responsabilite</strong>
            <p>
                La double authentification est une mesure de securite optionnelle mise a disposition par Sazulis. Meme activee,
                <strong>Sazulis ne saurait etre tenu responsable en cas de piratage, compromission, perte d'acces ou tout autre
                incident lie a votre compte</strong>, qu'il soit du a une faute de l'utilisateur, a la perte de son appareil,
                a la compromission de son application d'authentification ou a tout autre evenement hors du controle de Sazulis.
                En activant ou en ignorant la 2FA, vous acceptez expressement ces conditions.
            </p>
        </div>

        <div class="twofa-modal-actions">
            <button id="twofa-accept" class="btn btn-primary" type="button">J'ai compris — configurer le 2FA</button>
            <form method="post" action="<?= htmlspecialchars($basePath . '/2fa/skip', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="__csrf" value="<?= htmlspecialchars((string) ($csrfToken ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <button class="btn btn-ghost" type="submit">Ignorer pour l'instant</button>
            </form>
        </div>
    </div>
</div>

<!-- Contenu setup (masqué jusqu'à acceptation) -->
<section class="auth-wrap twofa-setup-content" id="twofa-setup-content" style="display:none;">
    <div class="catalog-head">
        <h1>Configurer le 2FA</h1>
        <p>Scannez le QR code avec votre application puis entrez le code a 6 chiffres pour confirmer.</p>
    </div>

    <div class="twofa-steps">
        <div class="twofa-step">
            <span class="twofa-step-num">1</span>
            <div>
                <strong>Installez une application</strong>
                <p>Google Authenticator, Aegis (Android), Authy ou toute app TOTP compatible.</p>
            </div>
        </div>
        <div class="twofa-step">
            <span class="twofa-step-num">2</span>
            <div>
                <strong>Scannez le QR code</strong>
                <p>Ou entrez la cle manuellement si votre appareil ne supporte pas la camera.</p>
            </div>
        </div>
        <div class="twofa-step">
            <span class="twofa-step-num">3</span>
            <div>
                <strong>Entrez le code a 6 chiffres</strong>
                <p>L'app genere un nouveau code toutes les 30 secondes.</p>
            </div>
        </div>
    </div>

    <div class="twofa-qr-block">
        <canvas id="twofa-qr-canvas"></canvas>
        <div class="twofa-secret-block">
            <span class="captcha-label">Cle manuelle</span>
            <code class="twofa-secret-code"><?= htmlspecialchars(chunk_split($secret, 4, ' '), ENT_QUOTES, 'UTF-8') ?></code>
            <button class="btn btn-ghost twofa-copy-btn" type="button" id="twofa-copy-secret" data-secret="<?= htmlspecialchars($secret, ENT_QUOTES, 'UTF-8') ?>">Copier la cle</button>
        </div>
    </div>

    <form class="checkout-form" method="post" action="<?= htmlspecialchars($basePath . '/2fa/confirm', ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="__csrf" value="<?= htmlspecialchars((string) ($csrfToken ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <label>Code de verification (6 chiffres)
            <input type="text" name="totp_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code" placeholder="123456">
        </label>
        <?php if ($error !== ''): ?>
            <p class="feedback" style="color:#ff8d8d;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <button class="btn btn-primary" type="submit">Activer le 2FA</button>
        <form method="post" action="<?= htmlspecialchars($basePath . '/2fa/skip', ENT_QUOTES, 'UTF-8') ?>" style="display:inline;">
            <input type="hidden" name="__csrf" value="<?= htmlspecialchars((string) ($csrfToken ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <button class="btn btn-ghost" type="submit">Ignorer</button>
        </form>
    </form>
</section>

<!-- qrcode.js depuis CDN (rendu 100% cote client, secret ne quitte pas le navigateur) -->
<script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js" defer></script>
<script>
(function () {
    var overlay  = document.getElementById('twofa-modal-overlay');
    var accept   = document.getElementById('twofa-accept');
    var content  = document.getElementById('twofa-setup-content');
    var copyBtn  = document.getElementById('twofa-copy-secret');
    var uri      = <?= json_encode($otpauthUri, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

    if (accept) {
        accept.addEventListener('click', function () {
            overlay.style.display = 'none';
            content.style.display = '';
            // Generer le QR code une fois le contenu visible
            if (typeof QRCode !== 'undefined') {
                QRCode.toCanvas(
                    document.getElementById('twofa-qr-canvas'),
                    uri,
                    { width: 200, margin: 2, color: { dark: '#eff8ff', light: '#071019' } },
                    function (err) { if (err) console.error(err); }
                );
            }
        });
    }

    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            var secret = copyBtn.getAttribute('data-secret');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(secret).then(function () {
                    copyBtn.textContent = 'Copie !';
                    setTimeout(function () { copyBtn.textContent = 'Copier la cle'; }, 2000);
                });
            }
        });
    }
})();
</script>
