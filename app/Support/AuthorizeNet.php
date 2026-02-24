<?php

namespace App\Support;

final class AuthorizeNet
{
    /** Returns true if X-ANET-Signature matches the payload using Signature Key (hex) */
    public static function verify(string $payload, string $header, string $signatureKeyHex): bool
    {
        if ($payload === '' || $header === '' || $signatureKeyHex === '') {
            return false;
        }

        // header format: "SHA512=abcdef..."
        $parts = explode('=', $header, 2);
        if (count($parts) !== 2 || strtoupper($parts[0]) !== 'SHA512') {
            return false;
        }

        $given = $parts[1];
        $key = @pack('H*', $signatureKeyHex);        // hex to binary
        if ($key === false) {
            return false;
        }

        $calc = hash_hmac('sha512', $payload, $key);
        return hash_equals($calc, $given);
    }
}
