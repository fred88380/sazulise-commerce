<?php
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier

require_once __DIR__ . '/../security_headers.php';
?>
<!DOCTYPE html>
<html lang="fr">
<?php 

// 1. Mots-clés et balises uniques pour la conformité et le SEO des CGV
$page_title = "Conditions Générales de Vente (CGV) | Sazulis Épinal";
$page_description = "Prenez connaissance des Conditions Générales de Vente de Sazulis. Conditions de réalisation, tarifs et modalités de paiement pour nos services de développement web et SEO.";

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
        .cgv-section {
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
        .cgv-section h1 {
            font-size: 2.2em;
            color: #1a2347;
            font-family: 'Montserrat',sans-serif;
            font-weight: 900;
            margin-bottom: 1.2em;
            text-align: center;
        }
        .cgv-section h2 {
            color: #c8902e;
            font-size: 1.2em;
            margin-top: 1.5em;
            margin-bottom: 0.5em;
            font-weight: 700;
        }
        .cgv-section p, .cgv-section li {
            color: #222;
            font-size: 1.08em;
        }
        .cgv-section ul {
            margin: 0 0 0 1.2em;
            padding: 0;
            color: #333;
            font-size: 1.1em;
        }
        .cgv-section li {
            margin-bottom: 0.4em;
        }
        .cgv-section strong {
            color: #1a2347;
        }
        @media (max-width: 900px) {
            .cgv-section {
                padding: 1.2em 0.5em;
            }
            .cgv-section h1 {
                font-size: 1.3em;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../navbar.php'; ?>
    <main>
        <section class="cgv-section">
            <h1>Conditions Générales de Vente</h1>
            <p>Les présentes conditions générales de vente (CGV) régissent les relations contractuelles entre la société <span style="color:#d2691e;font-weight:bold;">Sazulis</span> et ses clients. Toute commande implique l'acceptation sans réserve des présentes CGV.</p>

            <h2>Commande</h2>
            <p>Toute commande doit être confirmée par écrit (email, formulaire ou devis signé). La société Sazulis se réserve le droit de refuser une commande en cas de litige ou d'impayé antérieur.</p>

            <h2>Prix</h2>
            <p>Les prix sont indiqués en euros TTC. La société Sazulis se réserve le droit de modifier ses tarifs à tout moment, mais les prestations seront facturées sur la base des tarifs en vigueur au moment de la commande.</p>

            <h2>Paiement</h2>
            <p>Le paiement s'effectue selon les modalités précisées lors de la commande (virement, carte bancaire, etc.). Tout retard de paiement pourra entraîner des pénalités conformément à la législation en vigueur.</p>

            <h2>Livraison / Exécution</h2>
            <p>Les délais de livraison ou d'exécution sont donnés à titre indicatif. Un retard ne saurait justifier l'annulation de la commande ou une demande de dommages et intérêts.</p>

            <h2>Garantie</h2>
            <p>Les prestations bénéficient de la garantie légale contre les vices cachés. Toute réclamation doit être formulée par écrit dans un délai de 7 jours après livraison.</p>

            <h2>Responsabilité</h2>
            <p>La société Sazulis ne saurait être tenue responsable des dommages indirects ou immatériels. Sa responsabilité est limitée au montant de la prestation facturée.</p>

            <h2>Propriété intellectuelle</h2>
            <p>Tous les éléments réalisés restent la propriété de Sazulis jusqu'au paiement intégral. Toute reproduction ou utilisation sans autorisation est interdite.</p>

            <h2>Données personnelles</h2>
            <p>Les données collectées sont utilisées uniquement pour le traitement des commandes et la relation client. Conformément au RGPD, vous disposez d’un droit d’accès, de rectification et de suppression de vos données.</p>

            <h2>Droit applicable</h2>
            <p>Les présentes CGV sont régies par le droit français. En cas de litige, les tribunaux compétents seront ceux du siège social de Sazulis.</p>
        </section>
    </main>
    <?php include __DIR__ . '/../footer.php'; ?>
</body>
</html>
