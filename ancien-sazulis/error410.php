<?php
http_response_code(410);
require_once __DIR__ . '/bootstrap.php';
?>

<main style="min-height:60vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2em 0;">
	<div style="text-align:center;">
		<img src="/assets/img/sazulis-logo1.png" alt="Sazulis" style="width:300px;margin-bottom:1em;" />
		<h1 style="font-size:2.5em;color:#d2691e;margin-bottom:0.5em;">error410 - Page supprimée</h1>
		<p style="font-size:1.2em;color:#333;max-width:500px;margin:auto;">
			Désolé, cette page n'est plus disponible.<br>
			Elle a été définitivement supprimée ou déplacée.
		</p>
		<a href="/index.php" style="display:inline-block;margin-top:2em;padding:0.7em 2em;background:#d2691e;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;">Retour à l'accueil</a>
	</div>
</main>

