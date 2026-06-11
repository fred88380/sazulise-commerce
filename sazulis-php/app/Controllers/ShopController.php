<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Repositories\ProductRepository;

final class ShopController extends Controller
{
    private function tcpdfAutoloadPath(): string
    {
        return dirname(__DIR__, 2) . '/../ancien-sazulis/vendor/autoload.php';
    }

    public function index(): void
    {
        $repo = new ProductRepository();
        $this->render('shop/catalog', [
            'products' => $repo->all(),
            'metaTitle' => 'Boutique Sazulis',
        ]);
    }

    public function show(string $slug): void
    {
        $repo = new ProductRepository();
        $product = $repo->findBySlug($slug);

        if ($product === null) {
            http_response_code(404);
            $this->render('shop/product', [
                'notFound' => true,
                'metaTitle' => 'Produit introuvable',
            ]);
            return;
        }

        $this->render('shop/product', [
            'product' => $product,
            'notFound' => false,
            'metaTitle' => $product['name'] . ' - Sazulis',
        ]);
    }

    public function checkout(): void
    {
        $user = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
        $this->render('checkout/index', [
            'metaTitle' => 'Checkout intelligent - Sazulis',
            'user' => $user,
        ]);
    }

    public function admin(): void
    {
        $user = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
        if (!$user || (($user['role'] ?? 'client') !== 'admin')) {
            $this->redirect('/login');
        }

        $repo = new ProductRepository();
        $products = $repo->all();
        $totalStock = array_sum(array_column($products, 'stock'));

        $this->render('admin/dashboard', [
            'products' => $products,
            'totalStock' => $totalStock,
            'metaTitle' => 'Backoffice Sazulis',
        ]);
    }

    public function profile(): void
    {
        $user = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
        if (!$user || (($user['role'] ?? 'client') !== 'client')) {
            $this->redirect('/login');
        }

        $orders = [];
        $pdo = Database::getConnection();
        if ($pdo !== null) {
            try {
                $stmt = $pdo->prepare(
                    'SELECT id, order_ref, customer_name, customer_email, total, status, created_at
                     FROM orders
                     WHERE user_id = :user_id OR customer_email = :email
                     ORDER BY created_at DESC'
                );
                $stmt->execute([
                    'user_id' => (int) ($user['id'] ?? 0),
                    'email' => (string) ($user['email'] ?? ''),
                ]);
                $orders = $stmt->fetchAll() ?: [];

                if (!empty($orders)) {
                    $itemStmt = $pdo->prepare('SELECT product_name, unit_price, quantity FROM order_items WHERE order_id = :order_id ORDER BY id ASC');
                    foreach ($orders as &$order) {
                        $itemStmt->execute(['order_id' => (int) $order['id']]);
                        $order['items'] = $itemStmt->fetchAll() ?: [];
                    }
                    unset($order);
                }
            } catch (\Throwable) {
                $orders = [];
            }
        }

        $this->render('account/profile', [
            'metaTitle' => 'Mon profil client - Sazulis',
            'user' => $user,
            'orders' => $orders,
        ]);
    }

    public function document(string $orderId, string $type): void
    {
        $user = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
        if (!$user || (($user['role'] ?? 'client') !== 'client')) {
            $this->redirect('/login');
        }

        $documentType = strtolower(trim($type));
        if (!in_array($documentType, ['contract', 'invoice'], true)) {
            http_response_code(404);
            echo 'Document introuvable';
            return;
        }

        $pdo = Database::getConnection();
        if ($pdo === null) {
            http_response_code(503);
            echo 'Base de donnees indisponible';
            return;
        }

        try {
            $stmt = $pdo->prepare(
                'SELECT id, order_ref, customer_name, customer_email, customer_address, total, status, created_at
                 FROM orders
                                 WHERE id = :order_id
                                     AND (user_id = :user_id OR customer_email = :email)
                 LIMIT 1'
            );
            $stmt->execute([
                                'order_id' => (int) $orderId,
                'user_id' => (int) ($user['id'] ?? 0),
                'email' => (string) ($user['email'] ?? ''),
            ]);
            $order = $stmt->fetch();

            if (!$order) {
                http_response_code(404);
                echo 'Commande introuvable';
                return;
            }

            $itemStmt = $pdo->prepare('SELECT product_name, unit_price, quantity FROM order_items WHERE order_id = :order_id ORDER BY id ASC');
            $itemStmt->execute(['order_id' => (int) $order['id']]);
            $order['items'] = $itemStmt->fetchAll() ?: [];
        } catch (\Throwable) {
            http_response_code(500);
            echo 'Impossible de generer le document';
            return;
        }

        $autoload = $this->tcpdfAutoloadPath();
        if (!is_file($autoload)) {
            http_response_code(500);
            echo 'TCPDF introuvable dans ancien-sazulis';
            return;
        }

        require_once $autoload;

        if (ob_get_level() > 0) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }
        ob_start();

