# 🔒 Guide de Sécurité Sazulis - Implémentation Complète

## Classes Disponibles

### 1. **SecurityValidator** (`App\Core\SecurityValidator`)
Valide et nettoie toutes les entrées utilisateur.

```php
// Sanitization
$email = SecurityValidator::sanitizeEmail($_POST['email']);
$text = SecurityValidator::sanitizeText($_POST['comment'], 500);
$html = SecurityValidator::sanitizeHtml($userContent);

// Validation
$errors = SecurityValidator::validatePassword($password);
$isXSS = SecurityValidator::isXssSuspicious($userInput);
$isSQLi = SecurityValidator::detectSqlInjection($input);

// Fichiers
$errors = SecurityValidator::validateFileUpload($_FILES['avatar'], ['image/jpeg', 'image/png'], 5242880);
```

### 2. **ContentModeration** (`App\Core\ContentModeration`)
Détecte et prévient les contenus offensants.

```php
// Scan complet
$result = ContentModeration::scan($userText);
// Retourne: ['is_clean' => bool, 'violations' => [], 'severity' => '']

// Vérifications individuelles
if (!ContentModeration::isSafe($comment)) {
    // Bloquer ou modérer
}

// Validation de champs utilisateur
$nameErrors = ContentModeration::validateDisplayName($name);
$bioErrors = ContentModeration::validateBiography($bio);
$usernameErrors = ContentModeration::validateUsername($username);

// Logging
ContentModeration::logViolation($text, ['violation'], $userId, 'context');
```

### 3. **SecurityHeaders** (`App\Core\SecurityHeaders`)
Configure les headers HTTP de sécurité.

```php
// À appeler au début de index.php
SecurityHeaders::setSecurityHeaders();
SecurityHeaders::configureSession();

// Avant les opérations sensibles
if (!SecurityHeaders::validateSessionIntegrity()) {
    die('Session compromise détectée');
}

// Régénérer les sessions anciennes
SecurityHeaders::regenerateSessionIfOld(1800);
```

## ⚠️ Patterns de Sécurité à Respecter

### Anti-Phishing
```php
// ✅ BON
$email = SecurityValidator::sanitizeEmail($_POST['email']);
if ($email === null) {
    throw new Exception('Email invalide');
}

// ❌ MAUVAIS
$email = $_POST['email'];
$pdo->execute(['email' => $email]); // SQL injection!
```

### Anti-XSS
```php
// ✅ BON
$comment = SecurityValidator::sanitizeText($_POST['comment']);
echo htmlspecialchars($comment, ENT_QUOTES, 'UTF-8');

// ❌ MAUVAIS
echo $_POST['comment']; // XSS!
```

### Anti-SQL Injection
```php
// ✅ BON
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
$stmt->execute(['email' => $sanitizedEmail]);

// ❌ MAUVAIS
$query = "SELECT * FROM users WHERE email = '" . $email . "'";
$pdo->query($query); // SQL injection!
```

### Anti-Insulte & Racisme
```php
// ✅ BON
$comment = SecurityValidator::sanitizeText($_POST['comment']);
$violations = ContentModeration::getViolations($comment);
if (!empty($violations)) {
    $_SESSION['error'] = 'Contenu inapproprié détecté';
    return;
}

// ❌ MAUVAIS
echo $_POST['comment']; // Pas de filtrage!
```

### Anti-Contenu Sexuel
```php
// ✅ BON
if (!ContentModeration::isSafe($bio)) {
    ContentModeration::logViolation($bio, ['contenu_sexuel'], $userId, 'profile_update');
    throw new Exception('Bio contient du contenu inapproprié');
}

// ❌ MAUVAIS
$user['bio'] = $_POST['bio']; // Pas de vérification!
```

## 🔑 Points d'Entrée Critiques

### Formulaires (TOUJOURS valider)
```php
- Login/Register: SecurityValidator::sanitizeEmail() + ContentModeration::validateDisplayName()
- Commentaires: SecurityValidator::sanitizeText() + ContentModeration::scan()
- Profil: ContentModeration::validateDisplayName(), validateBiography()
- Uploads: SecurityValidator::validateFileUpload()
```

### Vérifications Obligatoires
```php
1. CSRF: Csrf::validateRequest()
2. Rate Limiting: RateLimiter::tooManyAttempts()
3. SQL Injection: SecurityValidator::detectSqlInjection()
4. XSS: SecurityValidator::isXssSuspicious()
5. Contenu: ContentModeration::scan()
```

## 📋 Checklist pour Chaque Endpoint

- [ ] CSRF token validé (`Csrf::validateRequest()`)
- [ ] Rate limiting appliqué (`RateLimiter::`)
- [ ] Entrées sanitizées (`SecurityValidator::`)
- [ ] Contenu modéré (`ContentModeration::`)
- [ ] SQL Injection testée (`detectSqlInjection()`)
- [ ] XSS testée (`isXssSuspicious()`)
- [ ] Headers sécurité appliqués (`SecurityHeaders::`)

## 🚨 Signatures d'Attaque à Surveiller

### SQL Injection
```
UNION SELECT, INSERT INTO, DROP TABLE, exec(), --, /*, */
```

### XSS
```
<script>, <iframe>, javascript:, data:, on*=, <%
```

### Phishing
```
localhost, 127.0.0.1, email invalide, domaine suspect
```

### Offensive
```
Voir OFFENSIVE_WORDS dans ContentModeration
```

## 🛡️ Headers HTTP Appliqués

```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Content-Security-Policy: [restrictive]
Strict-Transport-Security: [1 an]
Referrer-Policy: strict-origin-when-cross-origin
```

## 📊 Logs

Tous les incidents sont loggés dans:
```
sazulis-php/logs/content_moderation.log
```

Format JSON avec:
- Timestamp
- User ID
- Violations
- IP Address
- User Agent
- Context

## 💡 Recommandations

1. **Mises à jour régulières** des listes de mots-clés offensants
2. **Audit** hebdomadaire des logs de modération
3. **Test** régulier des injections (SQL, XSS)
4. **Rotation** des session IDs toutes les 30 minutes
5. **Backups** quotidiens des logs de sécurité
6. **Alertes** en cas de multiples violations

## 🔍 Commandes de Test

```bash
# Tester XSS
curl -X POST https://example.com/register -d "name=<script>alert(1)</script>"

# Tester SQL Injection
curl -X POST https://example.com/login -d "email=admin' OR '1'='1"

# Tester Rate Limiting
for i in {1..10}; do
  curl -X POST https://example.com/login -d "email=test&password=wrong"
done

# Tester CSRF
curl -X POST https://example.com/profile -H "Cookie: PHPSESSID=..."
# (sans CSRF token)
```

## 📞 Support

En cas de violation de sécurité détectée:
1. Vérifier les logs
2. Contacter l'administrateur
3. Bloquer le compte utilisateur si nécessaire
4. Reporter à contact@sazulis.fr
