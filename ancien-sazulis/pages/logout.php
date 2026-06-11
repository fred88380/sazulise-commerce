<?php
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier
session_start();
session_unset();
session_destroy();
header('Location: connexion.php');
exit;