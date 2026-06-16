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
                return array_map(fn (array $item): array => $this->enrich($this->normalize($item)), $items);
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
            'unlimited_stock' => true,
        ];
    }

    private function seed(): array
    {
        $items = require __DIR__ . '/../Data/productCatalog.php';
        return array_map(fn (array $item): array => $this->enrich($item), $items);
    }

    private function enrich(array $product): array
    {
        $product['unlimited_stock'] = (bool) ($product['unlimited_stock'] ?? true);

        $slug = (string) ($product['slug'] ?? '');
        $family = $this->detectFamily($slug);
        $stack = str_contains($slug, 'nextjs') ? 'Next.js' : 'HTML';
        $tier = $this->detectTier($slug);

        if (empty($product['long_description'])) {
            $product['long_description'] = $this->buildLongDescription($family, $stack, $tier);
        }

        if (empty($product['highlights']) || !is_array($product['highlights'])) {
            $product['highlights'] = $this->buildHighlights($family, $stack, $tier);
        }

        if (empty($product['included']) || !is_array($product['included'])) {
            $product['included'] = $this->buildIncluded($family, $stack, $tier);
        }

        if (empty($product['not_included']) || !is_array($product['not_included'])) {
            $product['not_included'] = [
                'Creation de contenu texte illimite',
                'Campagnes publicitaires payantes (SEA)',
                'Interventions hors perimetre du pack',
            ];
        }

        return $product;
    }

    private function detectFamily(string $slug): string
    {
        foreach ([
            'application-web' => 'application-web',
            'e-commerce' => 'e-commerce',
            'hebergement' => 'hebergement',
            'maintenance' => 'maintenance',
            'refonte-site' => 'refonte-site',
            'seo' => 'seo',
            'site-vitrine' => 'site-vitrine',
            'wordpress-cms' => 'wordpress-cms',
        ] as $needle => $family) {
            if (str_starts_with($slug, $needle)) {
                return $family;
            }
        }

        return 'generic';
    }

    private function detectTier(string $slug): string
    {
        foreach (['starter', 'basic', 'business', 'premium', 'urgente'] as $tier) {
            if (str_contains($slug, $tier)) {
                return $tier;
            }
        }

        return 'standard';
    }

    private function buildLongDescription(string $family, string $stack, string $tier): string
    {
        return match ($family) {
            'site-vitrine' => "Ce pack {$stack} est concu pour presenter votre entreprise avec une image premium, un chargement rapide et une structure claire pour convertir vos visiteurs en demandes de contact.",
            'application-web' => "Cette prestation {$stack} permet de construire une application metier stable et evolutive, avec un parcours utilisateur fluide et un socle technique propre pour vos evolutions futures.",
            'e-commerce' => "Cette solution e-commerce {$stack} est orientee conversion: fiches produits performantes, tunnel d'achat simple et architecture optimisee pour encaisser la croissance de votre catalogue.",
            'wordpress-cms' => "Cette offre combine un back-office WordPress simple a prendre en main avec un front {$stack} moderne, pour publier vite tout en conservant de bonnes performances.",
            'seo' => "Ce pack SEO {$tier} vise a renforcer votre visibilite locale et nationale avec des optimisations techniques, semantiques et structurelles adaptees a votre activite.",
            'maintenance' => "Cette formule de maintenance {$tier} securise votre presence en ligne avec un suivi preventif, des correctifs rapides et un cadre d'intervention clair.",
            'hebergement' => "Cette offre d'hebergement {$tier} apporte un environnement fiable pour votre site {$stack}, avec surveillance, disponibilite et assistance adaptees a votre niveau de besoin.",
            'refonte-site' => "Cette refonte {$stack} modernise votre site existant: design, performance, lisibilite mobile et structure SEO sont retravailles pour relancer votre croissance digitale.",
            default => 'Prestation digitale sur mesure avec cadrage, execution propre et accompagnement professionnel.',
        };
    }

    private function buildHighlights(string $family, string $stack, string $tier): array
    {
        $common = [
            "Developpement {$stack} propre et maintenable",
            'Design responsive desktop/mobile',
            'Mise en ligne avec checklist qualite',
        ];

        return match ($family) {
            'site-vitrine' => array_merge($common, ['Pages claires orientees conversion', 'Formulaire de contact optimise']),
            'application-web' => array_merge($common, ['Fonctionnalites metier sur mesure', 'Architecture evolutive']),
            'e-commerce' => array_merge($common, ['Tunnel de commande simplifie', 'Catalogue et fiches produits optimises']),
            'wordpress-cms' => array_merge($common, ['Edition de contenu autonome', 'Back-office simplifie']),
            'seo' => ['Audit SEO initial', 'Optimisations on-page prioritaires', 'Plan d\'actions classe par impact'],
            'maintenance' => ['Surveillance et correctifs', 'Interventions selon SLA du pack', "Rapport d'actions periodique"],
            'hebergement' => ['Infrastructure adaptee au trafic', 'Sauvegardes et supervision', "Support {$tier} selon formule"],
            'refonte-site' => ['Audit de l\'existant', 'Nouveau design et structure', 'Migration progressive securisee'],
            default => $common,
        };
    }

    private function buildIncluded(string $family, string $stack, string $tier): array
    {
        return match ($family) {
            'site-vitrine', 'application-web', 'e-commerce', 'wordpress-cms' => [
                "Developpement {$stack} du projet",
                'Configuration technique de base',
                'Recette fonctionnelle avant livraison',
                "Hebergement d'un an offert",
                'SEO inclus',
                '3 mois de maintenance Basic offerts',
            ],
            'seo' => [
                'Analyse technique et semantique',
                "Optimisations SEO du pack {$tier}",
                'Recommandations prioritaires',
            ],
            'maintenance' => [
                'Suivi de bon fonctionnement',
                "Interventions de maintenance {$tier}",
                'Correctifs dans le perimetre du pack',
            ],
            'hebergement' => [
                "Environnement d'hebergement {$tier}",
                'Sauvegardes planifiees',
                'Assistance technique standard',
            ],
            'refonte-site' => [
                'Audit UX et technique',
                'Refonte front-end et structure',
                'Livraison avec mise en ligne accompagnee',
            ],
            default => ['Prestation principale du produit', 'Validation qualite', 'Support de demarrage'],
        };
    }
}
