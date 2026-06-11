<?php
/**
 * Dashboard Admin - Sazulis CRM & Scraper Ultra-Ciblé
 * Version: 4.0 (Chasseur de sites obsolètes Réaliste)
 */

require_once __DIR__ . '/vendor/autoload.php';
use GuzzleHttp\Client;

session_start();

// --- CONFIGURATION ---
$clientsFolder = __DIR__ . '/clients/';
if (!is_dir($clientsFolder)) {
    mkdir($clientsFolder, 0777, true);
}

/**
 * Analyse poussée pour détecter la vraie vétusté et éliminer les sites modernes.
 */
function evaluerVetusteSite(string $html, string $url): array {
    $score = 0;
    $anomalies = [];

    // 1. Détection du Responsive Design
    if (stripos($html, 'name="viewport"') === false && stripos($html, "name='viewport'") === false) {
        $score += 40;
        $anomalies[] = "Non adapté aux mobiles (Pas de balise viewport)";
    }

    // 2. Absence totale de sécurité moderne (HTTPS / SSL)
    if (strpos($url, 'https://') === false && substr_count($html, 'https://') < 3) {
        $score += 20;
        $anomalies[] = "Sécurité obsolète (Tout en HTTP ou manque de HTTPS)";
    }

    // 3. Mentions légales / Copyright figé dans le passé
    if (preg_match('/©\s*(200[0-9]|201[0-8])/', $html, $matches)) {
        $score += 25;
        $anomalies[] = "Copyright obsolète détecté (" . $matches[1] . ")";
    }

    // 4. Technologies et balises préhistoriques
    if (stripos($html, '<frameset>') !== false || stripos($html, '<frame ') !== false) {
        $score += 25;
        $anomalies[] = "Utilisation de Frames/Cadres (Années 2000)";
    }
    
    // Détection des layouts en Tableaux (exclusif aux vieux sites)
    if (substr_count($html, '<table') > 10 && substr_count($html, '<div') < 20) {
        $score += 20;
        $anomalies[] = "Structure archaïque par tableaux <table>";
    }

    // 5. Utilisation de librairies ultra-vieilles (ex: jQuery v1.x)
    if (preg_match('/jquery[-.]1\.[0-9]/i', $html)) {
        $score += 15;
        $anomalies[] = "Version de jQuery obsolète et vulnérable";
    }

    // Anti-Faux Positif : Si le site utilise des technologies modernes, on baisse drastiquement le score
    if (stripos($html, 'wp-content') !== false || stripos($html, 'next/script') !== false || stripos($html, '_nuxt/') !== false) {
        $score = max(0, $score - 40); 
    }

    if ($score > 100) $score = 100;

    $priorite = "Basse";
    if ($score >= 60) $priorite = "CRITIQUE (À refondre d'urgence)";
    elseif ($score >= 40) $priorite = "Moyenne (Modernisation nécessaire)";

    return [
        'score' => $score,
        'anomalies' => $anomalies,
        'priorite' => $priorite,
        'critique' => ($score >= 40) 
    ];
}

