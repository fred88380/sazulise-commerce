#!/usr/bin/env php
<?php
/**
 * Sazulis Security Test Suite
 *
 * Usage: php tests/security_test.php
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\SecurityValidator;
use App\Core\ContentModeration;

class SecurityTester
{
    private int $passed = 0;
    private int $failed = 0;

    public function runAllTests(): void
    {
        echo "\n🔒 Sazulis Security Test Suite\n";
        echo str_repeat("=", 60) . "\n\n";

        $this->testEmailValidation();
        $this->testPasswordValidation();
        $this->testXSSDetection();
        $this->testSQLInjectionDetection();
        $this->testOffensiveLanguage();
        $this->testRacismDetection();
        $this->testSexualContent();
        $this->testFilenameSanitization();
        $this->testTextSanitization();
        $this->testPathTraversal();

        $this->printSummary();
    }

    private function testEmailValidation(): void
    {
        echo "📧 Email Validation Tests\n";
        echo str_repeat("-", 60) . "\n";

        $tests = [
            ['valid@example.com', true, 'Valid email'],
            ['user.name+tag@example.co.uk', true, 'Complex valid email'],
            ['invalid@', false, 'Missing domain'],
            ['@example.com', false, 'Missing local part'],
            ['user@localhost', false, 'Localhost email'],
            ['user@127.0.0.1', false, 'IP email'],
            ['a@b.c', false, 'Too short'],
            ['valid@example.com.' . str_repeat('a', 300), false, 'Too long'],
        ];

        foreach ($tests as [$email, $expected, $description]) {
            $result = SecurityValidator::sanitizeEmail($email) !== null ? true : false;
            $this->assert($result === $expected, $description, $email);
        }
        echo "\n";
    }

    private function testPasswordValidation(): void
    {
        echo "🔐 Password Validation Tests\n";
        echo str_repeat("-", 60) . "\n";

        $tests = [
            ['SecurePass123!', [], 'Valid password'],
            ['short', ['12+ caractères'], 'Too short'],
            ['nouppercase123!', ['majuscule'], 'No uppercase'],
            ['NOLOWERCASE123!', ['minuscule'], 'No lowercase'],
            ['NoNumbers!', ['chiffre'], 'No numbers'],
            ['NoSpecial123', ['caractère spécial'], 'No special char'],
        ];

        foreach ($tests as [$password, $expectedErrors, $description]) {
            $errors = SecurityValidator::validatePassword($password);
            $hasExpectedErrors = count(array_filter($errors, function($e) use ($expectedErrors) {
                foreach ($expectedErrors as $exp) {
                    if (stripos($e, $exp) !== false) return true;
                }
                return false;
            })) > 0 || empty($expectedErrors);

            $this->assert($hasExpectedErrors, $description, $password);
        }
        echo "\n";
    }

    private function testXSSDetection(): void
    {
        echo "💀 XSS Detection Tests\n";
        echo str_repeat("-", 60) . "\n";

        $tests = [
            ['Hello world', false, 'Safe text'],
            ['<script>alert(1)</script>', true, 'Script tag'],
            ['<img src=x onerror=alert(1)>', true, 'Event handler'],
            ['javascript:void(0)', true, 'Javascript protocol'],
            ['<iframe src="x"></iframe>', true, 'Iframe tag'],
            ['data:text/html,<script>alert(1)</script>', true, 'Data URI'],
            ['<!--comment-->', false, 'HTML comment'],
            ['<div>Safe div</div>', false, 'Safe div tag'],
        ];

        foreach ($tests as [$text, $expected, $description]) {
            $result = SecurityValidator::isXssSuspicious($text);
            $this->assert($result === $expected, $description, $text);
        }
        echo "\n";
    }

    private function testSQLInjectionDetection(): void
    {
        echo "🔓 SQL Injection Detection Tests\n";
        echo str_repeat("-", 60) . "\n";

        $tests = [
            ['normal text', false, 'Normal text'],
            ["admin' OR '1'='1", true, 'Classic OR injection'],
            ['UNION SELECT * FROM users', true, 'UNION SELECT'],
            ['DROP TABLE users', true, 'DROP TABLE'],
            ['INSERT INTO users', true, 'INSERT INTO'],
            ['1; DELETE FROM users', true, 'Stacked query'],
            ["'; exec xp_cmdshell", true, 'Stored procedure'],
            ['1 -- comment', true, 'SQL comment'],
        ];

        foreach ($tests as [$text, $expected, $description]) {
            $result = SecurityValidator::detectSqlInjection($text);
            $this->assert($result === $expected, $description, $text);
        }
        echo "\n";
    }

    private function testOffensiveLanguage(): void
    {
        echo "😤 Offensive Language Detection\n";
        echo str_repeat("-", 60) . "\n";

        $tests = [
            ['This is a nice comment', true, 'Clean text'],
            ['putain de connard', false, 'Offensive French'],
            ['bloody fucking shit', false, 'Offensive English'],
            ['Hello world', true, 'Simple greeting'],
        ];

        foreach ($tests as [$text, $expected, $description]) {
            $result = ContentModeration::isSafe($text);
            $this->assert($result === $expected, $description, $text);
        }
        echo "\n";
    }

    private function testRacismDetection(): void
    {
        echo "🚫 Racism Detection\n";
        echo str_repeat("-", 60) . "\n";

        $tests = [
            ['people from all backgrounds', true, 'Inclusive text'],
            ['negre arabe terroriste', false, 'Racist French'],
            ['monkey paki illegal', false, 'Racist English'],
            ['I love my country', true, 'Patriotic safe'],
        ];

        foreach ($tests as [$text, $expected, $description]) {
            $result = ContentModeration::isSafe($text);
            $this->assert($result === $expected, $description, $text);
        }
        echo "\n";
    }

    private function testSexualContent(): void
    {
        echo "❤️ Sexual Content Detection\n";
        echo str_repeat("-", 60) . "\n";

        $tests = [
            ['Let us discuss romantic relationships', true, 'Safe relationship talk'],
            ['I am interested in learning more', true, 'Safe learning text'],
            ['xxx adult nude pornographic content', false, 'Explicit content'],
        ];

        foreach ($tests as [$text, $expected, $description]) {
            $result = ContentModeration::isSafe($text);
            $this->assert($result === $expected, $description, $text);
        }
        echo "\n";
    }

    private function testFilenameSanitization(): void
    {
        echo "📁 Filename Sanitization Tests\n";
        echo str_repeat("-", 60) . "\n";

        $tests = [
            ['image.jpg', 'image.jpg', 'Safe filename'],
            ['my-file_123.png', 'my-file_123.png', 'Filename with dash/underscore'],
            ['../../etc/passwd', '______etc_passwd', 'Path traversal'],
            ['file<script>.jpg', 'file_script_.jpg', 'Script tag removed'],
            ['file" onload="alert(1)"', 'file__onload_alert_1__', 'Event handler removed'],
        ];

        foreach ($tests as [$filename, $expected, $description]) {
            $result = SecurityValidator::sanitizeFilename($filename);
            $this->assert(strpos($result, '..') === false, $description, $filename);
        }
        echo "\n";
    }

    private function testTextSanitization(): void
    {
        echo "✨ Text Sanitization Tests\n";
        echo str_repeat("-", 60) . "\n";

        $tests = [
            ['Hello world', 'Hello world', 'Safe text'],
            ['<script>alert(1)</script>', '&lt;script&gt;alert(1)&lt;/script&gt;', 'Script removed'],
            ['Test "quotes" and \'apostrophes\'', 'Test &quot;quotes&quot; and &#039;apostrophes&#039;', 'Quotes escaped'],
        ];

        foreach ($tests as [$text, $expected, $description]) {
            $result = SecurityValidator::sanitizeText($text);
            $this->assert(
                strpos($result, '<') === false && strpos($result, '>') === false,
                $description,
                $text
            );
        }
        echo "\n";
    }

    private function testPathTraversal(): void
    {
        echo "🛣️ Path Traversal Prevention\n";
        echo str_repeat("-", 60) . "\n";

        $tests = [
            ['uploads/file.jpg', 'uploads/file.jpg', 'Safe path'],
            ['../../etc/passwd', 'etc/passwd', 'Traversal removed'],
            ['..\\..\\windows\\system32', '__windows_system32', 'Windows traversal'],
            ['uploads//file.jpg', 'uploads/file.jpg', 'Double slashes'],
        ];

        foreach ($tests as [$path, $expected, $description]) {
            $result = SecurityValidator::sanitizePath($path);
            $this->assert(strpos($result, '..') === false, $description, $path);
        }
        echo "\n";
    }

    private function assert(bool $condition, string $description, string $context = ''): void
    {
        if ($condition) {
            echo "✅ PASS: $description\n";
            $this->passed++;
        } else {
            echo "❌ FAIL: $description";
            if ($context) {
                echo " (Context: $context)";
            }
            echo "\n";
            $this->failed++;
        }
    }

    private function printSummary(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 Test Results\n";
        echo "✅ Passed: " . $this->passed . "\n";
        echo "❌ Failed: " . $this->failed . "\n";
        echo "📈 Total: " . ($this->passed + $this->failed) . "\n";

        $percentage = ($this->passed + $this->failed) > 0
            ? round(($this->passed / ($this->passed + $this->failed)) * 100, 2)
            : 0;

        echo "🎯 Success Rate: " . $percentage . "%\n";
        echo str_repeat("=", 60) . "\n\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }
}

$tester = new SecurityTester();
$tester->runAllTests();
