<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

final class ApiController extends Controller
{
    public function products(): void
    {
        require_once __DIR__ . '/../Repositories/ProductRepository.php';
        $repoClass = '\\App\\Repositories\\ProductRepository';
        $repo = new $repoClass();
        $this->json(['items' => $repo->all()]);
    }

    public function createOrder(): void
    {
        $input = json_decode((string) file_get_contents('php://input'), true) ?: [];

        $sessionUser = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
        $email = trim((string) ($input['email'] ?? ($sessionUser['email'] ?? '')));
        $name = trim((string) ($input['name'] ?? ($sessionUser['name'] ?? '')));
        $address = trim((string) ($input['address'] ?? ''));
        $cart = $input['cart'] ?? [];

        if ($email === '' || $name === '' || $address === '' || !is_array($cart) || empty($cart)) {
            $this->json(['ok' => false, 'message' => 'Invalid payload'], 422);
            return;
        }

        $orderId = 'SAZ-' . strtoupper(bin2hex(random_bytes(4)));
        $total = 0.0;
        foreach ($cart as $item) {
            $price = (float) ($item['price'] ?? 0);
            $qty = max(1, (int) ($item['qty'] ?? 1));
            $total += $price * $qty;
        }

        $pdo = Database::getConnection();
        if ($pdo !== null) {
            try {
                $pdo->beginTransaction();

                try {
                    $stmt = $pdo->prepare('INSERT INTO orders (order_ref, user_id, customer_email, customer_name, customer_address, total, status) VALUES (:ref, :user_id, :email, :name, :address, :total, :status)');
                    $stmt->execute([
                        'ref' => $orderId,
                        'user_id' => isset($sessionUser['id']) ? (int) $sessionUser['id'] : null,
                        'email' => $email,
                        'name' => $name,
                        'address' => $address,
                        'total' => $total,
                        'status' => 'pending',
                    ]);
                } catch (\Throwable) {
                    $stmt = $pdo->prepare('INSERT INTO orders (order_ref, customer_email, customer_name, customer_address, total, status) VALUES (:ref, :email, :name, :address, :total, :status)');
                    $stmt->execute([
                        'ref' => $orderId,
                        'email' => $email,
                        'name' => $name,
                        'address' => $address,
                        'total' => $total,
                        'status' => 'pending',
                    ]);
                }

                $orderPk = (int) $pdo->lastInsertId();
                $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_name, unit_price, quantity) VALUES (:order_id, :product_name, :unit_price, :quantity)');
                foreach ($cart as $item) {
                    $itemStmt->execute([
                        'order_id' => $orderPk,
                        'product_name' => (string) ($item['name'] ?? 'Produit'),
                        'unit_price' => (float) ($item['price'] ?? 0),
                        'quantity' => max(1, (int) ($item['qty'] ?? 1)),
                    ]);
                }

                $pdo->commit();
            } catch (\Throwable) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $this->json(['ok' => false, 'message' => 'Erreur creation commande'], 500);
                return;
            }
        }

        $this->json([
            'ok' => true,
            'message' => 'Order created',
            'orderId' => $orderId,
            'eta' => '48h',
            'total' => round($total, 2),
        ], 201);
    }
}
