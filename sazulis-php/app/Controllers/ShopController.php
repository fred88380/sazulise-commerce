<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Repositories\ProductRepository;

$tcpdfBase = dirname(__DIR__, 2);
if (!class_exists('\\TCPDF', false)) {
    foreach ([
        $tcpdfBase . '/vendor/tecnickcom/tcpdf/tcpdf.php',
        $tcpdfBase . '/vendor/tcpdf/tcpdf.php',
        $tcpdfBase . '/tcpdf/tcpdf.php',
    ] as $tcpdfFile) {
        if (is_file($tcpdfFile)) {
            require_once $tcpdfFile;
            if (class_exists('\\TCPDF', false)) {
                break;
            }
        }
    }
}

final class ShopController extends Controller
{
    private function tcpdfBootstrapCandidates(): array
    {
        $base = dirname(__DIR__, 2);
        return [
            $base . '/vendor/autoload.php',
            $base . '/vendor/tecnickcom/tcpdf/tcpdf.php',
            $base . '/vendor/tcpdf/tcpdf.php',
            $base . '/tcpdf/tcpdf.php',
        ];
    }

    private function ensureTcpdfLoaded(): bool
    {
        if (class_exists('\\TCPDF', false)) {
            return true;
        }

        foreach ($this->tcpdfBootstrapCandidates() as $candidate) {
            if (!is_file($candidate)) {
                continue;
            }

            require_once $candidate;
            if (class_exists('\\TCPDF', false)) {
                return true;
            }
        }

        return false;
    }

    private function orderColumnExists(\PDO $pdo, string $column): bool
    {
        $stmt = $pdo->prepare('SHOW COLUMNS FROM orders LIKE :column');
        $stmt->execute(['column' => $column]);
        return (bool) $stmt->fetch();
    }

    private function ensureOrderDocumentColumns(\PDO $pdo): void
    {
        $columns = [
            'acompte_paye' => 'ALTER TABLE orders ADD COLUMN acompte_paye TINYINT(1) NOT NULL DEFAULT 0',
            'solde_regle' => 'ALTER TABLE orders ADD COLUMN solde_regle TINYINT(1) NOT NULL DEFAULT 0',
            'client_signature_path' => 'ALTER TABLE orders ADD COLUMN client_signature_path VARCHAR(255) NULL',
            'client_signature_at' => 'ALTER TABLE orders ADD COLUMN client_signature_at DATETIME NULL',
        ];

        foreach ($columns as $column => $sql) {
            if ($this->orderColumnExists($pdo, $column)) {
                continue;
            }
            $pdo->exec($sql);
        }
    }

    private function findFirstFile(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }
        return null;
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
        $orders = [];
        $totalUsers = 0;
        $totalOrders = 0;
        $totalRevenue = 0.0;