        $label = $documentType === 'invoice' ? 'Facture' : 'Contrat';
        $filenameBase = (string) ($order['order_ref'] ?? ('commande_' . (string) $order['id']));
        $filename = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $label . '_' . $filenameBase) . '.pdf';

        $logoPath = dirname(__DIR__, 2) . '/../ancien-sazulis/assets/img/sazulis-logo1.png';

        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Sazulis');
        $pdf->SetAuthor('Sazulis');
        $pdf->SetTitle($label . ' - ' . (string) ($order['order_ref'] ?? ''));
        $pdf->SetMargins(14, 16, 14);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);

        $title = $documentType === 'invoice' ? 'FACTURE' : 'CONTRAT DE PRESTATION';
        $createdAt = !empty($order['created_at'])
            ? (new \DateTimeImmutable((string) $order['created_at']))->format('d/m/Y')
            : (new \DateTimeImmutable('now'))->format('d/m/Y');
        $customerName = htmlspecialchars((string) ($order['customer_name'] ?? 'Client'), ENT_QUOTES, 'UTF-8');
        $customerEmail = htmlspecialchars((string) ($order['customer_email'] ?? ''), ENT_QUOTES, 'UTF-8');
        $customerAddress = htmlspecialchars((string) ($order['customer_address'] ?? ''), ENT_QUOTES, 'UTF-8');
        $orderRef = htmlspecialchars((string) ($order['order_ref'] ?? ''), ENT_QUOTES, 'UTF-8');
        $status = htmlspecialchars(ucfirst((string) ($order['status'] ?? 'pending')), ENT_QUOTES, 'UTF-8');
        $total = number_format((float) ($order['total'] ?? 0), 2, ',', ' ');
        $items = isset($order['items']) && is_array($order['items']) ? $order['items'] : [];

        $html = '<table cellpadding="6" cellspacing="0" border="0" width="100%"><tr><td width="65%">';
        if (is_file($logoPath)) {
            $html .= '<img src="' . $logoPath . '" width="72"><br>';
        }
        $html .= '<span style="font-size:24px;font-weight:bold;color:#1a2347;">SAZULIS</span><br>';
        $html .= '<span style="color:#5e6b84;">Document telechargeable client</span>';
        $html .= '</td><td width="35%" align="right"><span style="font-size:18px;font-weight:bold;">' . $title . '</span><br>Ref: ' . $orderRef . '<br>Date: ' . htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') . '</td></tr></table>';
        $html .= '<hr style="border:0;border-top:2px solid #1a2347;">';
        $html .= '<table cellpadding="8" cellspacing="0" border="1" width="100%" style="border-color:#e4eaf3;">';
        $html .= '<tr><td width="50%"><b>Client</b><br>' . $customerName . '<br>' . $customerEmail . '<br>' . $customerAddress . '</td><td width="50%"><b>Resume commande</b><br><b>Reference:</b> ' . $orderRef . '<br><b>Statut:</b> ' . $status . '<br><b>Total:</b> ' . $total . ' EUR</td></tr>';
        $html .= '</table>';

        if ($documentType === 'invoice') {
            $html .= '<br><h2>Details facture</h2><table cellpadding="6" cellspacing="0" border="1" width="100%" style="border-color:#e4eaf3;border-collapse:collapse;"><tr style="background-color:#f8fafc;"><th align="left">Designation</th><th width="60" align="center">Qté</th><th width="90" align="right">PU</th><th width="90" align="right">Total</th></tr>';
            if (empty($items)) {
                $html .= '<tr><td colspan="4">Aucun article trouve</td></tr>';
            } else {
                foreach ($items as $item) {
                    $name = htmlspecialchars((string) ($item['product_name'] ?? 'Produit'), ENT_QUOTES, 'UTF-8');
                    $unitPrice = number_format((float) ($item['unit_price'] ?? 0), 2, ',', ' ');
                    $quantity = max(1, (int) ($item['quantity'] ?? 0));
                    $lineTotal = number_format((float) ($item['unit_price'] ?? 0) * $quantity, 2, ',', ' ');
                    $html .= '<tr><td>' . $name . '</td><td align="center">' . $quantity . '</td><td align="right">' . $unitPrice . ' EUR</td><td align="right">' . $lineTotal . ' EUR</td></tr>';
                }
            }
            $html .= '</table>';
        } else {
            $html .= '<br><h2>Conditions contractuelles</h2><ul><li>Commande liee: ' . $orderRef . '</li><li>Statut actuel: ' . $status . '</li><li>Montant convenu: ' . $total . ' EUR</li><li>Delai de livraison ou de realisation selon validation client</li></ul>';
        }

        $html .= '<br><table cellpadding="10" cellspacing="0" border="0" width="100%"><tr><td width="48%" style="border:1px solid #e4eaf3;min-height:70px;"><b>Sazulis</b><br><br><br><hr></td><td width="4%"></td><td width="48%" style="border:1px solid #e4eaf3;min-height:70px;"><b>Client</b><br><br><br><hr></td></tr></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        $pdf->Output($filename, 'I');
        exit;
    }
}
