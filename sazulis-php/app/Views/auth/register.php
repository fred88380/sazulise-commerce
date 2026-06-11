<?php
$error = isset($error) ? (string) $error : '';
$basePath = isset($basePath) ? (string) $basePath : '';
?>
<section class="auth-wrap">
    <div class="catalog-head">
        <h1>Creer un compte client</h1>
        <p>Inscription rapide pour centraliser historique et commandes.</p>
    </div>

    <form class="checkout-form" method="post" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/register">
        <label>Nom complet
            <input type="text" name="name" required>
        </label>
        <label>Email
            <input type="email" name="email" required>
        </label>
        <label>Mot de passe (8 caracteres min)
            <input type="password" name="password" minlength="8" required>
        </label>
        <button class="btn btn-primary" type="submit">Creer mon compte</button>
        <?php if ($error !== ''): ?>
            <p class="feedback" style="color:#ff8d8d;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <p>Deja inscrit ? <a class="link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/login">Se connecter</a></p>
    </form>
</section>
