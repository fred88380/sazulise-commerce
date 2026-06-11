<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

final class AuthController extends Controller
{
    public function loginForm(): void
    {
        $this->render('auth/login', [
            'metaTitle' => 'Connexion - Sazulis',
            'error' => $_SESSION['auth_error'] ?? null,
        ]);
        unset($_SESSION['auth_error']);
    }

    public function registerForm(): void
    {
        $this->render('auth/register', [
            'metaTitle' => 'Inscription - Sazulis',
            'error' => $_SESSION['auth_error'] ?? null,
        ]);
        unset($_SESSION['auth_error']);
    }

    public function login(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $normalizedEmail = mb_strtolower($email);
        $adminEmail = 'sazulis@outlook.fr';

        if ($email === '' || $password === '') {
            $_SESSION['auth_error'] = 'Email et mot de passe requis.';
            $this->redirect('/login');
        }

        $pdo = Database::getConnection();
        if ($pdo === null) {
            $_SESSION['auth_error'] = 'Base de donnees indisponible.';
            $this->redirect('/login');
        }

        $stmt = $pdo->prepare('SELECT id, full_name, email, password_hash, role FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, (string) ($user['password_hash'] ?? ''))) {
            $_SESSION['auth_error'] = 'Identifiants invalides.';
            $this->redirect('/login');
        }

        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => (string) $user['full_name'],
            'email' => (string) $user['email'],
            'role' => ((string) ($user['role'] ?? 'client') === 'admin' || $normalizedEmail === $adminEmail) ? 'admin' : 'client',
        ];

        if ($normalizedEmail === $adminEmail) {
            $this->redirect('/admin');
        }

        $this->redirect('/checkout');
    }

    public function register(): void
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($name === '' || $email === '' || strlen($password) < 8) {
            $_SESSION['auth_error'] = 'Nom, email et mot de passe de 8 caracteres minimum requis.';
            $this->redirect('/register');
        }

        $pdo = Database::getConnection();
        if ($pdo === null) {
            $_SESSION['auth_error'] = 'Base de donnees indisponible.';
            $this->redirect('/register');
        }

        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $check->execute(['email' => $email]);
        if ($check->fetch()) {
            $_SESSION['auth_error'] = 'Cet email existe deja.';
            $this->redirect('/register');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (:name, :email, :hash, :role)');
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'hash' => $hash,
            'role' => 'client',
        ]);

        $_SESSION['user'] = [
            'id' => (int) $pdo->lastInsertId(),
            'name' => $name,
            'email' => $email,
            'role' => 'client',
        ];

        $this->redirect('/checkout');
    }

    public function adminLoginForm(): void
    {
        $this->redirect('/login');
    }

    public function adminLogin(): void
    {
        $this->redirect('/login');
    }

    public function logout(): void
    {
        unset($_SESSION['user']);
        $this->redirect('/');
    }
}
