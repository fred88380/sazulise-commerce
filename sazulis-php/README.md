# Sazulis PHP V2

Nouvelle version du site Sazulis en 100% PHP, architecture MVC legere, design e-commerce moderne et checkout API.

## Stack
- PHP 8.1+
- Apache (XAMPP)
- MySQL (optionnel, fallback data integree)
- HTML/CSS/JS natif

## Fonctionnalites V2
- Landing page immersive
- Catalogue produits dynamique
- Fiche produit detaillee
- Panier en localStorage
- Checkout connecte a une API interne
- Backoffice basique (vue stocks)
- Animations UI et responsive mobile

## Installation rapide (XAMPP)
1. Copier `.env.example` en `.env`.
2. Importer `database/schema.sql` dans phpMyAdmin.
3. Verifier que Apache + MySQL sont demarres.
4. Ouvrir:
   - `http://localhost/sazulis/sazulis-php`

## Lancement sur le port 3000 (PHP)
Si tu veux absolument `localhost:3000`, lance un serveur PHP et non un serveur front statique:

```powershell
cd c:\xampp\htdocs\sazulis\sazulis-php
php -S localhost:3000 index.php
```

Puis ouvre:
- `http://localhost:3000`

## Endpoints API
- `GET /api/products`
- `POST /api/orders`

## Roadmap recommandee
- Auth admin securisee
- Vrai paiement Stripe/PayPal
- Gestion commandes en base
- Recherche, filtres, coupons
- Dashboard analytics temps reel
