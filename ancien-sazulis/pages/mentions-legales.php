<?php
require_once __DIR__ . '/../protect.php';
include '../navbar.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php
    // 1. Mots-clés et balises uniques pour les Mentions Légales (SEO & Conformité)
    $page_title = "Mentions Légales | Sazulis - Développeur Web Épinal";
    $page_description = "Consultez les mentions légales du site Sazulis.fr. Informations juridiques sur l'entreprise de développement web et SEO à Arches / Épinal.";

    // 2. Inclusion de ton head dynamique
    include __DIR__ . '/../head.php';
    ?>
<style>
    body {
        background: url('../assets/img/unique.png') center/cover no-repeat fixed;
        margin: 0;
        font-family: 'Segoe UI', Arial, sans-serif;
    }
    main {
        background: transparent !important;
        box-shadow: none !important;
        border-radius: 0;
        margin-bottom: 2em;
    }
    .mentions-section {
        background: rgba(255,255,255,0.97);
        border-radius: 22px;
        box-shadow: 0 4px 32px #0001, 0 1.5px 8px #ffd70022;
        max-width: 800px;
        width: 100%;
        margin: 2.5em auto 2em auto;
        padding: 2.5em 2em 2em 2em;
        display: flex;
        flex-direction: column;
        align-items: stretch;
        box-sizing: border-box;
    }
    .mentions-section h1 {
        font-size: 2.2em;
        color: #1a2347;
        font-family: 'Montserrat',sans-serif;
        font-weight: 900;
        margin-bottom: 1.2em;
        text-align: center;
    }
    .mentions-section h2 {
        color: #c8902e;
        font-size: 1.2em;
        margin-top: 1.5em;
        margin-bottom: 0.5em;
        font-weight: 700;
    }
    .mentions-section ul {
        margin: 0 0 0 1.2em;
        padding: 0;
        color: #333;
        font-size: 1.1em;
    }
    .mentions-section li {
        margin-bottom: 0.4em;
    }
    .mentions-section p, .mentions-section li {
        color: #222;
        font-size: 1.08em;
    }
    .mentions-section strong {
        color: #1a2347;
    }
    @media (max-width: 900px) {
        .mentions-section {
            padding: 1.2em 0.5em;
        }
        .mentions-section h1 {
            font-size: 1.3em;
        }
    }
</style>
</head>
<body>
    <main>
        <section class="mentions-section">
            <h1>Mentions légales</h1>
            
            <h2>Éditeur du site</h2>
            <p><strong>Nom / Raison sociale :</strong> Société <span style="color:#c8902e;font-weight:bold;">Sazulis</span><br>
            <strong>Statut juridique :</strong> Auto-entrepreneur<br>
            <strong>Adresse :</strong> 1 Résidence les fallières 88380 ARCHES<br>
            <strong>Email :</strong> <a href="mailto:sazulis@outlook.fr">sazulis@outlook.fr</a><br>
            <strong>Téléphone :</strong> 06 79 71 93 84<br>
            <strong>SIRET :</strong> 75262804000020<br>
            <strong>RCS / RM :</strong> Épinal</p>

            <h2>Directeur de la publication</h2>
            <p>Le directeur de la publication est Mr SEMOL Frédéric, en sa qualité de responsable et directeur de la société <span style="color:#c8902e;font-weight:bold;">Sazulis</span>.</p>

            <h2>Hébergement</h2>
            <p>Ce site internet est hébergé par la société <strong>WEBSTRATOR</strong>.<br>
            Adresse : 1 Boulevard de l'Anguison, 54000 Nancy, France<br>
            Site web : <a href="https://webstrator.com" target="_blank" rel="noopener">www.webstrator.com</a></p>

            <h2>Activité</h2>
            <p>Le site a pour objet la présentation et la vente de services de développement web, incluant notamment :</p>
            <ul>
                <li>création de sites internet,</li>
                <li>développement sur mesure,</li>
                <li>maintenance et assistance technique,</li>
                <li>prestations liées au web et au digital.</li>
            </ul>

            <h2>Propriété intellectuelle</h2>
            <p>L’ensemble du contenu présent sur ce site (textes, images, graphismes, logos, icônes, code, structure) est protégé par le droit de la propriété intellectuelle.<br>Toute reproduction, représentation, modification ou adaptation, totale ou partielle, sans autorisation écrite préalable est strictement interdite.</p>

            <h2>Responsabilité</h2>
            <p>L