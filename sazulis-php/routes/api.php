<?php

declare(strict_types=1);

use App\Controllers\ApiController;
use App\Core\Router;

return static function (Router $router): void {
    $router->get('/api/products', [ApiController::class, 'products']);
    $router->post('/api/orders', [ApiController::class, 'createOrder']);
};
