<?php

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

namespace EWW\Dpf\Helper;

use DOMXPath;
use Exception;

class SlubInfoHelper
{
    /**
     * Check if a string is a valid Fedora PID (e.g. "qucosa:35840").
     * Format: namespace:localId — both parts alphanumeric plus dot, hyphen, underscore.
     */
    public static function isValidPid(string $pid): bool
    {
        return (bool)preg_match('/^[A-Za-z][A-Za-z0-9._-]*:[A-Za-z0-9._-]+$/', $pid);
    }

    /**
     * Check if a string is a valid Fedora datastream ID (e.g. "ATT-0", "SLUB-INFO").
     * Alphanumeric plus dot, hyphen, underscore only.
     */
    public static function isValidDsid(string $dsid): bool
    {
        return (bool)preg_match('/^[A-Za-z0-9._-]+$/', $dsid);
    }

    /**
     * Sanitize a Fedora datastream label for use as a download filename.
     * Strips any existing extension and OS-unsafe characters, then appends $extension.
     * Returns empty string if the result would be empty after sanitization.
     */
    public static function sanitizeFilenameLabel(string $label, string $extension): string
    {
        // Strip any trailing extension so we always use the MIME-derived one
        $name = pathinfo($label, PATHINFO_FILENAME) ?: $label;
        $name = preg_replace('/[\/\\\\:*?"<>|\x00-\x1f]/', '_', $name);
        $name = preg_replace('/\s+/', '_', $name);
        $name = trim($name, '_');
        if (empty($name)) {
            return '';
        }
        if (empty($extension)) {
            return $name;
        }
        return $name . '.' . ltrim($extension, '.');
    }

    /**
     * Check if a datastream is marked as downloadable in a SLUB-INFO XML document.
     *
     * @param string $slubInfoXml Raw XML content of the SLUB-INFO datastream
     * @param string $dsid        Fedora datastream identifier to check
     * @return bool True if the datastream is downloadable
     * @throws Exception if the XML cannot be parsed
     */
    public static function isDownloadable(string $slubInfoXml, string $dsid): bool
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $prevLibxmlErrors = libxml_use_internal_errors(true);
        $loaded = \EWW\Dpf\Helper\XPath::loadXml($dom, $slubInfoXml);
        libxml_use_internal_errors($prevLibxmlErrors);
        if (!$loaded) {
            throw new Exception("Cannot obtain datastream access conditions", 500);
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('slub', 'http://slub-dresden.de/');

        // $dsid is never interpolated into the query — comparison happens in PHP
        // to prevent XPath injection via user-controlled attachment IDs.
        $nodes = $xpath->query('//slub:attachment[@isDownloadable="yes"]');
        foreach ($nodes as $node) {
            if ($node->getAttribute('ref') === $dsid) {
                return true;
            }
        }

        return false;
    }
}
