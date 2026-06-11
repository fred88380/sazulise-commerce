<?php
$error = isset($error) ? (string) $error : '';
$basePath = isset($basePath) ? (string) $basePath : '';
?>
<section class="auth-wrap">
    <div class="catalog-head">
        <h1>Connexion administration</h1>
        <p>Acces reserve a l'equipe Sazulis.</p>
    </div>

    <form class="checkout-form" method="post" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin/login">
        <label>Email admin
            <input type="email" name="email" required>
        </label>
        <label>Mot de passe admin
            <input type="password" name="password" required>
        </label>
        <button class="btn btn-primary" type="submit">Se connecter en admin</button>
        <?php if ($error !== ''): ?>
            <p class="feedback" style="color:#ff8d8d;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <p><a class="link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/login">Retour connexion client</a></p>
    </form>
</section>
