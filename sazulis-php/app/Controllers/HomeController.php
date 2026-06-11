<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\ProductRepository;

final class HomeController extends Controller
{
    public function index(): void
    {
        $repo = new ProductRepository();
        $products = array_slice($repo->all(), 0, 3);

        $this->render('home/index', [
            'products' => $products,
            'metaTitle' => 'Sazulis - E-commerce nouvelle generation',
        ]);
    }
}
