<?php
/**
 * Fichier d'exemple: Comment intégrer la sécurité dans vos contrôleurs
 *
 * Ceci montre les patterns à utiliser dans TOUS les contrôleurs pour garantir
 * une sécurité maximale contre le phishing, injections, insultes et contenu offensant.
 */

use App\Core\SecurityValidator;
use App\Core\ContentModeration;
use App\Core\Csrf;
use App\Core\RateLimiter;

/**
 * EXEMPLE 1: Formulaire de Contact (Sécurité Complète)
 */
class ContactControllerExample
{
    public function submit(): void
    {
        // 1. CSRF Protection
        if (!Csrf::validateRequest()) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid request']);
            return;
        }

        // 2. Rate Limiting (anti-spam)
        $throttleKey = 'contact:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        if (RateLimiter::tooManyAttempts($throttleKey, 5, 3600)) {
            http_response_code(429);
            echo json_encode(['error' => 'Trop de soumissions. Réessaie plus tard.']);
            return;
        }

        // 3. Récupérer et valider données
        $name = SecurityValidator::sanitizeText($_POST['name'] ?? '', 100);
        $email = SecurityValidator::sanitizeEmail($_POST['email'] ?? '');
        $subject = SecurityValidator::sanitizeText($_POST['subject'] ?? '', 200);
        $message = SecurityValidator::sanitizeText($_POST['message'] ?? '', 5000);

        $errors = [];

        // 4. Validation sécurité
        if ($email === null) {
            $errors[] = 'Email invalide';
        }

        if (empty($name) || strlen($name) < 2) {
            $errors[] = 'Nom requis (min 2 caractères)';
        }

        // 5. Détection injection SQL/XSS
        if (SecurityValidator::detectSqlInjection($_POST['message'] ?? '')) {
            ContentModeration::logViolation($_POST['message'] ?? '', ['sql_injection'], 'anonymous', 'contact');
            $errors[] = 'Requête suspecte détectée';
        }

        if (SecurityValidator::isXssSuspicious($_POST['message'] ?? '')) {
            ContentModeration::logViolation($_POST['message'] ?? '', ['xss_attempt'], 'anonymous', 'contact');
            $errors[] = 'Code HTML détecté';
        }

        // 6. Scan contenu offensant
        $moderation = ContentModeration::scan($message);
        if (!$moderation['is_clean']) {
            ContentModeration::logViolation($message, $moderation['violations'], 'anonymous', 'contact');
            if ($moderation['severity'] === 'critical') {
                $errors[] = 'Contenu offensant ou raciste détecté';
            } elseif ($moderation['severity'] === 'high') {
                $errors[] = 'Contenu inapproprié détecté';
            }
        }

        if (!empty($errors)) {
            RateLimiter::hit($throttleKey, 3600);
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            return;
        }

        // 7. Envoyer email de contact (email est safe)
        // ... email code ...

        RateLimiter::clear($throttleKey);
        echo json_encode(['success' => 'Message envoyé avec succès']);
    }
}

/**
 * EXEMPLE 2: Mise à jour Profil Utilisateur
 */
class ProfileControllerExample
{
    public function updateProfile(): void
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            http_response_code(401);
            return;
        }

        if (!Csrf::validateRequest()) {
            http_response_code(403);
            return;
        }

        $userId = (int) $user['id'];

        // 1. Valider et sanitizer champs utilisateur
        $displayName = SecurityValidator::sanitizeText($_POST['display_name'] ?? '', 64);
        $bio = SecurityValidator::sanitizeText($_POST['bio'] ?? '', 500);
        $phone = SecurityValidator::sanitizePhoneNumber($_POST['phone'] ?? '');

        // 2. Valider avec les règles de modération
        $nameErrors = ContentModeration::validateDisplayName($displayName);
        $bioErrors = ContentModeration::validateBiography($bio);

        if (!empty($nameErrors) || !empty($bioErrors)) {
            echo json_encode(['errors' => array_merge($nameErrors, $bioErrors)]);
            return;
        }

        // 3. Vérifier injection dans d'autres champs
        if (SecurityValidator::detectSqlInjection($_POST['display_name'] ?? '') ||
            SecurityValidator::detectSqlInjection($_POST['bio'] ?? '')) {
            ContentModeration::logViolation(
                json_encode($_POST),
                ['sql_injection_attempt'],
                (string) $userId,
                'profile_update'
            );
            http_response_code(400);
            return;
        }

        // 4. Sauvegarder (avec prepared statements)
        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->prepare('
            UPDATE users
            SET display_name = :name, bio = :bio, phone = :phone, updated_at = NOW()
            WHERE id = :id
        ');
        $stmt->execute([
            'name' => $displayName,
            'bio' => $bio,
            'phone' => $phone,
            'id' => $userId
        ]);

        ContentModeration::logViolation(
            'Profile updated',
            [],
            (string) $userId,
            'profile_update'
        );

        echo json_encode(['success' => 'Profil mis à jour']);
    }
}

/**
 * EXEMPLE 3: Upload Avatar (Sécurité Fichier)
 */
