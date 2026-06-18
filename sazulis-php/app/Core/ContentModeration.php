<?php

declare(strict_types=1);

namespace App\Core;

final class ContentModeration
{
    private const OFFENSIVE_WORDS = [
        'putain', 'salaud', 'connard', 'débile', 'idiot', 'imbécile',
        'con', 'bite', 'couille', 'chier', 'merde', 'enculé',
        'fumier', 'pourri', 'nul', 'naze', 'pourrave', 'salop',
        'ordure', 'porc', 'charogne', 'racaille', 'pourritue',
        'pédé', 'pd', 'tapette', 'gouine', 'tantouse',
        'fétard', 'drogué', 'alcoolique', 'clochard', 'pauvre type',
        'bastard', 'asshole', 'damn', 'crap', 'hell', 'bitch',
        'fuck', 'shit', 'piss', 'cock', 'dick', 'pussy', 'ass',
        'whore', 'slut', 'retard', 'idiocy'
    ];

    private const RACIST_WORDS = [
        'négro', 'nègre', 'arabe', 'chelou', 'chelou', 'bicot',
        'bougre', 'youpin', 'juif', 'islamiste', 'terroriste',
        'chinetoque', 'ricain', 'british', 'rosbif', 'boche',
        'schleu', 'gypsie', 'gitans', 'clandestin', 'sans-papier',
        'intégriste', 'sectaire', 'racaille', 'toubib', 'bamboulés',
        'caïd', 'macaque', 'babouins', 'singes', 'mongol',
        'nigger', 'nigga', 'monkey', 'paki', 'terrorist',
        'illegal', 'invader', 'vermin', 'scum', 'subhuman',
        'race traitor', 'race mixing', 'miscegenation',
        'chinois', 'indous', 'négresse', 'beurette', 'maghrébine'
    ];

    private const SEXUAL_WORDS = [
        'cul', 'fesses', 'seins', 'nichon', 'poitrine', 'teton',
        'vagin', 'cunt', 'dick', 'cock', 'penis', 'dildo',
        'sexe', 'éjaculation', 'orgasme', 'sperm', 'semen',
        'menstruations', 'règles', 'tampons', 'serviettes hygiéniques',
        'porno', 'porno', 'xxx', 'adult', 'nude', 'naked',
        'sex', 'fuck', 'cum', 'suck', 'blow', 'penetration',
        'prostitué', 'pute', 'escort', 'travesti', 'transexuel',
        'gay', 'lesbienne', 'LGBT', 'queer', 'drag queen',
        'butch', 'femme', 'topless', 'fessier', 'exhibitionniste'
    ];

    private const GENDER_SPECIFIC_WORDS = [
        'féminazi', 'masculiniste', 'SJW', 'woke', 'cancel',
        'white privilege', 'manspreading', 'mansplaining',
        'toxic masculinity', 'rape culture', 'patriarcat',
        'matriarch', 'misandry', 'misogyny', 'sexiste',
        'machiste', 'incel', 'femcel', 'red pill', 'blue pill'
    ];

    private const HATE_SPEECH_KEYWORDS = [
        'mort', 'tuer', 'assassiner', 'massacre', 'génocide', 'lyncher',
        'bombe', 'explosion', 'terrorisme', 'attaque', 'arme',
        'viol', 'violer', 'pédophile', 'enfant', 'petit',
        'enlever', 'kidnapper', 'prisonnier', 'esclave', 'servitude',
        'kill', 'murder', 'bomb', 'attack', 'rape', 'pedophile'
    ];

    public static function scan(string $text): array
    {
        $text = mb_strtolower($text, 'UTF-8');
        $results = [
            'is_clean' => true,
            'violations' => [],
            'severity' => 'none'
        ];

        if (self::containsOffensiveLanguage($text)) {
            $results['is_clean'] = false;
            $results['violations'][] = 'Langage offensant détecté';
            $results['severity'] = 'high';
        }

        if (self::containsRacism($text)) {
            $results['is_clean'] = false;
            $results['violations'][] = 'Contenu raciste détecté';
            $results['severity'] = 'critical';
        }

        if (self::containsGenderSpecificContent($text)) {
            $results['is_clean'] = false;
            $results['violations'][] = 'Contenu sensible relatif au genre détecté';
            $results['severity'] = 'medium';
        }

        if (self::containsSexualContent($text)) {
            $results['is_clean'] = false;
            $results['violations'][] = 'Contenu sexuel détecté';
            $results['severity'] = 'high';
        }

        if (self::containsHateSpeech($text)) {
            $results['is_clean'] = false;
            $results['violations'][] = 'Discours haineux détecté';
            $results['severity'] = 'critical';
        }

        return $results;
    }

    private static function containsOffensiveLanguage(string $text): bool
    {
        return self::containsKeywords($text, self::OFFENSIVE_WORDS, 0.6);
    }

