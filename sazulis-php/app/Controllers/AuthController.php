<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ContentModeration;
use App\Core\Controller;
use App\Core\Database;
use App\Core\RateLimiter;
use App\Core\SecurityValidator;
use App\Core\Totp;

final class AuthController extends Controller
{
    private function consume2faOrigin(): string
    {
        $origin = (string) ($_SESSION['auth_2fa_origin'] ?? 'register');
        unset($_SESSION['auth_2fa_origin']);

        return $origin === 'profile' ? '/profile' : '/checkout';
    }

    private function loginThrottleKey(string $email): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        return 'login:' . sha1($ip . '|' . mb_strtolower($email));
    }

    private function generateCaptchaCode(int $length = 6): string
    {
        $pool = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789@#%&!?';
        $max = strlen($pool) - 1;
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $pool[random_int(0, $max)];
        }

        return $code;
    }

    private function freshCaptcha(): string
    {
        $newCode = $this->generateCaptchaCode();
        $_SESSION['auth_captcha_code'] = $newCode;
        return $newCode;
    }

    private function currentCaptcha(): string
    {
        $existing = (string) ($_SESSION['auth_captcha_code'] ?? '');
        if ($existing !== '') {
            return $existing;
        }

        return $this->freshCaptcha();
    }

    private function canRenderCaptchaImage(): bool
    {
        return function_exists('imagecreatetruecolor')
            && function_exists('imagecolorallocate')
            && function_exists('imagefill')
            && function_exists('imagestring')
            && function_exists('imagepng');
    }

    private function outputCaptchaImage(string $code): bool
    {
        if (!$this->canRenderCaptchaImage()) {
            return false;
        }

        $width = 210;
        $height = 68;
        $img = imagecreatetruecolor($width, $height);
        if ($img === false) {
            return false;
        }

        $bg = imagecolorallocate($img, 10, 20, 34);
        $fg = imagecolorallocate($img, 255, 216, 77);
        $noiseA = imagecolorallocate($img, 95, 170, 225);
        $noiseB = imagecolorallocate($img, 255, 125, 95);
        $noiseC = imagecolorallocate($img, 120, 220, 160);

        imagefill($img, 0, 0, $bg);

        for ($i = 0; $i < 150; $i++) {
            imagesetpixel($img, random_int(0, $width - 1), random_int(0, $height - 1), ($i % 2 === 0) ? $noiseA : $noiseB);
        }

        for ($i = 0; $i < 6; $i++) {
            imageline(
                $img,
                random_int(0, (int) ($width * 0.35)),
                random_int(0, $height),
                random_int((int) ($width * 0.65), $width),
                random_int(0, $height),
                ($i % 2 === 0) ? $noiseB : $noiseC
            );
        }

        $x = 16;
        for ($i = 0, $len = strlen($code); $i < $len; $i++) {
            $char = $code[$i];
            $y = random_int(18, 30);
            imagestring($img, 5, $x, $y, $char, $fg);
            $x += random_int(28, 32);
        }

        header('Content-Type: image/png');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        imagepng($img);
        imagedestroy($img);
        return true;
    }

    private function consumeCaptchaAndValidate(string $input): bool
    {
        $expected = (string) ($_SESSION['auth_captcha_code'] ?? '');
        unset($_SESSION['auth_captcha_code']);

        if ($expected === '' || $input === '') {
            return false;
        }

        return hash_equals($expected, $input);
    }

    public function captchaImage(): void
    {
        // ?refresh=1 → nouveau code ; sinon → code actuel (pour le premier chargement)
        $refresh = isset($_GET['refresh']) && $_GET['refresh'] === '1';
        $code = $refresh ? $this->freshCaptcha() : $this->currentCaptcha();

        if ($this->outputCaptchaImage($code)) {
            exit;
        }

        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo $code;
        exit;
    }

    public function loginForm(): void
    {
        // Toujours un nouveau captcha a chaque affichage du formulaire
        $this->freshCaptcha();
        $this->render('auth/login', [
            'metaTitle' => 'Connexion - Sazulis',
            'error' => $_SESSION['auth_error'] ?? null,
            'captchaImageUrl' => '/captcha/auth',
        ]);
        unset($_SESSION['auth_error']);
    }

    public function registerForm(): void
    {
        // Toujours un nouveau captcha a chaque affichage du formulaire
        $this->freshCaptcha();
        $this->render('auth/register', [
            'metaTitle' => 'Inscription - Sazulis',
            'error' => $_SESSION['auth_error'] ?? null,
            'captchaImageUrl' => '/captcha/auth',
        ]);
        unset($_SESSION['auth_error']);
    }

    public function login(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $captchaInput = trim((string) ($_POST['captcha_code'] ?? ''));

        $sanitizedEmail = SecurityValidator::sanitizeEmail($email);
        if ($sanitizedEmail === null) {
            $_SESSION['auth_error'] = 'Email invalide.';
            $this->redirect('/login');
        }

        $normalizedEmail = mb_strtolower($sanitizedEmail);
        $adminEmail = 'sazulis@outlook.fr';
        $throttleKey = $this->loginThrottleKey($sanitizedEmail);

        if (RateLimiter::tooManyAttempts($throttleKey, 7, 900)) {
            ContentModeration::logViolation('Login rate limit exceeded', ['trop_de_tentatives'], 'unknown', 'login_attempt');
            $_SESSION['auth_error'] = 'Trop de tentatives. Reessaie dans quelques minutes.';
            $this->redirect('/login');
        }

        if (!$this->consumeCaptchaAndValidate($captchaInput)) {
            RateLimiter::hit($throttleKey, 900);
            $_SESSION['auth_error'] = 'Captcha invalide. Respecte exactement majuscules, minuscules et symboles.';
            $this->redirect('/login');
        }

        if ($sanitizedEmail === '' || $password === '') {
            RateLimiter::hit($throttleKey, 900);
            $_SESSION['auth_error'] = 'Email et mot de passe requis.';
            $this->redirect('/login');
        }

        if (SecurityValidator::detectSqlInjection($password)) {
            RateLimiter::hit($throttleKey, 900);
            ContentModeration::logViolation('SQL injection attempt in login', ['sql_injection'], 'unknown', 'login_attempt');
            $_SESSION['auth_error'] = 'Tentative suspecte détectée.';
            $this->redirect('/login');
        }

        $pdo = Database::getConnection();
        if ($pdo === null) {
            RateLimiter::hit($throttleKey, 900);
            $_SESSION['auth_error'] = 'Base de donnees indisponible.';
            $this->redirect('/login');
        }

        $stmt = $pdo->prepare('SELECT id, full_name, email, password_hash, role, totp_enabled, totp_secret FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $sanitizedEmail]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, (string) ($user['password_hash'] ?? ''))) {
            RateLimiter::hit($throttleKey, 900);
            $_SESSION['auth_error'] = 'Identifiants invalides.';
            $this->redirect('/login');
        }

        RateLimiter::clear($throttleKey);

        $role = ((string) ($user['role'] ?? 'client') === 'admin' || $normalizedEmail === $adminEmail) ? 'admin' : 'client';

        if ((int) ($user['totp_enabled'] ?? 0) === 1) {
            $_SESSION['auth_2fa_pending'] = [
                'id'    => (int) $user['id'],
                'name'  => (string) $user['full_name'],
                'email' => (string) $user['email'],
                'role'  => $role,
                'secret'=> (string) $user['totp_secret'],
                'throttle_key' => $throttleKey,
            ];
            $this->redirect('/2fa/verify');
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'    => (int) $user['id'],
            'name'  => (string) $user['full_name'],
            'email' => (string) $user['email'],
            'role'  => $role,
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
        $captchaInput = trim((string) ($_POST['captcha_code'] ?? ''));

        if (!$this->consumeCaptchaAndValidate($captchaInput)) {
            $_SESSION['auth_error'] = 'Captcha invalide. Respecte exactement majuscules, minuscules et symboles.';
            $this->redirect('/register');
        }

        $errors = [];

        if ($name === '') {
            $errors[] = 'Le nom est requis.';
        } else {
            $nameErrors = ContentModeration::validateDisplayName($name);
            if (!empty($nameErrors)) {
                $errors = array_merge($errors, $nameErrors);
            }
        }

        $sanitizedEmail = SecurityValidator::sanitizeEmail($email);
        if ($sanitizedEmail === null) {
            $errors[] = 'Email invalide ou suspecte.';
        }

        $passwordErrors = SecurityValidator::validatePassword($password);
        if (!empty($passwordErrors)) {
            $errors[] = 'Mot de passe faible. Requis: 12+ caractères, majuscule, minuscule, chiffre, caractère spécial.';
        }

        if (!empty($errors)) {
            $_SESSION['auth_error'] = implode(' | ', $errors);
            $this->redirect('/register');
        }

        if (SecurityValidator::detectSqlInjection($name . ' ' . $email)) {
            $_SESSION['auth_error'] = 'Requête suspecte détectée.';
            $this->redirect('/register');
        }

        $pdo = Database::getConnection();
        if ($pdo === null) {
            $_SESSION['auth_error'] = 'Base de donnees indisponible.';
            $this->redirect('/register');
        }

        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $check->execute(['email' => $sanitizedEmail]);
        if ($check->fetch()) {
            $_SESSION['auth_error'] = 'Cet email existe deja.';
            $this->redirect('/register');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $cleanName = SecurityValidator::sanitizeText($name, 100);
        $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (:name, :email, :hash, :role)');
        $stmt->execute([
            'name' => $cleanName,
            'email' => $sanitizedEmail,
            'hash' => $hash,
            'role' => 'client',
        ]);

        $_SESSION['user'] = [
            'id'    => (int) $pdo->lastInsertId(),
            'name'  => $cleanName,
            'email' => $sanitizedEmail,
            'role'  => 'client',
        ];

        ContentModeration::logViolation('User registered: ' . $sanitizedEmail, [], (string) $_SESSION['user']['id'], 'registration');

        $this->redirect('/2fa/setup?origin=register');
    }

    // -----------------------------------------------------------------------
    // 2FA — configuration (apres inscription ou depuis le profil)
    // -----------------------------------------------------------------------
    public function setup2faForm(): void
    {
        $user = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
        if (!$user) {
            $this->redirect('/login');
        }

        $origin = (string) ($_GET['origin'] ?? '');
        if ($origin === 'profile' || $origin === 'register') {
            $_SESSION['auth_2fa_origin'] = $origin;
        }

        // Generer un secret temporaire (en session, pas encore en BDD)
        if (empty($_SESSION['auth_2fa_setup_secret'])) {
            $_SESSION['auth_2fa_setup_secret'] = Totp::generateSecret();
        }

        $secret = (string) $_SESSION['auth_2fa_setup_secret'];
        $uri    = Totp::provisioningUri($secret, (string) ($user['email'] ?? ''));

        $this->render('auth/2fa-setup', [
            'metaTitle'   => 'Configurer la double authentification - Sazulis',
            'secret'      => $secret,
            'otpauthUri'  => $uri,
            'error'       => $_SESSION['auth_error'] ?? null,
        ]);
        unset($_SESSION['auth_error']);
    }

    public function confirm2fa(): void
    {
        $user = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
        if (!$user) {
            $this->redirect('/login');
        }

        $code   = trim((string) ($_POST['totp_code'] ?? ''));
        $secret = (string) ($_SESSION['auth_2fa_setup_secret'] ?? '');

        if ($secret === '' || !Totp::verify($secret, $code)) {
            $_SESSION['auth_error'] = 'Code invalide. Verifie l\'heure de ton telephone et reessaie.';
            $this->redirect('/2fa/setup');
        }

        $pdo = Database::getConnection();
        if ($pdo !== null) {
            // Ajouter les colonnes si elles n'existent pas encore
            try {
                $pdo->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS totp_secret VARCHAR(64) NULL');
                $pdo->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS totp_enabled TINYINT(1) NOT NULL DEFAULT 0');
            } catch (\Throwable) {}

            $stmt = $pdo->prepare('UPDATE users SET totp_secret = :secret, totp_enabled = 1 WHERE id = :id');
            $stmt->execute(['secret' => $secret, 'id' => (int) ($user['id'] ?? 0)]);
        }

        unset($_SESSION['auth_2fa_setup_secret']);
        $_SESSION['user']['totp_enabled'] = 1;
        $this->redirect($this->consume2faOrigin());
    }

    public function skip2fa(): void
    {
        $user = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
        if (!$user) {
            $this->redirect('/login');
        }
        unset($_SESSION['auth_2fa_setup_secret']);
        $this->redirect($this->consume2faOrigin());
    }

    public function disable2fa(): void
    {
        $user = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
        if (!$user || (($user['role'] ?? 'client') !== 'client')) {
            $this->redirect('/login');
        }

        $password = (string) ($_POST['current_password'] ?? '');
        if ($password === '') {
            $_SESSION['profile_error'] = 'Mot de passe requis pour desactiver la 2FA.';
            $this->redirect('/profile');
        }

        $pdo = Database::getConnection();
        if ($pdo === null) {
            $_SESSION['profile_error'] = 'Base de donnees indisponible.';
            $this->redirect('/profile');
        }

        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int) ($user['id'] ?? 0)]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($password, (string) ($row['password_hash'] ?? ''))) {
            $_SESSION['profile_error'] = 'Mot de passe incorrect. 2FA non modifiee.';
            $this->redirect('/profile');
        }

        $update = $pdo->prepare('UPDATE users SET totp_enabled = 0, totp_secret = NULL WHERE id = :id');
        $update->execute(['id' => (int) ($user['id'] ?? 0)]);

        $_SESSION['user']['totp_enabled'] = 0;
        $_SESSION['profile_notice'] = 'Double authentification desactivee.';
        $this->redirect('/profile');
    }

    // -----------------------------------------------------------------------
    // 2FA — verification a la connexion
    // -----------------------------------------------------------------------
    public function verify2faForm(): void
    {
        if (empty($_SESSION['auth_2fa_pending'])) {
            $this->redirect('/login');
        }

        $this->render('auth/2fa-verify', [
            'metaTitle' => 'Verification 2FA - Sazulis',
            'error'     => $_SESSION['auth_error'] ?? null,
        ]);
        unset($_SESSION['auth_error']);
    }

    public function verify2fa(): void
    {
        $pending = $_SESSION['auth_2fa_pending'] ?? null;
        if (!is_array($pending)) {
            $this->redirect('/login');
        }

        $verifyKey = '2fa:' . sha1((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . (string) ($pending['id'] ?? '0'));
        if (RateLimiter::tooManyAttempts($verifyKey, 8, 900)) {
            $_SESSION['auth_error'] = 'Trop de codes invalides. Reessaie dans quelques minutes.';
            $this->redirect('/2fa/verify');
        }

        $code   = trim((string) ($_POST['totp_code'] ?? ''));
        $secret = (string) ($pending['secret'] ?? '');

        if ($secret === '' || !Totp::verify($secret, $code)) {
            RateLimiter::hit($verifyKey, 900);
            $_SESSION['auth_error'] = 'Code 2FA invalide ou expire. Reessaie.';
            $this->redirect('/2fa/verify');
        }

        RateLimiter::clear($verifyKey);
        if (!empty($pending['throttle_key']) && is_string($pending['throttle_key'])) {
            RateLimiter::clear($pending['throttle_key']);
        }

        unset($_SESSION['auth_2fa_pending']);
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'    => (int) ($pending['id']    ?? 0),
            'name'  => (string) ($pending['name']  ?? ''),
            'email' => (string) ($pending['email'] ?? ''),
            'role'  => (string) ($pending['role']  ?? 'client'),
        ];

        if ((string) ($pending['role'] ?? '') === 'admin') {
            $this->redirect('/admin');
        }

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
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'] ?? '/', $params['domain'] ?? '', (bool) ($params['secure'] ?? false), (bool) ($params['httponly'] ?? true));
        }
        session_destroy();
        $this->redirect('/');
    }
}
