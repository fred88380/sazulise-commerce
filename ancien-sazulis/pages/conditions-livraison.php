<?php
session_start();


         include '../navbar.php'; ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <?php
           // 1. Mots-clés et balises uniques pour la page Conditions de livraison
$page_title = "Conditions de Livraison des Services Digitaux | Sazulis";
$page_description = "Découvrez les modalités de livraison de Sazulis (Épinal) : délais maximums de 30 jours, suivi du projet par email et livraison après paiement total.";

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
                    max-width: 800px;
                    margin: 2em auto;
                    padding: 2em;
                    background: #fff;
                    border-radius: 14px;
                    box-shadow: 0 2px 16px #0001;
                }
                .sazulis-color {
                    color: #d2691e;
                    font-weight: bold;
                }
                h1 {
                    text-align: center;
                    margin-bottom: 1.5em;
                    font-size: 2.2em;
                    letter-spacing: 0.01em;
                    font-weight: 800;
                    color: #d2691e;
                }
                h2 {
                    color: #d2691e;
                    margin-top: 1.5em;
                }
                ul {
                    margin-left: 1.2em;
                }
                @media (max-width: 600px) {
                    main {
                        padding: 1em;
                    }
                    h1 {
                        font-size: 1.4em;
                    }
                }
            </style>
        </head>
        <body>
        <main>
            <h1>Conditions de livraison</h1>
            <section>
                <h2>Paiement préalable</h2>
                <p>La livraison des prestations ou fichiers commandés s'effectue uniquement après paiement intégral du montant total ou du solde restant dû.</p>
            </section>
            <section>
                <h2>Délais de livraison</h2>
                <p>Le délai maximum de livraison est de 30 jours à compter de la réception du paiement complet.</p>
            </section>
            <section>
                <h2>Informations importantes</h2>
                <ul>
                    <li>Le client sera informé par email de la disponibilité ou de l'envoi des livrables.</li>
                    <li>En cas de retard indépendant de la volonté de <span class="sazulis-color">Sazulis</span>, le client sera tenu informé dans les meilleurs délais.</li>
                    <li>Aucune livraison ne sera effectuée avant réception du paiement total ou du solde convenu.</li>
                </ul>
            </section>
            <section>
                <h2>Contact</h2>
                <p>Pour toute question concernant la livraison, veuillez contacter <a href="mailto:sazulis@outlook.fr">sazulis@outlook.fr</a>.</p>
            </section>
        </main>
        <?php include '../footer.php'; ?>
        </body>
        </html>
    <style>