class AvatarControllerExample
{
    public function upload(): void
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            http_response_code(401);
            return;
        }

        if (!isset($_FILES['avatar'])) {
            http_response_code(400);
            return;
        }

        $userId = (int) $user['id'];

        // 1. Valider fichier upload
        $errors = SecurityValidator::validateFileUpload(
            $_FILES['avatar'],
            ['image/jpeg', 'image/png', 'image/webp'],
            5242880 // 5MB
        );

        if (!empty($errors)) {
            echo json_encode(['errors' => $errors]);
            return;
        }

        // 2. Sanitizer filename
        $originalName = SecurityValidator::sanitizeFilename($_FILES['avatar']['name']);
        $safeFilename = uniqid('avatar_' . $userId . '_') . '.' . pathinfo($originalName, PATHINFO_EXTENSION);

        // 3. Déplacer fichier vers dossier sécurisé
        $uploadDir = dirname(__DIR__) . '/public/uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0750, true);
        }

        $fullPath = $uploadDir . $safeFilename;

        if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $fullPath)) {
            http_response_code(500);
            echo json_encode(['error' => 'Upload failed']);
            return;
        }

        // 4. Sauvegarder path en base
        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->prepare('UPDATE users SET avatar_path = :path WHERE id = :id');
        $stmt->execute([
            'path' => '/public/uploads/avatars/' . $safeFilename,
            'id' => $userId
        ]);

        ContentModeration::logViolation('Avatar uploaded', [], (string) $userId, 'avatar_upload');
        echo json_encode(['success' => 'Avatar uploadé', 'path' => '/public/uploads/avatars/' . $safeFilename]);
    }
}

/**
 * EXEMPLE 4: Création Commentaire (Modération Stricte)
 */
class CommentControllerExample
{
    public function create(): void
    {
        if (!Csrf::validateRequest()) {
            http_response_code(403);
            return;
        }

        $user = $_SESSION['user'] ?? null;
        $userId = $user ? (int) $user['id'] : 0;

        $comment = SecurityValidator::sanitizeText($_POST['content'] ?? '', 2000);

        // 1. XSS Check
        if (SecurityValidator::isXssSuspicious($_POST['content'] ?? '')) {
            ContentModeration::logViolation(
                $_POST['content'] ?? '',
                ['xss_attempt'],
                (string) $userId,
                'comment_creation'
            );
            http_response_code(400);
            echo json_encode(['error' => 'Code HTML non autorisé']);
            return;
        }

        // 2. Scan contenu complet
        $moderation = ContentModeration::scan($comment);

        if (!$moderation['is_clean']) {
            ContentModeration::logViolation(
                $comment,
                $moderation['violations'],
                (string) $userId,
                'comment_creation'
            );

            if ($moderation['severity'] === 'critical') {
                http_response_code(403);
                echo json_encode(['error' => 'Contenu offensant ou raciste détecté']);
                return;
            }

            if ($moderation['severity'] === 'high') {
                $comment = ContentModeration::sanitizeContent($comment);
            }
        }

        // 3. Sauvegarder
        $pdo = \App\Core\Database::getConnection();
        $stmt = $pdo->prepare('
            INSERT INTO comments (user_id, post_id, content, created_at)
            VALUES (:user_id, :post_id, :content, NOW())
        ');

        $stmt->execute([
            'user_id' => $userId,
            'post_id' => (int) ($_POST['post_id'] ?? 0),
            'content' => $comment
        ]);

        echo json_encode(['success' => 'Commentaire créé']);
    }
}

/**
 * EXEMPLE 5: Détection d'Attaque
 */
class SecurityMonitorExample
{
    public static function checkSuspiciousActivity(): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        // 1. Vérifier IP de session
        if (isset($_SESSION['__session_ip']) && $_SESSION['__session_ip'] !== $ip) {
            error_log('[SECURITY] Session hijacking attempt from ' . $ip);
            return false;
        }

        // 2. Vérifier User Agent
        if (isset($_SESSION['__session_ua']) && $_SESSION['__session_ua'] !== $ua) {
            error_log('[SECURITY] User agent mismatch from ' . $ip);
            return false;
        }

        // 3. Vérifier pattern d'attaque
        if (self::isAttackPattern($_SERVER['REQUEST_URI'] ?? '/')) {
            ContentModeration::logViolation(
                $_SERVER['REQUEST_URI'] ?? '',
                ['attack_pattern_detected'],
                (string) ($_SESSION['user']['id'] ?? 'unknown'),
                'attack_detection'
            );
            return false;
        }

        return true;
    }

    private static function isAttackPattern(string $uri): bool
    {
        $attackPatterns = [
            '/\.\.\//',           // Path traversal
            '/;.*\?/',            // SQL injection
            '/\<.*script.*\>/i',  // XSS
            '/union.*select/i',   // SQL injection
            '/exec\(/i',          // Code execution
            '/eval\(/i',          // Code execution
            '/system\(/i',        // Command injection
        ];

        foreach ($attackPatterns as $pattern) {
            if (preg_match($pattern, $uri)) {
                return true;
            }
        }

        return false;
    }
}

/**
 * Résumé des Points de Sécurité
 */
?>
<!--
## CHECKLIST à copier dans chaque contrôleur:

1. ✅ CSRF Protection
   - if (!Csrf::validateRequest()) { error }

2. ✅ Authentification
   - Vérifier $_SESSION['user']

3. ✅ Sanitization
   - SecurityValidator::sanitizeText()
   - SecurityValidator::sanitizeEmail()
   - SecurityValidator::sanitizePath()

4. ✅ Detection Injection
   - SecurityValidator::detectSqlInjection()
   - SecurityValidator::isXssSuspicious()

5. ✅ Modération Contenu
   - ContentModeration::scan()
   - ContentModeration::validateDisplayName()

6. ✅ Rate Limiting
   - RateLimiter::tooManyAttempts()
   - RateLimiter::hit()

7. ✅ Logging
   - ContentModeration::logViolation()

8. ✅ Prepared Statements
   - $pdo->prepare() + execute()
   - JAMAIS de string concatenation

9. ✅ Input Validation
   - Vérifier types et longueurs
   - Whitelist plutôt que blacklist

10. ✅ Error Handling
    - http_response_code()
    - json_encode() pour API
    - Ne pas exposer erreurs techniques
-->
