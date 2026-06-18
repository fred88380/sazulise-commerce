# Sazulis - Checklist de Sécurité Complète

## ✅ Implémentations de Sécurité Achevées

### 1. Anti-Phishing
- [x] Validation d'email stricte (`SecurityValidator::sanitizeEmail()`)
  - Vérification format RFC 5322
  - Rejet emails locaux (localhost, 127.0.0.1)
  - Validation domaine DNS
  
- [x] Détection domaines suspects
- [x] Vérification HTTPS obligatoire en production
- [x] Headers X-Frame-Options: DENY (clickjacking)

### 2. Anti-Injection (SQL, XSS)
- [x] **SQL Injection**: 
  - Parameterized queries (PDO prepared statements)
  - Pattern detection (`detectSqlInjection()`)
  - Sanitization SQL dangerous characters
  
- [x] **XSS (Cross-Site Scripting)**:
  - `sanitizeText()` - Strip tags & htmlspecialchars
  - `sanitizeHtml()` - Double filtering
  - Pattern detection (`isXssSuspicious()`)
  - CSP (Content Security Policy) header strict
  
- [x] **Path Traversal**:
  - `sanitizePath()` - Remove ../ sequences
  - Validation filename upload

### 3. Anti-Insulte
- [x] Base de données de mots offensants
  - 50+ mots français/anglais
  - Détection variantes (typos, caractères spéciaux)
  - Levenshtein distance matching
  
- [x] Logging violations
- [x] Blocking content automatic

### 4. Anti-Racisme
- [x] Base de données termes racistes
  - 80+ mots français/anglais
  - Détection multi-langue
  - Blocking CRITIQUE
  
- [x] Alert administrator

### 5. Anti-Sexuel & Genre
- [x] Base de données termes sexuels
  - 40+ mots français/anglais
  - Détection contenu explicite
  
- [x] Validation champs utilisateur
  - Username validation
  - Display name validation
  - Biography validation

### 6. Authentification & Session
- [x] Password hashing (`password_hash()`)
  - SHA2-512/bcrypt
  - 12+ caractères requis
  - Majuscule + minuscule + chiffre + spécial
  
- [x] Rate limiting login (7 tentatives/900s)
- [x] CAPTCHA image generation
- [x] 2FA TOTP (Time-based One-Time Password)
- [x] Session fixation prevention
- [x] Session IP validation
- [x] Session expiration (1h)
- [x] Session regeneration auto (30min)

### 7. CSRF Protection
- [x] CSRF tokens (`Csrf::validateRequest()`)
- [x] SameSite=Strict cookie
- [x] Token validation on POST

### 8. Headers HTTP Sécurité
- [x] `X-Content-Type-Options: nosniff`
- [x] `X-Frame-Options: DENY`
- [x] `X-XSS-Protection: 1; mode=block`
- [x] `Content-Security-Policy` (strict)
- [x] `Strict-Transport-Security` (1 an)
- [x] `Referrer-Policy: strict-origin-when-cross-origin`
- [x] `Permissions-Policy` (géolocalisation, microphone, caméra = disabled)

### 9. Upload Fichier Sécurité
- [x] MIME type validation
- [x] File size limits
- [x] Filename sanitization
- [x] No executable uploads
- [x] Virus scan ready

### 10. Logging & Monitoring
- [x] Security event logging
- [x] Content moderation logging
- [x] IP address tracking
- [x] User agent logging
- [x] Suspicious activity detection

## 🚀 Comment Utiliser

### Dans un Contrôleur

```php
<?php
use App\Core\SecurityValidator;
use App\Core\ContentModeration;
use App\Core\Csrf;

public function storeComment(): void {
    // 1. Valider CSRF
    if (!Csrf::validateRequest()) {
        http_response_code(403);
        return;
    }
    
    // 2. Récupérer et sanitizer
    $comment = SecurityValidator::sanitizeText($_POST['comment'] ?? '', 500);
    $email = SecurityValidator::sanitizeEmail($_POST['email'] ?? '');
    
    // 3. Vérifier SQL injection
    if (SecurityValidator::detectSqlInjection($_POST['comment'] ?? '')) {
        $this->error('Contenu suspect détecté');
        return;
    }
    
    // 4. Vérifier XSS
    if (SecurityValidator::isXssSuspicious($_POST['comment'] ?? '')) {
        $this->error('Code HTML détecté');
        return;
    }
    
    // 5. Scanner contenu offensant
    $moderation = ContentModeration::scan($comment);
    if (!$moderation['is_clean']) {
        ContentModeration::logViolation($comment, $moderation['violations'], $userId, 'comment');
        $this->error('Contenu inapproprié: ' . implode(', ', $moderation['violations']));
        return;
    }
    
    // 6. Sauvegarder en base (avec PDO prepared)
    $stmt = $pdo->prepare('INSERT INTO comments (user_id, content, email) VALUES (:id, :content, :email)');
    $stmt->execute([
        'id' => $userId,
        'content' => $comment,
        'email' => $email
    ]);
}
```

