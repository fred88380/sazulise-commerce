<?php
session_start();
include '../navbar.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php
    // 1. Mots-clés et balises uniques pour la page Contact (SEO Local)
    $page_title = "Contactez Sazulis | Développeur Web & SEO à Épinal (Arches)";
    $page_description = "Un projet de site internet ou un besoin d'optimisation SEO dans les Vosges ? Contactez Sazulis pour un devis gratuit. Réponse rapide garantie.";

    // 2. Inclusion de ton head dynamique
    include __DIR__ . '/../head.php';
    ?>
    <style>
        body, html {
            height: 100%;
            margin: 0;
        }
        main.contact-main {
            min-height: 100vh;
            background: url('../assets/img/contact.png') center/cover no-repeat fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 1;
            margin-bottom: -50px;
        }
        .contact-container {
            background: rgba(255,255,255,0.92);
            border-radius: 32px;
            box-shadow: 0 8px 32px #ffd70055, 0 2px 8px #0002;
            padding: 3em 2.5em 2.5em 2.5em;
            max-width: 480px;
            width: 100%;
            margin: 2em 1em;
            display: flex;
            flex-direction: column;
            align-items: center;
            border: 2px solid #ffe9c6;
        }
        .contact-container h1 {
            font-size: 2.2em;
            color: #d4af37;
            margin-bottom: 0.5em;
            font-family: 'Montserrat',sans-serif;
            letter-spacing: 1px;
        }
        .contact-container p {
            color: #444;
            font-size: 1.1em;
            margin-bottom: 1.5em;
            text-align: center;
        }
        .contact-form {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 1.2em;
        }
        .contact-form input, .contact-form textarea {
            border-radius: 18px;
            border: 1.5px solid #ffd70099;
            padding: 0.8em 1.2em;
            font-size: 1.05em;
            font-family: inherit;
            background: #fffbe6;
            box-shadow: 0 1px 4px #ffd70022;
            outline: none;
            transition: border 0.2s;
        }
        .contact-form input:focus, .contact-form textarea:focus {
            border: 2px solid #d4af37;
        }
        .contact-form textarea {
            min-height: 110px;
            resize: vertical;
        }
        .contact-form button {
            background: linear-gradient(90deg, #ffe9c6, #fffbe6 80%, #ffd700);
            color: #333;
            border: none;
            border-radius: 24px;
            padding: 0.7em 2.2em;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 1px 8px #ffd70044;
            transition: background 0.2s, transform 0.15s;
        }
        .contact-form button:hover {
            background: linear-gradient(90deg, #ffd700, #fffbe6 80%, #ffe9c6);
            transform: translateY(-2px) scale(1.04);
        }
        .contact-info {
            margin-top: 2em;
            text-align: center;
            color: #888;
            font-size: 1em;
        }
        @media (max-width: 600px) {
            .contact-container {
                padding: 1.5em 0.5em;
            }
            .contact-container h1 {
                font-size: 1.3em;
            }
        }
    </style>
</head>
<body>
    <main class="contact-main">
        <div class="contact-container">
            <h1>Contact</h1>
            <p>Une question, un projet, un besoin urgent ?<br>Remplissez le formulaire ci-dessous, notre équipe vous répondra dans les plus brefs délais.</p>
            
            <form class="contact-form" method="post" action="#">
                <input type="text" name="nom" placeholder="Votre nom" required>
                <input type="email" name="email" placeholder="Votre email" required>
                <textarea name="message" placeholder="Votre message" required></textarea>
                <button type="submit">Envoyer</button>
            </form>
            
            <div class="contact-info">
                <div><strong>Email&nbsp;:</strong> contact@sazulis.fr</div>
                <div><strong>Téléphone&nbsp;:</strong> 06 98 76 67 80</div>
                <div><strong>Adresse&nbsp;:</strong> 1 Résidence les fallières, 88380 ARCHES</div>
            </div>
        </div>
    </main>
    <?php include '../footer.php'; ?>
</body>
</html>