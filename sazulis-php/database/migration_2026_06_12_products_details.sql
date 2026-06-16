USE sazulis_v2;

ALTER TABLE products
  ADD COLUMN IF NOT EXISTS unlimited_stock TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS long_description TEXT NULL,
  ADD COLUMN IF NOT EXISTS highlights_json TEXT NULL,
  ADD COLUMN IF NOT EXISTS included_json TEXT NULL,
  ADD COLUMN IF NOT EXISTS not_included_json TEXT NULL;

-- Tous les produits en stock illimite
UPDATE products
SET unlimited_stock = 1,
    stock = 0;

-- Contenus de base pour les fiches
UPDATE products
SET not_included_json = '["Creation de contenu texte illimite non incluse", "Campagnes publicitaires payantes (SEA) non incluses", "Interventions hors perimetre du pack non incluses"]'
WHERE not_included_json IS NULL OR not_included_json = '';

UPDATE products
SET included_json = '["Developpement du projet", "Configuration technique de base", "Recette fonctionnelle avant livraison"]'
WHERE included_json IS NULL OR included_json = '';

-- Regle metier demandee: inclus pour site vitrine, e-commerce, wordpress, application web
UPDATE products
SET included_json = '["Developpement du projet", "Configuration technique de base", "Recette fonctionnelle avant livraison", "Hebergement d\'un an offert", "SEO inclus", "3 mois de maintenance Basic offerts"]'
WHERE slug LIKE 'site-vitrine%'
   OR slug LIKE 'e-commerce%'
   OR slug LIKE 'wordpress-cms%'
   OR slug LIKE 'application-web%';

-- Descriptions longues par famille
UPDATE products
SET long_description = CASE
    WHEN slug LIKE 'site-vitrine%' THEN 'Pack site vitrine professionnel pour presenter votre activite avec une image premium, des performances rapides et une structure claire orientee conversion.'
    WHEN slug LIKE 'e-commerce%' THEN 'Solution e-commerce complete orientee ventes: parcours client simple, fiches produits optimisees et tunnel de commande fluide.'
    WHEN slug LIKE 'wordpress-cms%' THEN 'Offre WordPress CMS pour gerer facilement vos contenus avec une base technique moderne et fiable.'
    WHEN slug LIKE 'application-web%' THEN 'Prestation application web sur mesure pour digitaliser vos processus metier avec un socle robuste et evolutif.'
    WHEN slug LIKE 'seo%' THEN 'Pack SEO pour ameliorer votre visibilite avec des optimisations techniques, semantiques et structurelles.'
    WHEN slug LIKE 'maintenance%' THEN 'Service de maintenance pour securiser votre site avec suivi preventif et interventions reactives selon la formule.'
    WHEN slug LIKE 'hebergement%' THEN 'Offre d\'hebergement avec supervision, disponibilite et assistance technique selon le niveau de service choisi.'
    WHEN slug LIKE 'refonte-site%' THEN 'Refonte de site pour moderniser design, performances et structure SEO tout en conservant vos objectifs business.'
    ELSE COALESCE(long_description, description)
END;

-- Points cles par famille
UPDATE products
SET highlights_json = CASE
    WHEN slug LIKE 'site-vitrine%' THEN '["Design responsive desktop/mobile", "Pages orientees conversion", "Mise en ligne avec checklist qualite"]'
    WHEN slug LIKE 'e-commerce%' THEN '["Catalogue optimise", "Tunnel de commande simplifie", "Parcours client rapide"]'
    WHEN slug LIKE 'wordpress-cms%' THEN '["Back-office simple", "Publication de contenu autonome", "Base technique evolutive"]'
    WHEN slug LIKE 'application-web%' THEN '["Fonctionnalites metier sur mesure", "Architecture maintenable", "UX fluide et moderne"]'
    WHEN slug LIKE 'seo%' THEN '["Audit SEO initial", "Optimisations on-page prioritaires", "Plan d\'actions classe par impact"]'
    WHEN slug LIKE 'maintenance%' THEN '["Surveillance continue", "Correctifs encadres", "Suivi des interventions"]'
    WHEN slug LIKE 'hebergement%' THEN '["Infrastructure stable", "Sauvegardes planifiees", "Support technique"]'
    WHEN slug LIKE 'refonte-site%' THEN '["Audit de l\'existant", "Nouveau design", "Migration accompagnee"]'
    ELSE highlights_json
END;

-- Verification rapide
SELECT slug, name, unlimited_stock, included_json
FROM products
WHERE slug LIKE 'site-vitrine%'
   OR slug LIKE 'e-commerce%'
   OR slug LIKE 'wordpress-cms%'
   OR slug LIKE 'application-web%'
ORDER BY slug;
