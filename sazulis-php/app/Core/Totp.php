<?php

declare(strict_types=1);

namespace App\Core;

/**
 * TOTP implementation – RFC 6238 / RFC 4226
 * Compatible Google Authenticator, Authy, Aegis…
 * Aucune dependance externe.
 */
final class Totp
{
    private const CHARS  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const PERIOD = 30;
    private const DIGITS = 6;
    private const WINDOW = 1; // ±1 periode toleree

    /** Genere un secret aleatoire base32 (20 octets = 32 caracteres). */
    public static function generateSecret(): string
    {
        return self::base32Encode(random_bytes(20));
    }

    /** Verifie un code TOTP en tenant compte de ±WINDOW periodes. */
    public static function verify(string $secret, string $code): bool
    {
        $ts = (int) floor(time() / self::PERIOD);
        for ($i = -self::WINDOW; $i <= self::WINDOW; $i++) {
            if (hash_equals(self::generate($secret, $ts + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    /** Genere le code TOTP pour une periode donnee (null = maintenant). */
    public static function generate(string $secret, ?int $ts = null): string
    {
        $ts  = $ts ?? (int) floor(time() / self::PERIOD);
        $key = self::base32Decode($secret);
        // Compteur 8 octets big-endian
        $time = pack('N*', 0) . pack('N*', $ts);
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord($hash[19]) & 0x0f;
        $code   = (
            ((ord($hash[$offset])     & 0x7f) << 24)
          | ((ord($hash[$offset + 1]) & 0xff) << 16)
          | ((ord($hash[$offset + 2]) & 0xff) <<  8)
          |  (ord($hash[$offset + 3]) & 0xff)
        ) % (10 ** self::DIGITS);
        return str_pad((string) $code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /** URI otpauth:// pour le QR code. */
    public static function provisioningUri(string $secret, string $email, string $issuer = 'Sazulis'): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=%d&period=%d',
            rawurlencode($issuer),
            rawurlencode($email),
            $secret,
            rawurlencode($issuer),
            self::DIGITS,
            self::PERIOD
        );
    }

    // -----------------------------------------------------------------------
    // Base32
    // -----------------------------------------------------------------------

    private static function base32Encode(string $data): string
    {
        $result  = '';
        $buffer  = 0;
        $bitsLeft = 0;
        for ($i = 0, $len = strlen($data); $i < $len; $i++) {
            $buffer    = ($buffer << 8) | ord($data[$i]);
            $bitsLeft += 8;
            while ($bitsLeft >= 5) {
                $bitsLeft -= 5;
                $result   .= self::CHARS[($buffer >> $bitsLeft) & 0x1f];
            }
        }
        if ($bitsLeft > 0) {
            $result .= self::CHARS[($buffer << (5 - $bitsLeft)) & 0x1f];
        }
        return $result;
    }

    private static function base32Decode(string $data): string
    {
        $result   = '';
        $buffer   = 0;
        $bitsLeft = 0;
        foreach (str_split(strtoupper($data)) as $char) {
            $pos = strpos(self::CHARS, $char);
            if ($pos === false) {
                continue;
            }
            $buffer    = ($buffer << 5) | $pos;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $result   .= chr(($buffer >> $bitsLeft) & 0xff);
            }
        }
        return $result;
    }
}
