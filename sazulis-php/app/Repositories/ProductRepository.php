<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class ProductRepository
{
    public function all(): array
    {
        $pdo = Database::getConnection();
        if ($pdo !== null) {
            $stmt = $pdo->query('SELECT id, slug, name, description, price, image, featured, badges, stock FROM products ORDER BY featured DESC, id DESC');
            $items = $stmt->fetchAll();
            if (!empty($items)) {
                return array_map([$this, 'normalize'], $items);
            }
        }

        return $this->seed();
    }

    public function findBySlug(string $slug): ?array
    {
        $products = $this->all();
        foreach ($products as $product) {
            if ($product['slug'] === $slug) {
                return $product;
            }
        }
        return null;
    }

    private function normalize(array $row): array
    {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'slug' => (string) ($row['slug'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
            'description' => (string) ($row['description'] ?? ''),
            'price' => (float) ($row['price'] ?? 0),
            'image' => (string) ($row['image'] ?? '/assets/img/index.png'),
            'featured' => (bool) ($row['featured'] ?? false),
            'badges' => is_string($row['badges'] ?? null) ? array_filter(array_map('trim', explode(',', $row['badges']))) : [],
            'stock' => (int) ($row['stock'] ?? 10),
        ];
    }

    private function seed(): array
    {
        return [
            [
                'id' => 1,
                'slug' => 'application-web-html',
                'name' => 'Application Web HTML',
                'description' => 'Developpement d\'une application web en HTML.',
                'price' => 5015.13,
                'image' => '/assets/img/application-web.png',
                'featured' => true,
                'badges' => ['Dev Web'],
                'stock' => 10,
            ],
            [
                'id' => 2,
                'slug' => 'application-web-nextjs',
                'name' => 'Application Web Next.js',
                'description' => 'Developpement d\'une application web avec Next.js.',
                'price' => 6087.36,
                'image' => '/assets/img/application-web.png',
                'featured' => true,
                'badges' => ['Dev Web'],
                'stock' => 10,
            ],
            [
                'id' => 3,
                'slug' => 'e-commerce-html',
                'name' => 'E-commerce HTML',
                'description' => 'Site e-commerce en HTML.',
                'price' => 6373.63,
                'image' => '/assets/img/e-commerce2.png',
                'featured' => true,
                'badges' => ['E-commerce'],
                'stock' => 10,
            ],
            [
                'id' => 4,
                'slug' => 'e-commerce-nextjs',
                'name' => 'E-commerce Next.js',
                'description' => 'Site e-commerce avec Next.js.',
                'price' => 8299.00,
                'image' => '/assets/img/e-commerce2.png',
                'featured' => true,
                'badges' => ['E-commerce', 'Top Vente'],
                'stock' => 10,
            ],
            [
                'id' => 5,
                'slug' => 'hebergement-basic-html',
                'name' => 'Hébergement Basic HTML',
                'description' => 'Hebergement basique pour site HTML.',
                'price' => 149.99,
                'image' => '/assets/img/hebergement.jpg',
                'featured' => false,
                'badges' => ['Hebergement'],
                'stock' => 50,
            ],
            [
                'id' => 6,
                'slug' => 'hebergement-basic-nextjs',
                'name' => 'Hébergement Basic Next.js',
                'description' => 'Hebergement basique pour site Next.js.',
                'price' => 299.99,
                'image' => '/assets/img/hebergement.jpg',
                'featured' => false,
                'badges' => ['Hebergement'],
                'stock' => 50,
            ],
            [
                'id' => 7,
                'slug' => 'hebergement-business-html',
                'name' => 'Hébergement Business HTML',
                'description' => 'Hebergement business pour site HTML.',
                'price' => 289.99,
                'image' => '/assets/img/hebergement.jpg',
                'featured' => false,
                'badges' => ['Hebergement'],
                'stock' => 50,
            ],
            [
                'id' => 8,
                'slug' => 'hebergement-business-nextjs',
                'name' => 'Hébergement Business Next.js',
                'description' => 'Hebergement business pour site Next.js.',
                'price' => 579.98,
                'image' => '/assets/img/hebergement.jpg',
                'featured' => false,
                'badges' => ['Hebergement'],
                'stock' => 50,
            ],
            [
                'id' => 9,
                'slug' => 'hebergement-premium-html',
                'name' => 'Hébergement Premium HTML',
                'description' => 'Hebergement premium pour site HTML.',
                'price' => 579.99,
                'image' => '/assets/img/hebergement.jpg',
                'featured' => false,
                'badges' => ['Hebergement', 'Premium'],
                'stock' => 50,
            ],
            [
                'id' => 10,
                'slug' => 'hebergement-premium-nextjs',
                'name' => 'Hébergement Premium Next.js',
                'description' => 'Hebergement premium pour site Next.js.',
                'price' => 1159.96,
                'image' => '/assets/img/hebergement.jpg',
                'featured' => false,
                'badges' => ['Hebergement', 'Premium'],
                'stock' => 50,
            ],
            [
                'id' => 11,
                'slug' => 'maintenance-basic',
                'name' => 'Maintenance Basic',
                'description' => 'Maintenance basique.',
                'price' => 350.00,
                'image' => '/assets/img/maintenance.jpg',
                'featured' => false,
                'badges' => ['Maintenance'],
                'stock' => 100,
            ],
            [
                'id' => 12,
                'slug' => 'maintenance-business',
                'name' => 'Maintenance Business',
                'description' => 'Maintenance business.',
                'price' => 650.00,
                'image' => '/assets/img/maintenance.jpg',
                'featured' => false,
                'badges' => ['Maintenance'],
                'stock' => 100,
            ],
            [
                'id' => 13,
                'slug' => 'maintenance-premium',
                'name' => 'Maintenance Premium',
                'description' => 'Maintenance premium.',
                'price' => 950.00,
                'image' => '/assets/img/maintenance.jpg',
                'featured' => false,
                'badges' => ['Maintenance', 'Premium'],
                'stock' => 100,
            ],
            [
                'id' => 14,
                'slug' => 'maintenance-urgente',
                'name' => 'Maintenance Urgente',
                'description' => 'Maintenance urgente.',
                'price' => 200.00,
                'image' => '/assets/img/urgente.png',
                'featured' => false,
                'badges' => ['Maintenance'],
                'stock' => 100,
            ],
            [
                'id' => 15,
                'slug' => 'refonte-site-html',
                'name' => 'Refonte site HTML',
                'description' => 'Refonte de site en HTML.',
                'price' => 250.00,
                'image' => '/assets/img/refonte-site-internet.jpg',
                'featured' => false,
                'badges' => ['Refonte'],
                'stock' => 25,
            ],
            [
                'id' => 16,
                'slug' => 'refonte-site-nextjs',
                'name' => 'Refonte site Next.js',
                'description' => 'Refonte de site avec Next.js.',
                'price' => 600.00,
                'image' => '/assets/img/refonte-site-internet.jpg',
                'featured' => false,
                'badges' => ['Refonte'],
                'stock' => 25,
            ],
            [
                'id' => 17,
                'slug' => 'seo-basic',
                'name' => 'SEO Basic',
                'description' => 'Pack SEO basic pour ameliorer la visibilite Google.',
                'price' => 299.99,
                'image' => '/assets/img/seo.png',
                'featured' => false,
                'badges' => ['SEO'],
                'stock' => 100,
            ],
            [
                'id' => 18,
                'slug' => 'seo-business',
                'name' => 'SEO Business',
                'description' => 'Pack SEO business.',
                'price' => 459.98,
                'image' => '/assets/img/seo.png',
                'featured' => false,
                'badges' => ['SEO'],
                'stock' => 100,
            ],
            [
                'id' => 19,
                'slug' => 'seo-premium',
                'name' => 'SEO Premium',
                'description' => 'Pack SEO premium.',
                'price' => 599.99,
                'image' => '/assets/img/seo.png',
                'featured' => false,
                'badges' => ['SEO', 'Premium'],
                'stock' => 100,
            ],
            [
                'id' => 20,
                'slug' => 'site-vitrine-html',
                'name' => 'Site vitrine HTML',
                'description' => 'Site vitrine en HTML.',
                'price' => 4326.87,
                'image' => '/assets/img/site-vitrine.png',
                'featured' => true,
                'badges' => ['Site Vitrine'],
                'stock' => 12,
            ],
            [
                'id' => 21,
                'slug' => 'site-vitrine-nextjs',
                'name' => 'Site vitrine Next.js',
                'description' => 'Site vitrine avec Next.js.',
                'price' => 5399.10,
                'image' => '/assets/img/site-vitrine.png',
                'featured' => true,
                'badges' => ['Site Vitrine', 'Top Vente'],
                'stock' => 12,
            ],
            [
                'id' => 22,
                'slug' => 'site-vitrine-pack-starter-html',
                'name' => 'Site vitrine pack-starter HTML',
                'description' => 'Pack starter site vitrine HTML.',
                'price' => 1316.98,
                'image' => '/assets/img/vitrine-site.png',
                'featured' => false,
                'badges' => ['Site Vitrine'],
                'stock' => 15,
            ],
            [
                'id' => 23,
                'slug' => 'site-vitrine-pack-starter-nextjs',
                'name' => 'Site vitrine pack-starter Next.js',
                'description' => 'Pack starter site vitrine Next.js.',
                'price' => 2899.10,
                'image' => '/assets/img/vitrine-site.png',
                'featured' => false,
                'badges' => ['Site Vitrine'],
                'stock' => 15,
            ],
            [
                'id' => 24,
                'slug' => 'wordpress-cms-html',
                'name' => 'WordPress CMS HTML',
                'description' => 'Site WordPress CMS HTML.',
                'price' => 3603.64,
                'image' => '/assets/img/wordpress.jpg',
                'featured' => false,
                'badges' => ['WordPress'],
                'stock' => 10,
            ],
            [
                'id' => 25,
                'slug' => 'wordpress-cms-nextjs',
                'name' => 'WordPress CMS Next.js',
                'description' => 'Site WordPress CMS Next.js.',
                'price' => 4675.87,
                'image' => '/assets/img/wordpress.jpg',
                'featured' => false,
                'badges' => ['WordPress'],
                'stock' => 10,
            ],
        ];
    }
}
