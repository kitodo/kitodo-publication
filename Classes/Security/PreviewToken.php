<?php
namespace EWW\Dpf\Security;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

class PreviewToken
{
    const TTL = 3600;

    /**
     * Generate a short-lived, document-scoped HMAC token.
     *
     * The master secret never appears in the token or in URLs — only a
     * per-document, time-limited MAC is embedded. Token format:
     * "{expiry_unix}|{hmac_sha256}"
     *
     * @param string $qid Document process number
     * @param string $secret Master delivery secret from TypoScript
     * @return string Opaque token safe to embed in URLs
     */
    public static function generate(string $qid, string $secret): string
    {
        $expiry = time() + self::TTL;
        $mac = hash_hmac('sha256', "$qid|$expiry", $secret);
        return "$expiry|$mac";
    }

    /**
     * Validate a preview token against the document qid and master secret.
     *
     * Uses hash_equals() for constant-time comparison to prevent timing attacks.
     *
     * @param string $token Token from the URL parameter
     * @param string $qid Document process number the token must be bound to
     * @param string $secret Master delivery secret from TypoScript
     * @return bool True if the token is valid and not expired
     */
    public static function validate(string $token, string $qid, string $secret): bool
    {
        $parts = explode('|', $token, 2);
        if (count($parts) !== 2) {
            return false;
        }
        [$expiry, $mac] = $parts;
        if (!is_numeric($expiry) || time() > (int)$expiry) {
            return false;
        }
        $expected = hash_hmac('sha256', "$qid|$expiry", $secret);
        return hash_equals($expected, $mac);
    }
}
