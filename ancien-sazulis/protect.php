<?php
// DEBUG LOCAL : Active l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure les headers de sécurité
require_once __DIR__ . '/security_headers.php';

// Protection anti-robots (User-Agent basique)
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$badBots = ['curl', 'wget', 'python', 'scrapy', 'bot', 'spider', 'crawler', 'httpclient'];
foreach ($badBots as $bot) {
    if (stripos($userAgent, $bot) !== false) {
        http_response_code(403);
        exit('Accès refusé.');
    }
}

// Protection anti-spam simple (limite de requêtes par IP)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!isset($_SESSION['request_count'])) {
    $_SESSION['request_count'] = 0;
    $_SESSION['first_request_time'] = time();
}
if (time() - $_SESSION['first_request_time'] < 60) {
    $_SESSION['request_count']++;
    if ($_SESSION['request_count'] > 30) {
        http_response_code(429);
        exit('Trop de requêtes.');
    }
} else {
    $_SESSION['request_count'] = 1;
    $_SESSION['first_request_time'] = time();
}

// Protection contre l'accès direct à certains fichiers sensibles (optionnel)
// if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
//     http_response_code(403);
//     exit('Accès direct interdit.');
// }

// Autres protections personnalisées ici

// ===============================
// MODÉRATION ANTI-INSULTE/HAINE
// ===============================
function sazulis_moderate_text($text) {
    // Liste noire (à compléter selon besoin)
    $banned_patterns = [
        '/\b(con|connard|connasse|idiot|imbécile|crétin|débile|abruti|enculé|enculée|salaud|salope|putain|pute|merde|chier|couille|bite|pédé|tapette|tantouze|fuck|shit|bitch|bastard|asshole|dick|cunt|whore|slut|fag|faggot|c0n|f\*ck|sh1t|b1tch|a\$\$hole|fdp|fils de pute|enfoiré|enc\*lé|nique ta mère|ntm|ta gueule|ferme ta gueule|va te faire foutre|motherfucker|son of a bitch|piece of shit|go to hell|raciste|nègre|nigger|bougnoule|bicot|raton|youpin|feuj|chintok|niakoué|blédard|rebeu|arabe de merde|sale arabe|sale noir|sale blanc|sale juif|sale musulman|retourne dans ton pays|sale race|sous-race|immigré de merde|parasite immigré|pédale|gouine|lesbienne de merde|sale lesbienne|dyke|queer de merde|gay de merde|homo de merde|brûle en enfer|dégénéré|maladie mentale|contre nature|pédérastie|sodomite|inverti|salope|pute|pétasse|garce|chienne|connasse|gonzesse de merde|pouffiasse|traînée|marie-couche-toi-là|bitch|whore|slut|cunt|hoe|thot|skank|retourne à la cuisine|les femmes sont inférieures|femme objet|good for nothing|make me a sandwich|tu sers à rien sauf baiser|je vais te tuer|tu vas mourir|je vais te buter|je vais te niquer|je vais te défoncer|je sais où tu habites|je vais te retrouver|je vais te faire mal|tu vas souffrir|i will kill you|gonna kill you|you will die|you\'re dead|i\'ll find you|watch your back|you\'re gonna pay|suicide-toi|va te suicider|crève|go kill yourself|kys|hang yourself|jump off a bridge|personne t\'aime|tout le monde te déteste|t\'es pathétique|t\'es minable|t\'es nul|tu vaux rien|déchet humain|sous-merde|tu mérites pas de vivre|waste of space|human trash|vive isis|vive daesh|heil hitler|sieg heil|white power|suprématie blanche|nettoyage ethnique|solution finale|race supérieure|race pure|envoie nudes|send nudes|montre tes seins|suce moi|lèche moi|branle moi|je vais te violer|rape you)\b/i',
    ];
    // Liste blanche (mots autorisés même si partiellement inclus)
    $whitelist = ['basketball','classique','passion','assassin'];
    foreach ($whitelist as $ok) {
        if (stripos($text, $ok) !== false) return true;
    }
    foreach ($banned_patterns as $pattern) {
        if (preg_match($pattern, $text)) {
            return false; // Mot interdit détecté
        }
    }
    return true;
}

// ===============================
// MINI-WAF (détection SQLi/XSS)
// ===============================
function sazulis_waf_check($input) {
    $patterns = [
        '/(union.*select|select.*from|drop table|delete from|insert into|update .* set|or 1=1|--|#|\/\*|\*\/|<script|<iframe|onerror=|onload=|javascript:|eval\(|document\.cookie|alert\()/i',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $input)) {
            return false; // Pattern dangereux détecté
        }
    }
    return true;
}

// ===============================
// EXEMPLE D'UTILISATION POUR UN CHAMP TEXTE OU LABEL
// ===============================
// $label = $_POST['label'] ?? '';
// if (!sazulis_moderate_text($label)) {
//     die('Label interdit (insulte, haine, etc.)');
// }
// if (!sazulis_waf_check($label)) {
//     die('Label dangereux (tentative d\'attaque détectée)');
// }
