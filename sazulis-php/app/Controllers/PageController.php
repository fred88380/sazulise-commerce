<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class PageController extends Controller
{
    public function mentionsLegales(): void
    {
        $this->render('pages/mentions-legales', ['metaTitle' => 'Mentions legales - Sazulis']);
    }

    public function apropos(): void
    {
        $this->render('pages/apropos', ['metaTitle' => 'A propos - Sazulis']);
    }

    public function cgu(): void
    {
        $this->render('pages/cgu', ['metaTitle' => 'CGU - Sazulis']);
    }

    public function cgv(): void
    {
        $this->render('pages/cgv', ['metaTitle' => 'CGV - Sazulis']);
    }

    public function paiementSecurise(): void
    {
        $this->render('pages/paiement-securise', ['metaTitle' => 'Paiement securise - Sazulis']);
    }

    public function conditionsLivraison(): void
    {
        $this->render('pages/conditions-livraison', ['metaTitle' => 'Conditions de livraison - Sazulis']);
    }

    public function partenaire(): void
    {
        $this->render('pages/partenaire', ['metaTitle' => 'Partenaires - Sazulis']);
    }

    public function creations(): void
    {
        $this->render('pages/creations', ['metaTitle' => 'Creations - Sazulis']);
    }

    public function contact(): void
    {
        $this->render('pages/contact', ['metaTitle' => 'Contact - Sazulis']);
    }

    public function audit(): void
    {
        $this->render('pages/audit', ['metaTitle' => 'Audit - Sazulis']);
    }
}