// --- LOGIQUE DE RECHERCHE & SCRAPING (API) ---
if (isset($_POST['action']) && $_POST['action'] === 'rechercher_entreprises') {
    header('Content-Type: application/json');
    
    $secteur = trim($_POST['secteur'] ?? '');
    $ville   = trim($_POST['ville'] ?? '');
    $page    = rand(1, 3); 

    if (empty($secteur) || empty($ville)) {
        echo json_encode(['status' => 'error', 'message' => 'Critères manquants.']);
        exit;
    }

    // Appel API Gouvernementale
    $apiUrl = "https://recherche-entreprises.api.gouv.fr/search?q=" . urlencode($secteur) . "&code_postal=" . urlencode($ville) . "&per_page=20&page=" . $page;
    
    $clientGuzzle = new Client([
        'timeout' => 4, 
        'verify' => false,
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]
    ]);
    
    try {
        $response = $clientGuzzle->get($apiUrl);
        $data = json_decode($response->getBody(), true);
        $entreprisesPourries = [];

        if (!empty($data['results'])) {
            foreach ($data['results'] as $ent) {
                $nom = $ent['nom_complet'] ?? 'Entreprise Inconnue';
                $adresse = $ent['siege']['geo_adresse'] ?? 'Adresse non renseignée';
                $siret = $ent['siege']['siret'] ?? '';
                
                // Nettoyage pour tenter de deviner l'URL
                $cleanNom = preg_replace('/(sarl|sas|eurl|sa|ets|et fils|\bco\b)/i', '', $nom);
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $cleanNom));
                
                if (strlen($slug) < 3) continue;
                
                $urlTest = "https://www." . $slug . ".fr";
                $html = "";
                
                // Essai en HTTPS, puis repli en HTTP
                try {
                    $resSite = $clientGuzzle->get($urlTest);
                    $html = (string)$resSite->getBody();
                } catch (\Exception $e) {
                    try {
                        $urlTest = "http://www." . $slug . ".fr";
                        $resSite = $clientGuzzle->get($urlTest);
                        $html = (string)$resSite->getBody();
                    } catch (\Exception $ex) {
                        continue; 
                    }
                }

                if (!empty($html) && strlen($html) > 1000) {
                    // Ignorer les parkings de domaines
                    if (preg_match('/(domaine acheté|ce domaine est à vendre|sedo parking|ovhcloud|site en construction|plesk)/i', $html)) {
                        continue;
                    }
                    
                    $audit = evaluerVetusteSite($html, $urlTest);
                    
                    if ($audit['critique'] === true) {
                        $emailFound = "Inconnu";
                        
                        if (preg_match_all('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}/i', $html, $matches)) {
                            $emailsBruts = array_unique($matches[0]);
                            $emailsValides = [];
                            foreach ($emailsBruts as $email) {
                                $emailLower = strtolower($email);
                                if (preg_match('/\.(png|jpg|jpeg|gif|webp|svg|css|js)$/', $emailLower)) continue;
                                if (strpos($emailLower, 'sentry') !== false || strpos($emailLower, 'wix') !== false || strpos($emailLower, 'abuse') !== false) continue;
                                $emailsValides[] = $email;
                            }
                            if (!empty($emailsValides)) {
                                $emailFound = implode(', ', $emailsValides);
                            }
                        }

                        $entreprisesPourries[] = [
                            'nom' => $nom,
                            'adresse' => $adresse,
                            'site' => $urlTest,
                            'email' => $emailFound,
                            'siret' => $siret,
                            'score_vetuste' => $audit['score'],
                            'priorite' => $audit['priorite'],
                            'anomalies' => $audit['anomalies']
                        ];

                        $log = "Société: $nom\nUrl: $urlTest\nScore Vétusté: " . $audit['score'] . "/100\nEmails: $emailFound\n---\n";
                        file_put_contents($clientsFolder . 'prospection_live.txt', $log, FILE_APPEND);
                    }
                }
            }
        }

        usort($entreprisesPourries, function($a, $b) {
            return $b['score_vetuste'] <=> $a['score_vetuste'];
        });

        echo json_encode(['status' => 'success', 'results' => $entreprisesPourries]);
    } catch (\Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Sazulis | Scraper de Sites Obsolètes v4</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #0a0a0f; color: #e2e8f0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { background: #12121a; border: 1px solid #222230; transition: all 0.3s ease; }
        .card:hover { border-color: #ef4444; transform: translateY(-2px); }
        .btn-danger { background: #dc2626; color: #fff; font-weight: bold; transition: background 0.2s; }
        .btn-danger:hover { background: #b91c1c; }
        .badge-critique { background: rgba(220, 38, 38, 0.15); border: 1px solid rgba(220, 38, 38, 0.4); color: #f87171; }
        .badge-moyen { background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.4); color: #fbbf24; }
    </style>
</head>
<body class="p-8">

    <div class="max-w-7xl mx-auto">
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4 border-b border-gray-800 pb-6">
            <div>
                <h1 class="text-3xl font-extrabold text-red-500 flex items-center gap-2">
                    <i class="fas fa-biohazard text-red-500 animate-pulse"></i> Traqueur de Sites Obsolètes v4
                </h1>
                <p class="text-sm text-gray-400 mt-1">Filtre et extrait uniquement les entreprises possédant un site web d'ancienne génération.</p>
            </div>
            <div class="flex flex-wrap gap-3 w-full md:w-auto">
                <input id="secteur" type="text" placeholder="Métier (Ex: Garage, Menuiserie)" class="bg-gray-900 border border-gray-700 p-2.5 rounded-lg w-full md:w-64 text-white focus:outline-none focus:border-red-500">
                <input id="ville" type="text" placeholder="Département ou CP (Ex: 75)" class="bg-gray-900 border border-gray-700 p-2.5 rounded-lg w-full md:w-40 text-white focus:outline-none focus:border-red-500">
                <button onclick="lancerScrapingCiblé()" class="btn-danger px-6 py-2.5 rounded-lg shadow-lg w-full md:w-auto flex items-center justify-center gap-2">
                    <i class="fas fa-search-dollar"></i> Scanner les Pépites
                </button>
            </div>
        </header>

        <div id="results-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="col-span-full text-center py-20 text-gray-500 bg-gray-900/30 rounded-2xl border border-dashed border-gray-800">
                <i class="fas fa-search text-4xl mb-3 text-gray-600"></i>
                <p>Configurez vos critères ci-dessus pour isoler les sites nécessitant une refonet totale.</p>
            </div>
        </div>
    </div>

    <script>
    async function lancerScrapingCiblé() {
        const secteur = document.getElementById('secteur').value;
        const ville = document.getElementById('ville').value;
        const grid = document.getElementById('results-grid');

        if(!secteur || !ville) {
            alert("Veuillez renseigner un secteur d'activité et un département cible !");
            return;
        }

        grid.innerHTML = `
            <div class="col-span-full text-center py-20 bg-gray-900/20 rounded-2xl border border-gray-800">
                <i class="fas fa-radiation fa-spin text-5xl text-red-500"></i>
                <p class="mt-4 text-white font-medium">Analyse comparative INSEE & Test de charge des codes sources en cours...</p>
                <p class="text-xs text-gray-500 mt-1">Filtres appliqués : anti-responsive, structures obsolètes, liens HTTP bruts.</p>
            </div>
        `;

        const formData = new FormData();
        formData.append('action', 'rechercher_entreprises');
        formData.append('secteur', secteur);
        formData.append('ville', ville);

        try {
            // Appelle le script actuel (scraper.php)
            const response = await fetch('scraper.php', { method: 'POST', body: formData });
            const data = await response.json();

            grid.innerHTML = '';

            if(data.results && data.results.length > 0) {
                data.results.forEach(ent => {
                    let anomaliesHtml = ent.anomalies.map(ano => `
                        <li class="text-xs text-red-400 flex items-center gap-1.5">
                            <i class="fas fa-times-circle text-red-500 text-[10px]"></i> ${ano}
                        </li>
                    `).join('');

                    let badgeClass = ent.score_vetuste >= 60 ? 'badge-critique' : 'badge-moyen';

                    grid.innerHTML += `
                        <div class="card p-6 rounded-xl shadow-2xl relative flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-start mb-3">
                                    <span class="text-xs font-bold px-2.5 py-1 rounded-full ${badgeClass}">
                                        Score d'Obsolescence : ${ent.score_vetuste}/100
                                    </span>
                                </div>
                                <h3 class="text-lg font-bold text-white mb-1 uppercase tracking-tight">${ent.nom}</h3>
                                <p class="text-xs text-gray-400 mb-4"><i class="fas fa-map-marker-alt mr-1 text-gray-600"></i>${ent.adresse}</p>
                                
                                <div class="bg-black/40 p-3 rounded-lg border border-gray-800/60 mb-4">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 block mb-2">Défauts techniques identifiés :</span>
                                    <ul class="space-y-1.5">
                                        ${anomaliesHtml}
                                    </ul>
                                </div>
                            </div>

                            <div class="space-y-2 mt-auto">
                                <div class="flex items-center justify-between bg-gray-950 p-2 rounded border border-gray-800">
                                    <span class="text-[10px] text-gray-500 font-bold uppercase">Lien Cible :</span>
                                    <a href="${ent.site}" target="_blank" class="text-xs font-mono text-blue-400 hover:underline truncate max-w-[180px]">${ent.site}</a>
                                </div>
                                <div class="flex items-center justify-between bg-gray-950 p-2 rounded border border-gray-800">
                                    <span class="text-[10px] text-gray-500 font-bold uppercase">Email Extrait :</span>
                                    <span class="text-xs font-mono text-yellow-500 font-bold truncate max-w-[180px]">${ent.email}</span>
                                </div>
                                <div class="pt-3 flex gap-2 border-t border-gray-800 mt-2">
                                    <a href="mailto:${ent.email.split(',')[0]}?subject=Modernisation de votre site internet" class="flex-1 text-center bg-gray-800 hover:bg-gray-700 text-white py-2 rounded text-xs font-bold transition">
                                        <i class="fas fa-paper-plane mr-1"></i> Démarcher
                                    </a>
                                </div>
                            </div>
                        </div>
                    `;
                });
            } else {
                grid.innerHTML = `
                    <div class="col-span-full text-center py-12 text-yellow-500 bg-yellow-500/5 rounded-xl border border-yellow-500/20">
                        <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                        <p>Aucun site obsolète n'a été validé sur cette page de l'INSEE. Modifiez vos critères (ex: essayez un autre département ou un autre corps de métier).</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error(error);
            grid.innerHTML = '<p class="col-span-full text-center text-red-500">Erreur réseau ou format de réponse invalide.</p>';
        }
    }
    </script>
</body>
</html>