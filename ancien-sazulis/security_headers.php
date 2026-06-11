<?php
// security_headers.php
// ⚠️ À inclure AVANT tout output HTML

// --- Basic security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), camera=(), microphone=()");

// HSTS (active uniquement si ton site est en HTTPS)
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header("Strict-Transport-Security: max-age=63072000; includeSubDomains; preload");
}

// --- CSP
// NOTE: tu voulais enlever les restrictions images -> on autorise images en https/http + data/blob.
// (On évite img-src * qui autorise aussi des schémas bizarres.)
$csp = implode(';', [
    "default-src 'self'",
    "base-uri 'self'",
    "object-src 'none'",
    "frame-ancestors 'self'",

    // Styles / Fonts
    "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com",
    "font-src  'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com https://fonts.googleapis.com",

    // Scripts
    "script-src 'self' 'unsafe-inline' https://code.jquery.com https://js.stripe.com https://www.paypal.com",

    // Images (✅ pas de restriction bloquante)
    "img-src 'self' data: blob: https: http:",

    // PayPal/Stripe frames + XHR
    "frame-src 'self' https://js.stripe.com https://checkout.stripe.com https://hooks.stripe.com https://www.paypal.com https://www.sandbox.paypal.com",
    "connect-src 'self' https://api.stripe.com https://www.paypal.com https://api-m.paypal.com https://api-m.sandbox.paypal.com",

    // Optionnel mais utile
    "upgrade-insecure-requests",
]);

header("Content-Security-Policy: $csp");

// --- IMPORTANT:
// Tu avais COEP=require-corp + CORP=same-origin.
// Ça casse *souvent* les images/CDN externes (elles n'envoient pas les bons en-têtes).
// => On les désactive pour retrouver l'affichage normal.
// header("Cross-Origin-Opener-Policy: same-origin"); // optionnel
// header("Cross-Origin-Embedder-Policy: require-corp"); // ❌ désactivé
// header("Cross-Origin-Resource-Policy: same-origin"); // ❌ désactivé

// Si tu veux garder un minimum sans casser les images externes :
header("Cross-Origin-Opener-Policy: same-origin");
header("Cross-Origin-Resource-Policy: cross-origin");

// ⚠️ Évite ce header si tu n'exposes pas une API publique (sinon: fuites de données possibles)
// header("Access-Control-Allow-Origin: *");
