<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\PageController;
use App\Controllers\ShopController;
use App\Core\Router;

return static function (Router $router): void {
    $router->get('/', [HomeController::class, 'index']);
    $router->get('/shop', [ShopController::class, 'index']);
    $router->get('/shop/{slug}', [ShopController::class, 'show']);
    $router->get('/checkout', [ShopController::class, 'checkout']);
    $router->get('/profile', [ShopController::class, 'profile']);
    $router->get('/profile/documents/{orderId}/{type}', [ShopController::class, 'document']);
    $router->get('/profile/documents/{orderId}/{type}/preview', [ShopController::class, 'documentPreview']);
    $router->post('/profile/signature/{orderId}', [ShopController::class, 'saveSignature']);
    $router->get('/admin', [ShopController::class, 'admin']);
    $router->post('/admin/orders/{orderId}/validate/{type}', [ShopController::class, 'validatePayment']);

    $router->get('/mentions-legales', [PageController::class, 'mentionsLegales']);
    $router->get('/apropos', [PageController::class, 'apropos']);
    $router->get('/cgu', [PageController::class, 'cgu']);
    $router->get('/cgv', [PageController::class, 'cgv']);
    $router->get('/paiement-securise', [PageController::class, 'paiementSecurise']);
    $router->get('/conditions-livraison', [PageController::class, 'conditionsLivraison']);
    $router->get('/partenaire', [PageController::class, 'partenaire']);
    $router->get('/creations', [PageController::class, 'creations']);
    $router->get('/contact', [PageController::class, 'contact']);
    $router->get('/audit', [PageController::class, 'audit']);
    $router->post('/audit', [PageController::class, 'audit']);

    $router->get('/login', [AuthController::class, 'loginForm']);
    $router->post('/login', [AuthController::class, 'login']);
    $router->get('/register', [AuthController::class, 'registerForm']);
    $router->post('/register', [AuthController::class, 'register']);

    $router->get('/admin/login', [AuthController::class, 'adminLoginForm']);
    $router->post('/admin/login', [AuthController::class, 'adminLogin']);

    $router->post('/logout', [AuthController::class, 'logout']);
};