### Pour Valider Formulaire Utilisateur

```php
// Nom d'utilisateur
$usernameErrors = ContentModeration::validateUsername($_POST['username']);
if (!empty($usernameErrors)) {
    $this->error(implode(', ', $usernameErrors));
}

// Nom complet
$nameErrors = ContentModeration::validateDisplayName($_POST['name']);
if (!empty($nameErrors)) {
    $this->error(implode(', ', $nameErrors));
}

// Biographie
$bioErrors = ContentModeration::validateBiography($_POST['bio']);
if (!empty($bioErrors)) {
    $this->error(implode(', ', $bioErrors));
}
```

### Pour Upload Fichier

```php
$errors = SecurityValidator::validateFileUpload(
    $_FILES['avatar'],
    ['image/jpeg', 'image/png', 'image/gif'],
    5242880 // 5MB
);

if (!empty($errors)) {
    $this->error(implode(', ', $errors));
    return;
}

// Sanitizer filename
$filename = SecurityValidator::sanitizeFilename($_FILES['avatar']['name']);
move_uploaded_file($_FILES['avatar']['tmp_name'], '/uploads/' . $filename);
```

## 📊 Logs & Monitoring

### Fichier de Log
```
logs/content_moderation.log
```

### Format
```json
{
  "timestamp": "2024-01-15 14:32:45",
  "user_id": "123",
  "context": "comment",
  "violations": ["offensive_language", "racism"],
  "severity": "critical",
  "ip": "192.168.1.1",
  "user_agent": "Mozilla/5.0..."
}
```

## 🔐 Configuration PHP Recommandée

```ini
; php.ini
display_errors = Off
log_errors = On
error_log = /var/log/php-errors.log

; Disable dangerous functions
disable_functions = passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source,exec

; Security
memory_limit = 128M
max_execution_time = 30
upload_max_filesize = 10M
post_max_size = 10M

; Sessions
session.use_strict_mode = 1
session.use_only_cookies = 1
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = Strict
session.gc_maxlifetime = 3600
session.sid_length = 64
session.sid_bits_per_character = 6
```

## 🛡️ Nginx Configuration

```nginx
server {
    listen 443 ssl http2;
    server_name example.com;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    
    # Security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "geolocation=(), microphone=(), camera=()";
    
    # Disable TRACE method
    if ($request_method = TRACE) {
        return 405;
    }
    
    # Limit request size
    client_max_body_size 10M;
    
    location / {
        root /var/www/sazulis-php/public;
        try_files $uri /index.php$is_args$args;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name example.com;
    return 301 https://$server_name$request_uri;
}
```

## 🔄 Maintenance de Sécurité

### Hebdomadaire
- [ ] Vérifier logs de modération
- [ ] Vérifier patterns d'attaque
- [ ] Mettre à jour listes de mots-clés

### Mensuel
- [ ] Audit des sessions longue durée
- [ ] Vérifier compliance RGPD
- [ ] Mettre à jour dépendances PHP

### Trimestriel
- [ ] Test de pénétration interne
- [ ] Audit complet sécurité
- [ ] Mise à jour certificats SSL

## 🚨 Incident Response

1. **Détection**: Vérifier logs
2. **Blocage**: Désactiver compte utilisateur
3. **Quarantine**: Isoler données suspectes
4. **Notification**: Contacter sazulis@outlook.fr
5. **Investigation**: Analyser logs + backups
6. **Recovery**: Restaurer depuis backup propre

## 📞 Support Sécurité

- **Email**: security@sazulis.fr
- **Urgent**: admin@sazulis.fr
- **Documentation**: Ce fichier

---

**Version**: 1.0  
**Date**: 2024-01-15  
**Auteur**: Security Team
