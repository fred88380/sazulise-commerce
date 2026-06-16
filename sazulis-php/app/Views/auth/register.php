<?php
$error = isset($error) ? (string) $error : '';
$basePath = isset($basePath) ? (string) $basePath : '';
$captchaImageUrl = isset($captchaImageUrl) ? (string) $captchaImageUrl : '/captcha/auth';
$captchaImageSrc = htmlspecialchars($basePath . $captchaImageUrl, ENT_QUOTES, 'UTF-8');
?>
<section class="auth-wrap">
    <div class="catalog-head">
        <h1>Creer un compte client</h1>
        <p>Inscription rapide pour centraliser historique et commandes.</p>
    </div>

    <form class="checkout-form" method="post" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/register">
        <input type="hidden" name="__csrf" value="<?= htmlspecialchars((string) ($csrfToken ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <label>Nom complet
            <input type="text" name="name" required>
        </label>
        <label>Email
            <input type="email" name="email" required>
        </label>
        <label>Mot de passe (8 caracteres min)
            <input type="password" name="password" minlength="8" required>
        </label>
        <div class="captcha-wrap" aria-live="polite">
            <p class="captcha-label">Captcha maison</p>
            <img id="auth-captcha-image" class="captcha-image" src="<?= $captchaImageSrc ?>?t=<?= time() ?>" alt="Captcha">
            <button class="btn btn-ghost captcha-refresh" type="button" id="auth-captcha-refresh">Rafraichir le captcha</button>
            <p class="captcha-help">Recopie exactement le code (majuscules/minuscules/symboles). Exemple: U est different de v ou V.</p>
        </div>
        <label>Code captcha
            <input type="text" name="captcha_code" required autocomplete="off" spellcheck="false">
        </label>
        <button class="btn btn-primary" type="submit">Creer mon compte</button>
        <?php if ($error !== ''): ?>
            <p class="feedback" style="color:#ff8d8d;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <p>Deja inscrit ? <a class="link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/login">Se connecter</a></p>
    </form>
</section>

<script>
(function () {
    var img = document.getElementById('auth-captcha-image');
    var btn = document.getElementById('auth-captcha-refresh');
    if (!img || !btn) {
        return;
    }
    btn.addEventListener('click', function () {
        var base = img.getAttribute('src').split('?')[0];
        img.setAttribute('src', base + '?refresh=1&t=' + Date.now());
    });
})();
</script>
