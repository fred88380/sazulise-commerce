<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galerie protégée</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <h1>Galerie protégée</h1>
        <a href="logout.php">Déconnexion</a>
    </header>
    <main>
        <section>
            <h2>Photos</h2>
            <p>Voici la galerie réservée aux membres connectés.</p>
            <!-- Ajoutez ici vos images -->
        </section>
    </main>
</body>
</html>
