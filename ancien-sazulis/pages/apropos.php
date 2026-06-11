<?php
require_once __DIR__ . '/../protect.php';
include '../navbar.php';
?>
<!DOCTYPE html>
<html lang="fr">
<?php 

// 1. On définit les mots-clés uniques pour la page À propos avant d'appeler le head
$page_title = "À Propos de Sazulis | Développeur Web & Expert SEO à Épinal";
$page_description = "Découvrez Sazulis, développeur web freelance basé à Épinal (Vosges). Passionné par la création de sites internet performants, d'applications sur mesure et l'optimisation SEO.";

// 2. On inclut le head (qui va maintenant utiliser ces variables)
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
    .apropos-section {
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
    .apropos-section h1 {
        font-size: 2.2em;
        color: #1a2347;
        font-family: 'Montserrat',sans-serif;
        font-weight: 900;
        margin-bottom: 1.2em;
        text-align: center;
    }
    .apropos-section h2 {
        color: #c8902e;
        font-size: 1.2em;
        margin-top: 1.5em;
        margin-bottom: 0.5em;
        font-weight: 700;
    }
    .apropos-section ul {
        margin: 0 0 0 1.2em;
        padding: 0;
        color: #333;
        font-size: 1.1em;
    }
    .apropos-section li {
        margin-bottom: 0.4em;
    }
    .apropos-section p, .apropos-section li {
        color: #222;
        font-size: 1.08em;
    }
    .apropos-section strong {
        color: #1a2347;
    }
    @media (max-width: 900px) {
        .apropos-section {
            padding: 1.2em 0.5em;
        }
        .apropos-section h1 {
            font-size: 1.3em;
        }
    }
</style>
</head>
<body>
    <main>
        <section class="apropos-section">
            <h1>Qui suis-je&nbsp;?</h1>
            <p>Développeur web passionné, j’accompagne les professionnels, entrepreneurs et entreprises dans la création de solutions digitales performantes et adaptées à leurs besoins.<br><br>Spécialisé dans le développement web, je conçois des sites et applications fiables, optimisés et évolutifs, en mettant l’accent sur la qualité du code, l’expérience utilisateur et les objectifs business.</p>

            <h2>Mon approche</h2>
            <p>Chaque projet est unique. Mon approche repose sur&nbsp;:</p>
            <ul>
                <li>une analyse précise de vos besoins,</li>
                <li>une communication claire et transparente,</li>
                <li>un développement structuré et maintenable,</li>
                <li>le respect des délais et des engagements.</li>
            </ul>
            <p>L’objectif est de livrer des solutions efficaces, durables et simples à prendre en main.</p>

            <h2>Mes services</h2>
            <p>J’interviens sur différents types de prestations, notamment&nbsp;:</p>
            <ul>
                <li>création et refonte de sites internet,</li>
                <li>développement web sur mesure,</li>
                <li>intégration et personnalisation PrestaShop,</li>
                <li>optimisation des performances et du SEO technique,</li>
                <li>maintenance, assistance et évolutions.</li>
            </ul>

            <h2>Pourquoi me faire confiance&nbsp;?</h2>
            <ul>
                <li>Expertise technique et veille constante</li>
                <li>Solutions adaptées à vos objectifs réels</li>
                <li>Code propre, sécurisé et évolutif</li>
                <li>Accompagnement avant, pendant et après le projet</li>
            </ul>
            <p>Chaque collaboration est pensée comme un partenariat, avec une implication totale dans la réussite de votre projet.</p>

            <h2>Travaillons ensemble</h2>
            <p>Vous avez un projet ou une idée à concrétiser&nbsp;?<br><br>N’hésitez pas à me contacter pour échanger et définir ensemble la solution la plus adaptée à vos besoins.</p>
        </section>
    </main>
    <?php include '../footer.php'; ?>
</body>
</html>
