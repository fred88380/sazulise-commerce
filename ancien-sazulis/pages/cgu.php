<?php
// Inclure les headers de sécurité AVANT tout output ou session_start
require_once __DIR__ . '/../security_headers.php';
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<?php 

// 1. Mots-clés et balises uniques pour la conformité et le SEO des CGU
$page_title = "Conditions Générales d'Utilisation (CGU) | Sazulis";
$page_description = "Consultez les Conditions Générales d'Utilisation du site Sazulis.fr, développeur web freelance à Épinal. Cadre légal et règles d'utilisation de nos services.";

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
        .cgu-section {
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
        .cgu-section h1 {
            font-size: 2.2em;
            color: #1a2347;
            font-family: 'Montserrat',sans-serif;
            font-weight: 900;
            margin-bottom: 1.2em;
            text-align: center;
        }
        .cgu-section h2 {
            color: #c8902e;
            font-size: 1.2em;
            margin-top: 1.5em;
            margin-bottom: 0.5em;
            font-weight: 700;
        }
        .cgu-section p, .cgu-section li {
            color: #222;
            font-size: 1.08em;
        }
        .cgu-section ul {
            margin: 0 0 0 1.2em;
            padding: 0;
            color: #333;
            font-size: 1.1em;
        }
        .cgu-section li {
            margin-bottom: 0.4em;
        }
        .cgu-section strong {
            color: #1a2347;
        }
        @media (max-width: 900px) {
            .cgu-section {
                padding: 1.2em 0.5em;
            }
            .cgu-section h1 {
                font-size: 1.3em;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../navbar.php'; ?>
    <main>
        <section class="cgu-section">
            <h1>Conditions Générales d’Utilisation</h1>
            <p>Les présentes CGU définissent les règles d’accès et d’utilisation du site Sazulis, les droits et obligations des utilisateurs, la gestion des comptes, la sécurité, la propriété intellectuelle, la protection des données personnelles, la responsabilité et le droit applicable.</p>

            <h2>1. Accès au site</h2>
            <p>Le site Sazulis est accessible gratuitement à tout utilisateur disposant d’un accès internet. Certains services peuvent nécessiter la création d’un compte ou une identification.</p>

            <h2>2. Utilisation du site</h2>
            <p>L’utilisateur s’engage à utiliser le site de manière loyale, à ne pas porter atteinte à son intégrité, à ne pas tenter d’accéder aux données d’autres utilisateurs ou d’entraver le fonctionnement du site.</p>

            <h2>3. Création de compte</h2>
            <p>La création d’un compte peut être requise pour accéder à certains services. L’utilisateur s’engage à fournir des informations exactes et à les mettre à jour. Les identifiants sont personnels et confidentiels.</p>

            <h2>4. Sécurité</h2>
            <p>L’éditeur met en œuvre les moyens nécessaires pour assurer la sécurité du site et des données. L’utilisateur doit veiller à la confidentialité de ses identifiants et signaler toute utilisation frauduleuse.</p>

            <h2>5. Propriété intellectuelle</h2>
            <p>Tous les contenus présents sur le site (textes, images, logos, code, etc.) sont protégés par le droit de la propriété intellectuelle. Toute reproduction ou utilisation non autorisée est interdite.</p>

            <h2>6. Données personnelles</h2>
            <p>Les données collectées sont utilisées uniquement pour la gestion des services et la relation client. Conformément au RGPD, l’utilisateur dispose d’un droit d’accès, de rectification et de suppression de ses données.</p>

            <h2>7. Responsabilité</h2>
            <p>L’éditeur ne saurait être tenu responsable des dommages directs ou indirects liés à l’utilisation du site, ni des éventuelles interruptions ou erreurs.</p>

            <h2>8. Modification des CGU</h2>
            <p>Les CGU peuvent être modifiées à tout moment. L’utilisateur est invité à les consulter régulièrement.</p>

            <h2>9. Droit applicable</h2>
            <p>Les présentes CGU sont régies par le droit français. En cas de litige, les tribunaux compétents seront ceux du siège social de Sazulis.</p>
        </section>
    </main>
    <?php include __DIR__ . '/../footer.php'; ?>
</body>
</html>