<?php
require_once __DIR__ . '/../protect.php';
require_once __DIR__ . '/../security_headers.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php
    // 1. Mots-clés et balises uniques pour la page Paiement Sécurisé & Confidentialité
    $page_title = "Paiement Sécurisé & Confidentialité des Projets | Sazulis";
    $page_description = "Découvrez le cadre de sécurité technique de Sazulis : transactions cryptées par HTTPS, respect du RGPD, confidentialité stricte de vos projets web et moyens de paiement.";

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
        .paiement-section {
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
        .paiement-section h1 {
            font-size: 2.2em;
            color: #1a2347;
            font-family: 'Montserrat',sans-serif;
            font-weight: 900;
            margin-bottom: 1.2em;
            text-align: center;
        }
        .paiement-section h2 {
            color: #c8902e;
            font-size: 1.2em;
            margin-top: 1.5em;
            margin-bottom: 0.5em;
            font-weight: 700;
        }
        .paiement-section p, .paiement-section li {
            color: #222;
            font-size: 1.08em;
        }
        .paiement-section ul {
            margin: 0 0 0 1.2em;
            padding: 0;
            color: #333;
            font-size: 1.1em;
        }
        .paiement-section li {
            margin-bottom: 0.4em;
        }
        .paiement-section strong {
            color: #1a2347;
        }
        @media (max-width: 900px) {
            .paiement-section {
                padding: 1.2em 0.5em;
            }
            .paiement-section h1 {
                font-size: 1.3em;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../navbar.php'; ?>
    <main>
        <section class="paiement-section">
            <h1>Paiement sécurisé</h1>
            <p>Les paiements effectués sur ce site sont entièrement sécurisés. Les transactions sont traitées via des prestataires de paiement reconnus, respectant les normes de sécurité en vigueur.<br>Aucune donnée bancaire n’est stockée sur le site. L’ensemble des échanges est chiffré afin de garantir la confidentialité et l’intégrité des informations.</p>

            <h2>Moyens de paiement</h2>
            <p>Les paiements peuvent être réalisés par les moyens suivants :</p>
            <ul>
                <li>Carte bancaire via une plateforme de paiement sécurisée,</li>
                <li>Solutions de paiement en ligne reconnues,</li>
                <li>Virement bancaire (selon les prestations).</li>
            </ul>
            <p>Les modalités exactes de paiement sont précisées lors de la validation du devis ou de la commande de service.</p>

            <h2>Protection des données personnelles</h2>
            <p>Les données personnelles collectées sont strictly nécessaires à la gestion des demandes, des devis et des prestations.<br>Elles sont traitées de manière confidentielle et ne sont en aucun cas cédées ou vendues à des tiers.<br>Le site est conforme aux exigences du Règlement Général sur la Protection des Données (RGPD).</p>

            <h2>Confidentialité des projets</h2>
            <p>L’ensemble des informations, documents et accès transmis dans le cadre d’un projet sont considérés comme strictement confidentiels.<br>Aucune donnée liée aux projets clients n’est utilisée à des fins commerciales ou de communication sans accord préalable.</p>

            <h2>Sécurité technique</h2>
            <p>Le site bénéficie de mesures de sécurité techniques visant à protéger les données et les échanges, notamment :</p>
            <ul>
                <li>Protocole HTTPS,</li>
                <li>Mises à jour régulières,</li>
                <li>Protection contre les accès non autorisés.</li>
            </ul>
            <p>Malgré toutes les précautions mises en place, l’éditeur ne saurait être tenu responsable d’éventuelles failles liées à des causes extérieures.</p>

            <h2>Contact</h2>
            <p>Pour toute question relative à la sécurité des paiements ou à la protection des données, vous pouvez contacter l’éditeur via la page <a href="contact.php">Contact</a>.</p>
        </section>
    </main>
    <?php include __DIR__ . '/../footer.php'; ?>
</body>
</html>