        $pdo = Database::getConnection();
        if ($pdo !== null) {
            try {
                $this->ensureOrderDocumentColumns($pdo);
                $totalUsers = (int) ($pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() ?: 0);
                $totalOrders = (int) ($pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn() ?: 0);
                $totalRevenue = (float) ($pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN ('paid','completed')")->fetchColumn() ?: 0);
                $stmt = $pdo->query(
                    'SELECT id, order_ref, customer_name, customer_email, total, status, acompte_paye, solde_regle, client_signature_path, created_at
                     FROM orders
                     ORDER BY created_at DESC
                     LIMIT 100'
                );
                $orders = $stmt ? ($stmt->fetchAll() ?: []) : [];
            } catch (\Throwable) {
                $orders = [];
            }
        }

        $this->render('admin/dashboard', [
            'products' => $products,
            'totalStock' => $totalStock,
            'orders' => $orders,
            'totalUsers' => $totalUsers,
            'totalOrders' => $totalOrders,
            'totalRevenue' => $totalRevenue,
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
        $twofaEnabled = (int) ($user['totp_enabled'] ?? 0) === 1;
        $pdo = Database::getConnection();
        if ($pdo !== null) {
            try {
                $this->ensureOrderDocumentColumns($pdo);
                $stmt = $pdo->prepare(
                    'SELECT id, order_ref, customer_name, customer_email, total, status, created_at, acompte_paye, solde_regle, client_signature_path, client_signature_at
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

            try {
                $twofaStmt = $pdo->prepare('SELECT totp_enabled FROM users WHERE id = :id LIMIT 1');
                $twofaStmt->execute(['id' => (int) ($user['id'] ?? 0)]);
                $twofaRow = $twofaStmt->fetch();
                if (is_array($twofaRow)) {
                    $twofaEnabled = (int) ($twofaRow['totp_enabled'] ?? 0) === 1;
                }
            } catch (\Throwable) {
                // Keep session fallback if column/table migration is not yet applied.
            }
        }

        $this->render('account/profile', [
            'metaTitle' => 'Mon profil client - Sazulis',
            'user' => $user,
            'orders' => $orders,
            'twofaEnabled' => $twofaEnabled,
            'profileNotice' => $_SESSION['profile_notice'] ?? null,
            'profileError' => $_SESSION['profile_error'] ?? null,
        ]);

        unset($_SESSION['profile_notice'], $_SESSION['profile_error']);
    }

    public function saveSignature(string $orderId): void
    {
        $user = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
        if (!$user || (($user['role'] ?? 'client') !== 'client')) {
            $this->redirect('/login');
        }

        $pdo = Database::getConnection();
        if ($pdo === null) {
            $this->redirect('/profile');
        }

        try {
            $this->ensureOrderDocumentColumns($pdo);
            $stmt = $pdo->prepare(
                'SELECT id
                 FROM orders
                 WHERE id = :order_id AND (user_id = :user_id OR customer_email = :email)
                 LIMIT 1'
            );
            $stmt->execute([
                'order_id' => (int) $orderId,
                'user_id' => (int) ($user['id'] ?? 0),
                'email' => (string) ($user['email'] ?? ''),
            ]);
            $order = $stmt->fetch();
            if (!$order) {
                $this->redirect('/profile');
            }

            $dataUrl = (string) ($_POST['signature_data'] ?? '');
            if (!preg_match('#^data:image/png;base64,#', $dataUrl)) {
                $this->redirect('/profile');
            }

            $raw = base64_decode(substr($dataUrl, strlen('data:image/png;base64,')), true);
            if ($raw === false || strlen($raw) < 200) {
                $this->redirect('/profile');
            }

            $sigDir = dirname(__DIR__, 2) . '/public/factures/signatures';
            if (!is_dir($sigDir)) {
                mkdir($sigDir, 0775, true);
            }

            $fileName = (int) $orderId . '_' . (int) ($user['id'] ?? 0) . '.png';
            $fullPath = $sigDir . '/' . $fileName;
            file_put_contents($fullPath, $raw);

            $relativePath = '/public/factures/signatures/' . $fileName;
            $update = $pdo->prepare('UPDATE orders SET client_signature_path = :path, client_signature_at = NOW() WHERE id = :order_id');
            $update->execute([
                'path' => $relativePath,
                'order_id' => (int) $orderId,
            ]);
        } catch (\Throwable) {
        }

        $this->redirect('/profile');
    }

    public function validatePayment(string $orderId, string $type): void
    {
        $user = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
        if (!$user || (($user['role'] ?? 'client') !== 'admin')) {
            $this->redirect('/login');
        }

        $kind = strtolower(trim($type));
        if (!in_array($kind, ['acompte', 'solde'], true)) {
            $this->redirect('/admin');
        }

        $pdo = Database::getConnection();
        if ($pdo === null) {
            $this->redirect('/admin');
        }

        $state = isset($_POST['state']) ? (int) $_POST['state'] : 1;
        $state = $state === 1 ? 1 : 0;

        try {
            $this->ensureOrderDocumentColumns($pdo);
            if ($kind === 'acompte') {
                $stmt = $pdo->prepare('UPDATE orders SET acompte_paye = :state WHERE id = :order_id');
                $stmt->execute([
                    'state' => $state,
                    'order_id' => (int) $orderId,
                ]);
            } else {
                $newStatus = $state === 1 ? 'completed' : 'processing';
                $stmt = $pdo->prepare('UPDATE orders SET solde_regle = :state, status = :status WHERE id = :order_id');
                $stmt->execute([
                    'state' => $state,
                    'status' => $newStatus,
                    'order_id' => (int) $orderId,
                ]);
            }
        } catch (\Throwable) {
        }

        $this->redirect('/admin');
    }

    /**
     * Charge une commande (et ses articles) et retourne le tableau de données.
     * Retourne null si introuvable ou erreur DB.
     */
    private function loadOrderForDocument(\PDO $pdo, string $orderId, array $user): ?array
    {
        $this->ensureOrderDocumentColumns($pdo);
        $stmt = $pdo->prepare(
            'SELECT id, order_ref, customer_name, customer_email, customer_address, total, status, created_at, acompte_paye, solde_regle, client_signature_path
             FROM orders
             WHERE id = :order_id
               AND (user_id = :user_id OR customer_email = :email)
             LIMIT 1'
        );
        $stmt->execute([
            'order_id' => (int) $orderId,
            'user_id'  => (int) ($user['id'] ?? 0),
            'email'    => (string) ($user['email'] ?? ''),
        ]);
        $order = $stmt->fetch();
        if (!$order) {
            return null;
        }
        $itemStmt = $pdo->prepare('SELECT product_name, unit_price, quantity FROM order_items WHERE order_id = :order_id ORDER BY id ASC');
        $itemStmt->execute(['order_id' => (int) $order['id']]);
        $order['items'] = $itemStmt->fetchAll() ?: [];
        return $order;
    }

    /**
     * Construit le bloc HTML du document (facture ou contrat) prêt pour TCPDF ou la vue.
     * Pour la vue HTML, $forWeb=true remplace les chemins filesystem par des URL publiques.
     *
     * @return array{html: string, title: string, label: string, filename: string}
     */
    private function buildDocumentHtml(array $order, string $documentType, bool $forWeb = false): array
    {
        $label     = $documentType === 'invoice' ? 'Facture' : 'Contrat';
        $title     = $documentType === 'invoice' ? 'FACTURE' : 'CONTRAT DE PRESTATION';
        $filenameBase = (string) ($order['order_ref'] ?? ('commande_' . (string) $order['id']));
        $filename  = (string) preg_replace('/[^a-zA-Z0-9_-]+/', '_', $label . '_' . $filenameBase) . '.pdf';

        $logoPathFs  = dirname(__DIR__, 2) . '/public/assets/img/sazulis-logo1.png';
        $logoSrc     = $forWeb ? '' : $logoPathFs;

        $createdAt     = !empty($order['created_at'])
            ? (new \DateTimeImmutable((string) $order['created_at']))->format('d/m/Y')
            : (new \DateTimeImmutable('now'))->format('d/m/Y');
        $customerName    = htmlspecialchars((string) ($order['customer_name'] ?? 'Client'), ENT_QUOTES, 'UTF-8');
        $customerEmail   = htmlspecialchars((string) ($order['customer_email'] ?? ''), ENT_QUOTES, 'UTF-8');
        $customerAddress = htmlspecialchars((string) ($order['customer_address'] ?? ''), ENT_QUOTES, 'UTF-8');
        $orderRef        = htmlspecialchars((string) ($order['order_ref'] ?? ''), ENT_QUOTES, 'UTF-8');
        $status          = htmlspecialchars(ucfirst((string) ($order['status'] ?? 'pending')), ENT_QUOTES, 'UTF-8');
        $total           = number_format((float) ($order['total'] ?? 0), 2, ',', ' ');
        $acomptePaye     = ((int) ($order['acompte_paye'] ?? 0) === 1);
        $soldeRegle      = ((int) ($order['solde_regle'] ?? 0) === 1);

        $clientSignaturePath = trim((string) ($order['client_signature_path'] ?? ''));
        $signatureFs = '';
        $signatureUrl = '';
        if ($clientSignaturePath !== '') {
            $candidate = dirname(__DIR__, 2) . '/' . ltrim($clientSignaturePath, '/');
            if (is_file($candidate)) {
                $signatureFs  = $candidate;
                $signatureUrl = $clientSignaturePath; // web-accessible relative path
            }
        }

        $stampAcompteFs = $this->findFirstFile([
            dirname(__DIR__, 2) . '/public/assets/img/Acompte-Paye.png',
            dirname(__DIR__, 2) . '/public/assets/img/acompte-paye.png',
        ]);
        $stampSoldeFs = $this->findFirstFile([
            dirname(__DIR__, 2) . '/public/assets/img/Solde-regler.png',
            dirname(__DIR__, 2) . '/public/assets/img/solde-regler.png',
            dirname(__DIR__, 2) . '/public/assets/img/solde-regle.png',
        ]);

        $stampAcompteSrc = $forWeb ? '/public/assets/img/Acompte-Paye.png' : ($stampAcompteFs ?? '');
        $stampSoldeSrc   = $forWeb ? '/public/assets/img/Solde-regler.png'  : ($stampSoldeFs ?? '');
        $signatureSrc    = $forWeb ? $signatureUrl : $signatureFs;

        $items = isset($order['items']) && is_array($order['items']) ? $order['items'] : [];

        // En-tête
        $html  = '<table cellpadding="6" cellspacing="0" border="0" width="100%"><tr><td width="65%">';
        if ($logoSrc !== '' && is_file($logoSrc)) {
            $html .= '<img src="' . $logoSrc . '" width="72"><br>';
        }
        $html .= '<span style="font-size:24px;font-weight:bold;color:#1a2347;">SAZULIS</span><br>';
        $html .= '<span style="color:#5e6b84;">Document client</span>';
        $html .= '</td><td width="35%" align="right"><span style="font-size:18px;font-weight:bold;">' . $title . '</span><br>Ref: ' . $orderRef . '<br>Date: ' . htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') . '</td></tr></table>';
        $html .= '<hr style="border:0;border-top:2px solid #1a2347;">';

        // Infos client / commande
        $html .= '<table cellpadding="8" cellspacing="0" border="1" width="100%" style="border-color:#e4eaf3;">';
        $html .= '<tr><td width="50%"><b>Client</b><br>' . $customerName . '<br>' . $customerEmail . '<br>' . $customerAddress . '</td>';
        $html .= '<td width="50%"><b>Resume commande</b><br><b>Reference:</b> ' . $orderRef . '<br><b>Statut:</b> ' . $status . '<br><b>Total:</b> ' . $total . ' EUR</td></tr>';
        $html .= '</table>';

        if ($documentType === 'invoice') {
            // Statuts de paiement
            $html .= '<br><h2>Validation de paiement</h2>';
            $html .= '<table cellpadding="6" cellspacing="0" border="1" width="100%" style="border-color:#e4eaf3;border-collapse:collapse;">';
            $html .= '<tr><th align="left">Acompte</th><th align="left">Solde</th></tr>';
            $html .= '<tr><td>' . ($acomptePaye ? '<b style="color:#1a7a4a;">Valide</b>' : 'En attente') . '</td>';
            $html .= '<td>' . ($soldeRegle ? '<b style="color:#1a7a4a;">Valide</b>' : 'En attente') . '</td></tr>';
            $html .= '</table>';

            // Tampons
            if (($acomptePaye && $stampAcompteSrc !== '') || ($soldeRegle && $stampSoldeSrc !== '')) {
                $html .= '<br><table cellpadding="4" cellspacing="0" border="0" width="100%"><tr>';
                if ($acomptePaye && $stampAcompteSrc !== '') {
                    $html .= '<td align="left"><img src="' . htmlspecialchars($stampAcompteSrc, ENT_QUOTES, 'UTF-8') . '" width="90"></td>';
                } else {
                    $html .= '<td></td>';
                }
                if ($soldeRegle && $stampSoldeSrc !== '') {
                    $html .= '<td align="right"><img src="' . htmlspecialchars($stampSoldeSrc, ENT_QUOTES, 'UTF-8') . '" width="90"></td>';
                } else {
                    $html .= '<td></td>';
                }
                $html .= '</tr></table>';
            }

            // Lignes de la facture
            $html .= '<br><h2>Details facture</h2>';
            $html .= '<table cellpadding="6" cellspacing="0" border="1" width="100%" style="border-color:#e4eaf3;border-collapse:collapse;">';
            $html .= '<tr style="background-color:#f8fafc;"><th align="left">Designation</th><th width="60" align="center">Qte</th><th width="90" align="right">PU</th><th width="90" align="right">Total</th></tr>';
            if (empty($items)) {
                $html .= '<tr><td colspan="4">Aucun article trouve</td></tr>';
            } else {
                foreach ($items as $item) {
                    $name      = htmlspecialchars((string) ($item['product_name'] ?? 'Produit'), ENT_QUOTES, 'UTF-8');
                    $unitPrice = number_format((float) ($item['unit_price'] ?? 0), 2, ',', ' ');
                    $quantity  = max(1, (int) ($item['quantity'] ?? 0));
                    $lineTotal = number_format((float) ($item['unit_price'] ?? 0) * $quantity, 2, ',', ' ');
                    $html .= '<tr><td>' . $name . '</td><td align="center">' . $quantity . '</td><td align="right">' . $unitPrice . ' EUR</td><td align="right">' . $lineTotal . ' EUR</td></tr>';
                }
            }
            $html .= '<tr><td colspan="3" align="right"><b>Total TTC</b></td><td align="right"><b>' . $total . ' EUR</b></td></tr>';
            $html .= '</table>';
        } else {
            // Contrat
            $html .= '<br><h2>Conditions contractuelles</h2>';

            // Tableau des prestations depuis order_items
            if (!empty($items)) {
                $html .= '<h2>Detail des prestations</h2>';
                $html .= '<table cellpadding="6" cellspacing="0" border="1" width="100%" style="border-color:#e4eaf3;border-collapse:collapse;">';
                $html .= '<tr style="background-color:#f8fafc;"><th align="left">Designation</th><th align="center">Qte</th><th align="right">PU TTC</th><th align="right">Total</th></tr>';
                $runTotal = 0.0;
                foreach ($items as $it) {
                    $iName = htmlspecialchars((string) ($it['product_name'] ?? 'Produit'), ENT_QUOTES, 'UTF-8');
                    $iQty  = max(1, (int) ($it['quantity'] ?? 1));
                    $iPU   = (float) ($it['unit_price'] ?? 0);
                    $iLT   = $iPU * $iQty;
                    $runTotal += $iLT;
                    $html .= '<tr><td>' . $iName . '</td><td align="center">' . $iQty . '</td><td align="right">' . number_format($iPU, 2, ',', ' ') . ' EUR</td><td align="right">' . number_format($iLT, 2, ',', ' ') . ' EUR</td></tr>';
                }
                $html .= '<tr><td colspan="3" align="right"><b>Total TTC</b></td><td align="right"><b>' . number_format($runTotal, 2, ',', ' ') . ' EUR</b></td></tr>';
                $html .= '</table>';
            }

            $html .= '<ul>';
            $html .= '<li>Commande liee : ' . $orderRef . '</li>';
            $html .= '<li>Statut actuel : ' . $status . '</li>';
            $html .= '<li>Montant convenu : ' . $total . ' EUR</li>';
            $html .= '<li>Delai de livraison ou de realisation selon validation client</li>';
            $html .= '<li>Tout depassement de specification initial fera l\'objet d\'un avenant tarifaire.</li>';
            $html .= '<li>Acompte de 30 % a la commande, solde a la livraison.</li>';
            $html .= '</ul>';

            if ($acomptePaye || $soldeRegle) {
                $html .= '<br><table cellpadding="4" cellspacing="0" border="0" width="100%"><tr>';
                if ($acomptePaye && $stampAcompteSrc !== '') {
                    $html .= '<td align="left"><img src="' . htmlspecialchars($stampAcompteSrc, ENT_QUOTES, 'UTF-8') . '" width="90"></td>';
                } else {
                    $html .= '<td></td>';
                }
                if ($soldeRegle && $stampSoldeSrc !== '') {
                    $html .= '<td align="right"><img src="' . htmlspecialchars($stampSoldeSrc, ENT_QUOTES, 'UTF-8') . '" width="90"></td>';
                } else {
                    $html .= '<td></td>';
                }
                $html .= '</tr></table>';
            }
        }

        // Zone signatures
        $html .= '<br><table cellpadding="10" cellspacing="0" border="0" width="100%">';
        $html .= '<tr>';
        $html .= '<td width="48%" style="border:1px solid #e4eaf3;min-height:70px;"><b>Sazulis</b><br><br><br><hr></td>';
        $html .= '<td width="4%"></td>';
        $html .= '<td width="48%" style="border:1px solid #e4eaf3;min-height:70px;"><b>Client</b><br>';
        if ($signatureSrc !== '') {
            $html .= '<img src="' . htmlspecialchars($signatureSrc, ENT_QUOTES, 'UTF-8') . '" width="130"><br>';
        }
        $html .= '<br><hr></td>';
        $html .= '</tr></table>';

        return [
            'html'     => $html,
            'title'    => $title,
            'label'    => $label,
            'filename' => $filename,
        ];
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
            $order = $this->loadOrderForDocument($pdo, $orderId, $user);
        } catch (\Throwable) {
            http_response_code(500);
            echo 'Impossible de charger le document';
            return;
        }

        if ($order === null) {
            http_response_code(404);
            echo 'Commande introuvable';
            return;
        }

        if (!$this->ensureTcpdfLoaded()) {
            http_response_code(500);
            echo 'TCPDF introuvable dans sazulis-php. Installez TCPDF via Composer sur l\'hebergement.';
            return;
        }

        if (ob_get_level() > 0) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }
        ob_start();

        if ($documentType === 'contract') {
            $this->buildContractPdf($order);
        } else {
            $this->buildInvoicePdf($order);
        }
    }

    // -----------------------------------------------------------------------
    // PDF: helper to avoid page-break inside a logical block
    // -----------------------------------------------------------------------
    private static function writeBlock(\TCPDF $pdf, string $html): void
    {
        $startPage = $pdf->getPage();
        $pdf->startTransaction();
        $pdf->writeHTML($html, true, false, true, false, '');
        $endPage = $pdf->getPage();

        if ($endPage > $startPage) {
            $pdf->rollbackTransaction(true);
            $pdf->AddPage();

            $startPage2 = $pdf->getPage();
            $pdf->startTransaction();
            $pdf->writeHTML($html, true, false, true, false, '');
            $endPage2 = $pdf->getPage();

            if ($endPage2 > $startPage2) {
                $pdf->rollbackTransaction(true);
                $pdf->writeHTML($html, true, false, true, false, '');
            } else {
                $pdf->commitTransaction();
            }
        } else {
            $pdf->commitTransaction();
        }
    }

    // -----------------------------------------------------------------------
    // PDF: CONTRAT — version autonome pour sazulis-php
    // -----------------------------------------------------------------------
    private function buildContractPdf(array $order): void
    {
        $base        = dirname(__DIR__, 2);
        $assetsDir = $base . '/public/assets/img';
        $logoPath  = $this->findFirstFile([$assetsDir . '/sazulis-logo1.png']);
        $stampPath = $this->findFirstFile([$assetsDir . '/Sazulis-tampon0000.png', $assetsDir . '/Sazulis-tampon.png']);

        $clientSignaturePath = trim((string) ($order['client_signature_path'] ?? ''));
        $signatureFs = '';
        if ($clientSignaturePath !== '') {
            $candidate = $base . '/' . ltrim($clientSignaturePath, '/');
            if (is_file($candidate)) {
                $signatureFs = $candidate;
            }
        }

        $brandName    = 'Sazulis';
        $brandEmail   = 'contact@sazulis.fr';
        $brandWebsite = 'sazulis.fr';
        $brandSiret   = 'SIREN/SIRET : 752 628 040 00020';
        $brandAddress = '1 Residence les Fallieres, 88380 ARCHES';
        $brandLegal   = 'Micro-entreprise (auto-entrepreneur)';
        $brandCity    = 'Tribunal de EPINAL';

        $today          = (new \DateTimeImmutable('now'))->format('d/m/Y');
        $orderId        = (int) $order['id'];
        $contractNumber = 'CT-' . date('Y') . '-' . str_pad((string) $orderId, 6, '0', STR_PAD_LEFT);
        $orderRef       = htmlspecialchars((string) ($order['order_ref'] ?? ''), ENT_QUOTES, 'UTF-8');
        $status         = htmlspecialchars(ucfirst((string) ($order['status'] ?? 'pending')), ENT_QUOTES, 'UTF-8');
        $avancement     = match (strtolower((string) ($order['status'] ?? ''))) {
            'completed', 'paid' => 100,
            'shipped' => 80,
            'processing' => 40,
            default => 0,
        };
        $items   = isset($order['items']) && is_array($order['items']) ? $order['items'] : [];
        $total   = 0.0;
        foreach ($items as $item) {
            $total += (float) ($item['unit_price'] ?? 0) * max(1, (int) ($item['quantity'] ?? 1));
        }
        if ($total === 0.0) {
            $total = (float) ($order['total'] ?? 0);
        }
        $acompte = round($total * 0.30, 2);
        $solde   = round($total - $acompte, 2);

        $createdAt = (string) ($order['created_at'] ?? '');
        $dateShort = $createdAt !== '' ? substr($createdAt, 0, 10) : date('Y-m-d');

        $clientNom     = htmlspecialchars((string) ($order['customer_name'] ?? 'Client'), ENT_QUOTES, 'UTF-8');
        $clientEmail   = htmlspecialchars((string) ($order['customer_email'] ?? ''), ENT_QUOTES, 'UTF-8');
        $clientAdresse = htmlspecialchars((string) ($order['customer_address'] ?? ''), ENT_QUOTES, 'UTF-8');

        $gold  = '#D8B35A';
        $gold2 = '#E7CF9C';
        $dark  = '#1F1F1F';
        $muted = '#6B5A33';

        // --- Setup PDF ---
        $pdf = new SazulisContratPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->logoPath = (string) ($logoPath ?? '');
        $pdf->SetCreator('Sazulis');
        $pdf->SetAuthor('Sazulis');
        $pdf->SetTitle('Contrat - ' . $orderRef);
        $pdf->SetMargins(14, 16, 14);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();

        // --- Header box ---
        $pageW   = $pdf->getPageWidth();
        $marginL = 14;
        $boxX    = $marginL;
        $boxY    = 12;
        $boxW    = $pageW - ($marginL * 2);
        $boxH    = 32;

        $pdf->SetFillColor(251, 250, 247);
        $pdf->SetDrawColor(231, 207, 156);
        $pdf->RoundedRect($boxX, $boxY, $boxW, $boxH, 4, '1111', 'DF');

        if ($logoPath && is_file($logoPath)) {
            $pdf->Image($logoPath, $boxX + $boxW - 30, $boxY + 5, 22, 0, '', '', '', false, 300, '', false, false, 0, false, false, false);
        }

        $pdf->SetTextColor(216, 179, 90);
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->SetXY($boxX + 8, $boxY + 6);
        $pdf->Cell($boxW - 40, 8, $brandName . ' \u{2022} Contrat de prestation', 0, 2, 'L', false);

        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(107, 90, 51);
        $pdf->SetX($boxX + 8);
        $pdf->Cell($boxW - 16, 6, 'Contrat n\u{00B0} ' . $contractNumber . ' \u{2014} Genere le ' . $today, 0, 2, 'L', false);

        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(31, 31, 31);
        $pdf->SetX($boxX + 8);
        $pdf->MultiCell($boxW - 16, 6, 'Reference : ' . $orderRef . '    Statut : ' . $status . '    Avancement : ' . $avancement . '%', 0, 'L', false, 1);
        $pdf->Ln(4);

        // --- CSS pour les sections HTML ---
        $css = <<<CSS
<style>
.wrap{font-family:helvetica;color:{$dark};}
.section{margin-top:10px;border:1px solid {$gold2};border-radius:12px;padding:10px;background:#fff;}
.h{font-size:12.5px;font-weight:bold;color:{$muted};margin-bottom:6px;}
.small{font-size:9.5px;color:#666;}
.two{width:100%;}
.col{width:48%;display:inline-block;vertical-align:top;}
.right{margin-left:4%;}
.table{width:100%;border-collapse:collapse;margin-top:6px;}
.table th{background:{$gold};color:#fff;padding:7px;text-align:left;font-size:9.5px;text-transform:uppercase;letter-spacing:.4px;}
.table td{border-bottom:1px solid #eee;padding:7px;font-size:9.8px;}
ul{margin:0;padding-left:16px;}
li{margin-bottom:3px;}
.sign{margin-top:12px;border:1px dashed {$gold2};border-radius:12px;padding:10px;background:#fff;}
.sigbox{width:48%;display:inline-block;vertical-align:top;}
.sigline{margin-top:24px;border-top:1px solid #bbb;width:95%;}
.hint{color:#777;font-size:9px;margin-top:6px;}
</style>
CSS;

        $prestBloc  = '<b>' . $brandName . '</b><br><b>Statut :</b> ' . $brandLegal . '<br><b>' . $brandSiret . '</b><br><b>' . $brandAddress . '</b><br><b>Email :</b> ' . $brandEmail . '<br><b>Site :</b> ' . $brandWebsite;
        $clientBloc = '<b>Client :</b> ' . $clientNom . '<br><b>Adresse :</b> ' . $clientAdresse . '<br><b>Email :</b> ' . $clientEmail;
        $iTotal   = number_format($total, 2, ',', ' ') . ' EUR';
        $iAcompte = number_format($acompte, 2, ',', ' ') . ' EUR';
        $iSolde   = number_format($solde, 2, ',', ' ') . ' EUR';

        // 1) Parties
        self::writeBlock($pdf, $css . '<div class="wrap"><div class="section"><div class="h">1) Parties</div><div class="two"><div class="col">' . $prestBloc . '<br><span class="small">Ci-apres "le Prestataire".</span></div><div class="col right">' . $clientBloc . '<br><span class="small">Ci-apres "le Client".</span></div></div></div></div>');

        // 2) Objet
        self::writeBlock($pdf, $css . '<div class="wrap"><div class="section"><div class="h">2) Objet</div>Le present contrat encadre la prestation de developpement web relative a la commande <b>' . $orderRef . '</b>, incluant la conception, le developpement, les corrections raisonnables et la livraison.</div></div>');

        // 3) Perimetre
        self::writeBlock($pdf, $css . '<div class="wrap"><div class="section"><div class="h">3) Perimetre, livrables &amp; hors perimetre</div><ul><li>Le perimetre est celui defini par la commande / devis accepte.</li><li>Toute demande non prevue constitue du <b>hors perimetre</b> et fera l\'objet d\'un devis/avenant.</li><li>Les livrables peuvent inclure : code source, interface, back-office, mise en ligne, documentation courte selon l\'offre.</li></ul></div></div>');

        // 4) Recette
        self::writeBlock($pdf, $css . '<div class="wrap"><div class="section"><div class="h">4) Recette, validations &amp; acceptation</div><ul><li>Une phase de <b>recette</b> est prevue : le Client verifie les livrables et remonte les anomalies sous 7 jours.</li><li>A defaut de retour sous 7 jours, les livrables sont reputes <b>acceptes</b>.</li><li>Les corrections couvrent les bugs et ecarts au perimetre, pas les changements d\'avis.</li></ul></div></div>');

        // 5) Prix – tableau des lignes produits depuis order_items
        $itemsDetailHtml = '';
        if (!empty($items)) {
            $itemsDetailHtml .= '<br><table class="table"><thead><tr><th>Designation</th><th>Qte</th><th align="right">PU TTC</th><th align="right">Total</th></tr></thead><tbody>';
            foreach ($items as $item) {
                $iName = htmlspecialchars((string) ($item['product_name'] ?? 'Produit'), ENT_QUOTES, 'UTF-8');
                $iQty  = max(1, (int) ($item['quantity'] ?? 1));
                $iPU   = (float) ($item['unit_price'] ?? 0);
                $iLT   = $iPU * $iQty;
                $itemsDetailHtml .= '<tr><td>' . $iName . '</td><td>' . $iQty . '</td><td align="right">' . number_format($iPU, 2, ',', ' ') . ' EUR</td><td align="right">' . number_format($iLT, 2, ',', ' ') . ' EUR</td></tr>';
            }
            $itemsDetailHtml .= '<tr><td colspan="3" align="right"><b>Total TTC</b></td><td align="right"><b>' . $iTotal . '</b></td></tr>';
            $itemsDetailHtml .= '</tbody></table>';
        }
        self::writeBlock($pdf, $css . '<div class="wrap"><div class="section"><div class="h">5) Prix, paiement &amp; suspension</div>' . $itemsDetailHtml . '<br><table class="table"><thead><tr><th>Element</th><th>Montant</th><th>Modalite</th></tr></thead><tbody>' . ($itemsDetailHtml === '' ? '<tr><td>Total prestation</td><td><b>' . $iTotal . '</b></td><td>Selon commande</td></tr>' : '') . '<tr><td>Acompte (30%)</td><td><b>' . $iAcompte . '</b></td><td>Avant demarrage</td></tr><tr><td>Solde (70%)</td><td><b>' . $iSolde . '</b></td><td>Avant livraison</td></tr></tbody></table><ul><li>Le demarrage est conditionne au paiement de l\'acompte.</li><li>L\'acompte est non remboursable apres 7 jours suivant son paiement.</li><li>En cas de retard, le Prestataire peut <b>suspendre</b> la prestation.</li></ul></div></div>');

        // 6) Obligations client
        self::writeBlock($pdf, $css . '<div class="wrap"><div class="section"><div class="h">6) Obligations du Client</div><ul><li>Le Client fournit textes, images, logos, acces dans des delais raisonnables.</li><li>Le Prestataire n\'est pas responsable des retards dus a l\'absence de contenus ou d\'acces.</li><li>Le Client garantit disposer des droits sur les contenus fournis.</li></ul></div></div>');

        // 7) Hebergement
        self::writeBlock($pdf, $css . '<div class="wrap"><div class="section"><div class="h">7) Hebergement, services tiers &amp; securite</div><ul><li>Les services tiers restent sous leurs propres conditions.</li><li>Le Prestataire ne peut etre tenu responsable d\'une panne causee par un tiers.</li><li>Le Client doit gerer ses propres acces et mots de passe.</li></ul></div></div>');

        // 8) PI
        self::writeBlock($pdf, $css . '<div class="wrap"><div class="section"><div class="h">8) Propriete intellectuelle</div><ul><li>Les livrables deviennent la propriete du Client apres paiement integral.</li><li>Le Prestataire conserve la propriete de ses outils et composants generiques.</li><li>Sauf opposition, le Prestataire peut citer le projet comme reference (portfolio).</li></ul></div></div>');

        // 9) Maintenance
        self::writeBlock($pdf, $css . '<div class="wrap"><div class="section"><div class="h">9) Maintenance, support &amp; evolutions</div><ul><li>Sauf mention contraire, la maintenance n\'est pas incluse dans la prestation initiale.</li><li>Les demandes d\'evolution feront l\'objet d\'un devis/forfait.</li><li>Les mises a jour peuvent necessiter des interventions facturables si non incluses.</li></ul></div></div>');

        // 10) Responsabilites
        self::writeBlock($pdf, $css . '<div class="wrap"><div class="section"><div class="h">10) Responsabilites &amp; limitation</div><ul><li>Le Prestataire est tenu a une obligation de moyens.</li><li>Le Prestataire ne saurait etre responsable des pertes indirectes.</li><li>En cas de dommage prouve, l\'indemnisation est limitee au <b>montant effectivement paye</b>.</li></ul></div></div>');

        // 11) Resiliation
        self::writeBlock($pdf, $css . '<div class="wrap"><div class="section"><div class="h">11) Resiliation</div><ul><li>Resiliation possible en cas de manquement grave apres mise en demeure restee sans effet 15 jours.</li><li>Si le Client met fin au projet, les travaux realises restent dus et facturables au prorata.</li></ul></div></div>');

        // 12) Droit
        self::writeBlock($pdf, $css . '<div class="wrap"><div class="section"><div class="h">12) Droit applicable &amp; litiges</div><ul><li>Les parties privilegient une resolution amiable.</li><li>A defaut, les tribunaux competents seront ceux du ressort du Prestataire : <b>' . htmlspecialchars($brandCity, ENT_QUOTES, 'UTF-8') . '</b>.</li></ul></div></div>');

        // Signatures block
        $sigHtml = $css . '<div class="wrap"><div class="sign"><div class="h">Signatures</div>'
            . '<div class="sigbox"><b>Le Prestataire</b><br>' . $brandName . '<div class="sigline"></div><span class="small">Signature / Cachet</span></div>'
            . '<div class="sigbox" style="margin-left:4%;"><b>Le Client</b><br>' . $clientNom
            . '<div class="sigline"></div>'
            . '<span class="small" style="color:#f00;font-weight:bold;text-transform:uppercase;">Le signataire reconnait avoir lu le present contrat et declare en accepter expressement l\'ensemble des clauses, sans reserve.</span>'
            . '<div class="hint">Si vous avez signe en ligne, votre signature apparaitra ci-dessus.</div>'
            . '</div>'
            . '<div style="margin-top:8px" class="small">Date de la commande : ' . htmlspecialchars($dateShort, ENT_QUOTES, 'UTF-8') . ' — Document genere le ' . $today . '</div>'
            . '</div></div>';
        self::writeBlock($pdf, $sigHtml);

        // Tampon prestataire (absolu, derniere page)
        if ($stampPath !== null && is_file($stampPath)) {
            $last  = $pdf->getNumPages();
            $pdf->setPage($last);
            $pageH = $pdf->getPageHeight();
            $stampY = max(20.0, $pageH - 137.8);
            $pdf->SetAlpha(0.90);
            $pdf->Image($stampPath, 28.0, $stampY, 25.0, 0, 'PNG', '', '', false, 300, '', false, false, 0, false, false, false);
            $pdf->SetAlpha(1.0);
        }

        // Signature client (absolue, derniere page)
        if ($signatureFs !== '' && is_file($signatureFs)) {
            $last  = $pdf->getNumPages();
            $pdf->setPage($last);
            $pageH = $pdf->getPageHeight();
            $sigY  = max(20.0, $pageH - 137.8);
            $pdf->Image($signatureFs, 125.0, $sigY, 55.0, 0, 'PNG', '', '', false, 300, '', false, false, 0, false, false, false);
        }

        $fileName = 'Contrat_' . preg_replace('/[^a-zA-Z0-9_-]+/', '_', $contractNumber) . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        $pdf->Output($fileName, 'I');
        exit;
    }

    // -----------------------------------------------------------------------
    // PDF: FACTURE — version autonome pour sazulis-php
    // -----------------------------------------------------------------------
    private function buildInvoicePdf(array $order): void
    {
        $base        = dirname(__DIR__, 2);
        $assetsDir    = $base . '/public/assets/img';
        $logoPath     = $this->findFirstFile([$assetsDir . '/sazulis-logo1.png']);
        $stampPath    = $this->findFirstFile([$assetsDir . '/Sazulis-tampon0000.png', $assetsDir . '/Sazulis-tampon.png']);
        $stampAcompte = $this->findFirstFile([
            $base . '/public/assets/img/Acompte-Paye.png',
            $base . '/public/assets/img/acompte-paye.png',
        ]);
        $stampSolde = $this->findFirstFile([
            $base . '/public/assets/img/Solde-regler.png',
            $base . '/public/assets/img/solde-regler.png',
            $base . '/public/assets/img/solde-regle.png',
        ]);

        $clientSignaturePath = trim((string) ($order['client_signature_path'] ?? ''));
        $signatureFs = '';
        if ($clientSignaturePath !== '') {
            $candidate = $base . '/' . ltrim($clientSignaturePath, '/');
            if (is_file($candidate)) {
                $signatureFs = $candidate;
            }
        }

        $acomptePaye = ((int) ($order['acompte_paye'] ?? 0) === 1);
        $soldeRegle  = ((int) ($order['solde_regle'] ?? 0) === 1);

        $orderRef    = (string) ($order['order_ref'] ?? ('F-' . (string) $order['id']));
        $createdAt   = (string) ($order['created_at'] ?? '');
        $dateFacture = $createdAt !== ''
            ? (new \DateTimeImmutable($createdAt))->format('d/m/Y')
            : (new \DateTimeImmutable('now'))->format('d/m/Y');

        $clientNom     = (string) ($order['customer_name'] ?? 'Client');
        $clientEmail   = (string) ($order['customer_email'] ?? '');
        $clientAdresse = (string) ($order['customer_address'] ?? '');

        $items = isset($order['items']) && is_array($order['items']) ? $order['items'] : [];
        $total = 0.0;
        foreach ($items as $item) {
            $total += (float) ($item['unit_price'] ?? 0) * max(1, (int) ($item['quantity'] ?? 1));
        }
        if ($total === 0.0) {
            $total = (float) ($order['total'] ?? 0);
        }

        $acompteAmt  = round($total * 0.30, 2);
        $resteAPayer = $soldeRegle ? 0.0 : ($acomptePaye ? round($total * 0.70, 2) : $total);

        $entreprise = ['nom' => 'SAZULIS', 'adresse' => '1 Residence les Fallieres, 88380 Arches', 'email' => 'contact@sazulis.fr', 'tel' => '06 98 76 67 80'];

        // --- Custom TCPDF class ---
        $numeroGlobal = $orderRef;
        $dateGlobal   = $dateFacture;

        $pdf = new SazulisFacturePDF();
        $pdf->logoPath   = (string) ($logoPath ?? '');
        $pdf->numFacture = $numeroGlobal;
        $pdf->dateFact   = $dateGlobal;
        $pdf->SetCreator('SAZULIS');
        $pdf->SetAuthor('SAZULIS');
        $pdf->SetTitle('Facture ' . $orderRef);
        $pdf->SetMargins(15, 55, 15);
        $pdf->SetAutoPageBreak(true, 35);
        $pdf->AddPage();

        // Filigrane (grand, centré)
        if ($logoPath && is_file($logoPath)) {
            $pageW  = $pdf->getPageWidth();
            $pageH  = $pdf->getPageHeight();
            $imgW   = $pageW - 30;
            $pdf->SetAlpha(0.18);
            $pdf->Image($logoPath, ($pageW - $imgW) / 2, ($pageH / 2) - ($imgW / 2), $imgW, 0, '');
            $pdf->SetAlpha(1.0);
        }

        // Blocs Emetteur / Client
        $pdf->Ln(4);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetTextColor(40, 40, 40);
        $pdf->Cell(90, 6, 'Emetteur', 0, 0);
        $pdf->Cell(0, 6, 'Client', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(60, 60, 60);
        $pdf->MultiCell(90, 6, $entreprise['nom'] . "\n" . $entreprise['adresse'] . "\n" . $entreprise['email'] . "\n" . $entreprise['tel'], 0, 'L', false, 0);

        $blocClient = $clientNom . "\n";
        if ($clientAdresse !== '') {
            $blocClient .= $clientAdresse . "\n";
        }
        $blocClient .= $clientEmail;
        $pdf->MultiCell(0, 6, $blocClient, 0, 'L');
        $pdf->Ln(10);

        // Tableau produits (HTML)
        $htmlTable = '<table cellpadding="8"><thead><tr style="background-color:#E6C77A;color:#000;"><th width="70%">Designation</th><th width="30%" align="right">Prix</th></tr></thead><tbody>';
        if (empty($items)) {
            $htmlTable .= '<tr><td>Prestation - ' . htmlspecialchars($orderRef, ENT_QUOTES, 'UTF-8') . '</td><td align="right">' . number_format($total, 2, ',', ' ') . ' EUR</td></tr>';
        } else {
            foreach ($items as $item) {
                $name      = htmlspecialchars((string) ($item['product_name'] ?? 'Produit'), ENT_QUOTES, 'UTF-8');
                $unitPrice = (float) ($item['unit_price'] ?? 0);
                $qty       = max(1, (int) ($item['quantity'] ?? 1));
                $lineTotal = $unitPrice * $qty;
                $htmlTable .= '<tr><td>' . $name . ($qty > 1 ? ' x' . $qty : '') . '</td><td align="right">' . number_format($lineTotal, 2, ',', ' ') . ' EUR</td></tr>';
            }
        }
        $htmlTable .= '</tbody></table>';
        $pdf->writeHTML($htmlTable, true, false, true, false, '');
        $pdf->Ln(5);

        // Totaux
        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetTextColor(40, 40, 40);
        $pdf->Cell(130, 8, 'Total HT', 0);
        $pdf->Cell(40, 8, number_format($total, 2, ',', ' ') . ' EUR', 0, 1, 'R');

        if ($acomptePaye) {
            $pdf->Cell(130, 8, 'Acompte regle (30%)', 0);
            $pdf->Cell(40, 8, '-' . number_format($acompteAmt, 2, ',', ' ') . ' EUR', 0, 1, 'R');
        }

        $pdf->SetFont('helvetica', 'B', 13);
        $pdf->SetTextColor(180, 40, 40);
        $pdf->Cell(130, 10, $soldeRegle ? 'SOLDE - FACTURE REGLEE' : 'RESTE A PAYER', 0);
        $pdf->Cell(40, 10, number_format($resteAPayer, 2, ',', ' ') . ' EUR', 0, 1, 'R');

        // Note pro
        $pdf->SetTextColor(80, 80, 80);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Ln(6);
        $pdf->MultiCell(0, 6, "Merci pour votre confiance.\nConditions : acompte a la commande - solde exigible a la livraison.\nContact : " . $entreprise['email'] . ' / ' . $entreprise['tel'] . '.', 0, 'C');

        // Bloc bas : tampons + signatures
        $blockH   = 78;
        $blockOff = 12;
        $pageH    = $pdf->getPageHeight();
        $breakM   = $pdf->getBreakMargin();
        $yTop     = $pageH - $breakM - $blockH - $blockOff;

        if ($pdf->GetY() > ($yTop - 5)) {
            $pdf->AddPage();
            $pdf->Ln(10);
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->SetTextColor(40, 40, 40);
            $pdf->Cell(0, 8, 'VALIDATION DE PAIEMENT', 0, 1, 'C');
            $pdf->Ln(4);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetTextColor(80, 80, 80);
            $pdf->MultiCell(0, 6, "Ce document atteste du reglement des sommes dues.\nLe paiement de l'acompte et du solde valide la realisation des prestations.", 0, 'C');
            $pageH = $pdf->getPageHeight();
            $breakM = $pdf->getBreakMargin();
            $yTop   = $pageH - $breakM - $blockH - $blockOff;
        }

        $pdf->SetY($yTop);

        // Tampons centrés
        $tamponW   = 32;
        $tamponSpc = 18;
        $pageW     = $pdf->getPageWidth();
        $xStart    = ($pageW - ($tamponW * 2 + $tamponSpc)) / 2;

        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(60, 60, 60);
        $pdf->Cell(0, 6, 'Statut de paiement', 0, 1, 'C');
        $yTampon = $pdf->GetY();

        if ($acomptePaye && $stampAcompte !== null && is_file($stampAcompte)) {
            $pdf->Image($stampAcompte, $xStart, $yTampon, $tamponW, 0, '');
        }
        if ($soldeRegle && $stampSolde !== null && is_file($stampSolde)) {
            $pdf->Image($stampSolde, $xStart + $tamponW + $tamponSpc, $yTampon, $tamponW, 0, '');
        }

        // Lignes signature
        $ySig = $yTampon + $tamponW + 5;
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(40, 40, 40);
        $pdf->SetXY(20, $ySig);
        $pdf->Cell(60, 8, 'Prestataire', 0, 0, 'C');
        $pdf->SetXY(110, $ySig);
        $pdf->Cell(80, 8, 'Client', 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->SetXY(20, $ySig + 8);
        $pdf->Cell(80, 6, 'Signature et tampon prestataire', 0, 0, 'C');
        $pdf->SetXY(110, $ySig + 8);
        $pdf->Cell(80, 6, 'Signature client', 0, 1, 'C');

        // Tampon prestataire (absolu)
        if ($stampPath !== null && is_file($stampPath)) {
            $pdf->Image($stampPath, 25, $ySig + 15, 26, 0, '');
        }

        // Signature client (absolue)
        if ($signatureFs !== '' && is_file($signatureFs)) {
            $pdf->Image($signatureFs, 132, $ySig + 15, 40, 0, '');
        }

        $fileName = 'Facture_' . preg_replace('/[^a-zA-Z0-9_-]+/', '_', $orderRef) . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        $pdf->Output($fileName, 'I');
        exit;
    }

    public function documentPreview(string $orderId, string $type): void
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
            $order = $this->loadOrderForDocument($pdo, $orderId, $user);
        } catch (\Throwable) {
            http_response_code(500);
            echo 'Impossible de charger le document';
            return;
        }

        if ($order === null) {
            http_response_code(404);
            echo 'Commande introuvable';
            return;
        }

        $doc = $this->buildDocumentHtml($order, $documentType, true);

        $appConfig = require dirname(__DIR__, 2) . '/config/app.php';
        $basePath  = rtrim((string) (parse_url((string) ($appConfig['base_url'] ?? ''), PHP_URL_PATH) ?: ''), '/');

        $label        = $doc['label'];
        $orderRef     = htmlspecialchars((string) ($order['order_ref'] ?? ''), ENT_QUOTES, 'UTF-8');
        $downloadUrl  = htmlspecialchars($basePath . '/profile/documents/' . (int) $orderId . '/' . $documentType, ENT_QUOTES, 'UTF-8');
        $profileUrl   = htmlspecialchars($basePath . '/profile', ENT_QUOTES, 'UTF-8');

        header('Content-Type: text/html; charset=UTF-8');
        echo <<<HTML
<!doctype html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$label} {$orderRef} - Sazulis</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    body {
      font-family: 'Helvetica Neue', Arial, sans-serif;
      font-size: 13px;
      color: #1a2347;
      background: #f4f6fa;
      margin: 0;
      padding: 0;
    }
    .preview-toolbar {
      position: sticky;
      top: 0;
      z-index: 100;
      background: #1a2347;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 22px;
      gap: 12px;
      flex-wrap: wrap;
      print-color-adjust: exact;
    }
    .preview-toolbar span { font-size: 0.95rem; font-weight: 600; }
    .preview-toolbar-actions { display: flex; gap: 10px; flex-wrap: wrap; }
    .btn-toolbar {
      border: 0;
      border-radius: 6px;
      padding: 8px 16px;
      font-size: 0.88rem;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
    }
    .btn-toolbar-primary { background: #ff7a45; color: #fff; }
    .btn-toolbar-ghost { background: rgba(255,255,255,0.12); color: #fff; border: 1px solid rgba(255,255,255,0.3); }
    .preview-page {
      background: #fff;
      width: min(794px, 98vw);
      margin: 28px auto 48px;
      padding: 40px 48px;
      box-shadow: 0 4px 32px rgba(0,0,0,0.13);
      border-radius: 4px;
    }
    h2 { font-size: 1rem; margin: 18px 0 8px; color: #1a2347; }
    table { border-collapse: collapse; font-size: 12px; }
    hr { margin: 14px 0; }
    @media print {
      .preview-toolbar { display: none !important; }
      body { background: #fff; }
      .preview-page { box-shadow: none; margin: 0; padding: 20px; }
    }
  </style>
</head>
<body>
  <div class="preview-toolbar">
    <span>{$label} &mdash; {$orderRef}</span>
    <div class="preview-toolbar-actions">
      <button class="btn-toolbar btn-toolbar-ghost" onclick="window.print()">Imprimer</button>
      <a class="btn-toolbar btn-toolbar-primary" href="{$downloadUrl}">Telecharger le PDF</a>
      <a class="btn-toolbar btn-toolbar-ghost" href="{$profileUrl}">&larr; Retour profil</a>
    </div>
  </div>
  <div class="preview-page">
    {$doc['html']}
  </div>
</body>
</html>
HTML;
        exit;
    }
}

// ---------------------------------------------------------------------------
// TCPDF subclass: contrat avec filigrane tuilé + pagination
// ---------------------------------------------------------------------------
class SazulisContratPDF extends \TCPDF
{
    public string $logoPath = '';
    public float  $wmAlpha  = 0.14;

    public function Header(): void
    {
        if ($this->logoPath === '' || !is_file($this->logoPath)) {
            return;
        }
        $pageW = $this->getPageWidth();
        $pageH = $this->getPageHeight();
        $this->SetAlpha($this->wmAlpha);
        $tileW = 60; $stepX = 78; $stepY = 58;
        $this->StartTransform();
        $this->Rotate(25, $pageW / 2, $pageH / 2);
        for ($yy = -40; $yy < $pageH + 80; $yy += $stepY) {
            for ($xx = -40; $xx < $pageW + 80; $xx += $stepX) {
                $this->Image($this->logoPath, $xx, $yy, $tileW, 0, '', '', '', false, 300, '', false, false, 0, false, false, false);
            }
        }
        $this->StopTransform();
        $this->SetAlpha(1.0);
    }

    public function Footer(): void
    {
        $this->SetY(-15);
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

// ---------------------------------------------------------------------------
// TCPDF subclass: facture avec header logo + footer ligne dorée
// ---------------------------------------------------------------------------
class SazulisFacturePDF extends \TCPDF
{
    public string $logoPath   = '';
    public string $numFacture = '';
    public string $dateFact   = '';

    public function Header(): void
    {
        if ($this->logoPath !== '' && is_file($this->logoPath)) {
            $this->Image($this->logoPath, 15, 15, 20, 0, '');
        }
        $this->SetXY(40, 15);
        $this->SetFont('helvetica', 'B', 22);
        $this->SetTextColor(40, 40, 40);
        $this->Cell(0, 18, 'FACTURE', 0, 1, 'L');
        $this->Ln(2);
        $this->SetFont('helvetica', '', 11);
        $this->Cell(0, 6, 'Numero : ' . $this->numFacture, 0, 1);
        $this->Cell(0, 6, 'Date : ' . $this->dateFact, 0, 1);
        $this->Ln(6);
    }

    public function Footer(): void
    {
        $this->SetY(-25);
        $this->SetDrawColor(230, 199, 122);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->Ln(5);
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(140, 140, 140);
        $this->Cell(0, 6, 'Societe SAZULIS - SIREN 752 628 040 00020', 0, 0, 'C');
    }
}