    private static function containsRacism(string $text): bool
    {
        return self::containsKeywords($text, self::RACIST_WORDS, 0.5);
    }

    private static function containsSexualContent(string $text): bool
    {
        return self::containsKeywords($text, self::SEXUAL_WORDS, 0.5);
    }

    private static function containsGenderSpecificContent(string $text): bool
    {
        return self::containsKeywords($text, self::GENDER_SPECIFIC_WORDS, 0.6);
    }

    private static function containsHateSpeech(string $text): bool
    {
        return self::containsKeywords($text, self::HATE_SPEECH_KEYWORDS, 0.4);
    }

    private static function containsKeywords(string $text, array $keywords, float $threshold = 0.5): bool
    {
        $words = preg_split('/[\s\p{P}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?? [];
        $found = 0;
        $total = count($words);

        if ($total === 0) {
            return false;
        }

        foreach ($words as $word) {
            $word = mb_strtolower(trim($word), 'UTF-8');

            if (self::matchesKeyword($word, $keywords)) {
                $found++;
            }
        }

        $ratio = $found / $total;
        return $ratio >= $threshold;
    }

    private static function matchesKeyword(string $word, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            $keyword = mb_strtolower($keyword, 'UTF-8');

            if ($word === $keyword) {
                return true;
            }

            if (levenshtein($word, $keyword) <= 1) {
                return true;
            }

            if (stripos($word, $keyword) !== false && strlen($keyword) > 3) {
                return true;
            }

            if (self::isMaskedVariant($word, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private static function isMaskedVariant(string $word, string $keyword): bool
    {
        $masked = str_replace(['a', 'e', 'i', 'o', 'u'], ['*', '*', '*', '*', '*'], $keyword);
        $wordMasked = str_replace(['a', 'e', 'i', 'o', 'u'], ['*', '*', '*', '*', '*'], $word);

        return strpos($wordMasked, $masked) !== false;
    }

    public static function isSafe(string $text): bool
    {
        $result = self::scan($text);
        return $result['is_clean'];
    }

    public static function getSeverity(string $text): string
    {
        $result = self::scan($text);
        return $result['severity'];
    }

    public static function getViolations(string $text): array
    {
        $result = self::scan($text);
        return $result['violations'];
    }

    public static function sanitizeContent(string $text): string
    {
        if (self::isSafe($text)) {
            return $text;
        }

        $text = mb_strtolower($text, 'UTF-8');

        foreach (array_merge(self::OFFENSIVE_WORDS, self::RACIST_WORDS, self::SEXUAL_WORDS) as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/iu';
            $replacement = str_repeat('*', strlen($word));
            $text = preg_replace($pattern, $replacement, $text);
        }

        return $text;
    }

    public static function logViolation(string $text, array $violations, string $userId = 'unknown', string $context = 'content'): bool
    {
        $logFile = dirname(__DIR__, 2) . '/logs/content_moderation.log';

        if (!is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0775, true);
        }

        $logEntry = json_encode([
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $userId,
            'context' => $context,
            'violations' => $violations,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return file_put_contents($logFile, $logEntry . PHP_EOL, FILE_APPEND) !== false;
    }

    public static function validateUsername(string $username): array
    {
        $errors = [];

        if (strlen($username) < 3) {
            $errors[] = 'Le nom d\'utilisateur doit contenir au moins 3 caractères.';
        }

        if (strlen($username) > 32) {
            $errors[] = 'Le nom d\'utilisateur ne doit pas dépasser 32 caractères.';
        }

        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $username)) {
            $errors[] = 'Le nom d\'utilisateur ne peut contenir que des lettres, chiffres, tirets et points.';
        }

        if (!self::isSafe($username)) {
            $errors[] = 'Le nom d\'utilisateur contient du contenu inapproprié.';
        }

        return $errors;
    }

    public static function validateDisplayName(string $name): array
    {
        $errors = [];

        if (strlen($name) < 2) {
            $errors[] = 'Le nom doit contenir au moins 2 caractères.';
        }

        if (strlen($name) > 64) {
            $errors[] = 'Le nom ne doit pas dépasser 64 caractères.';
        }

        if (!preg_match('/^[a-zA-Z\s\-\'àâäùûüôöéèêëïîçÀÂÄÙÛÜÔÖÉÈÊËÏÎÇ]+$/', $name)) {
            $errors[] = 'Le nom contient des caractères non autorisés.';
        }

        if (!self::isSafe($name)) {
            $errors[] = 'Le nom contient du contenu inapproprié.';
        }

        return $errors;
    }

    public static function validateBiography(string $bio): array
    {
        $errors = [];

        if (strlen($bio) > 500) {
            $errors[] = 'La biographie ne doit pas dépasser 500 caractères.';
        }

        if (!self::isSafe($bio)) {
            $errors[] = 'La biographie contient du contenu inapproprié.';
        }

        return $errors;
    }
}
