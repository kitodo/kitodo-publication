<?php
namespace EWW\Dpf\Service;

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

use EWW\Dpf\Helper\Mods;
use EWW\Dpf\Helper\Slub;

/**
 * Generates structured download filenames per issue #57:
 * YYYY_Nachname_Titel_Mandant[_N].ext
 */
class FilenameGenerator
{
    const MAX_NAME_LENGTH  = 15;
    const MAX_TITLE_LENGTH = 30;
    const MAX_SUFFIX_LENGTH = 10;

    const MUSICONN_COLLECTION = 'fidmusik';

    const NAMESPACE_TO_LABEL = [
        'qucosa:slub'         => 'Qucosa-SLUB',
        'qucosa:ubl'          => 'Qucosa-UBL',
        'qucosa:fid-move'     => 'FID-Move',
        'qucosa:tubaf'        => 'Qucosa-TUBAF',
        'qucosa:hzdr'         => 'Qucosa-HZDR',
        'qucosa:ubc'          => 'Monarch',
        'qucosa:htw'          => 'Qucosa-HTWDD',
        'qucosa:diu'          => 'Qucosa-DIU',
        'qucosa:tud'          => 'Qucosa-TUD',
        'qucosa:htwk-leipzig' => 'Qucosa-HTWKL',
        'qucosa:si'           => 'Qucosa-SI',
    ];

    const MIME_TO_EXT = [
        'application/pdf'      => '.pdf',
        'application/epub+zip' => '.epub',
        'application/zip'      => '.zip',
        'text/plain'           => '.txt',
        'text/xml'             => '.xml',
        'application/xml'      => '.xml',
        'image/jpeg'           => '.jpg',
        'image/png'            => '.png',
    ];

    /**
     * Generate a structured filename for a downloadable file attachment.
     *
     * @param string $modsXml             MODS XML from Document::getXmlData()
     * @param string $slubInfoXml         SLUB-INFO XML from Document::getSlubInfoData()
     * @param string $swordNamespace      Client sword_collection_namespace (mandant fallback)
     * @param string $mimeType            MIME type of the file
     * @param int    $fileIndex           0-based position among downloadable files
     * @param int    $totalFiles          Total number of downloadable files
     * @return string                     Filename including extension
     */
    /**
     * Generate a structured filename. Returns empty string on any failure so callers
     * can fall back to the previous behaviour (Fedora's Content-Disposition header).
     */
    public function generate(
        string $modsXml,
        string $slubInfoXml,
        string $swordNamespace,
        string $mimeType,
        int $fileIndex,
        int $totalFiles
    ): string {
        try {
            return $this->doGenerate($modsXml, $slubInfoXml, $swordNamespace, $mimeType, $fileIndex, $totalFiles);
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function doGenerate(
        string $modsXml,
        string $slubInfoXml,
        string $swordNamespace,
        string $mimeType,
        int $fileIndex,
        int $totalFiles
    ): string {
        $mods = new Mods($modsXml);
        $slub = new Slub($slubInfoXml);

        $isMusiconn = in_array(self::MUSICONN_COLLECTION, $slub->getCollections());

        $year  = $this->extractYear($mods->getDateIssued());
        $name  = $this->extractName($mods);
        $title = $this->extractTitle($mods->getTitle());
        $suffix = $this->resolveSuffix($isMusiconn, $swordNamespace);

        $parts = array_filter([$year, $name, $title, $suffix], function ($p) {
            return $p !== '';
        });

        $base = implode('_', $parts);

        if (empty($base)) {
            return '';
        }

        if ($totalFiles > 1 && $fileIndex > 0) {
            $base .= '_' . ($fileIndex + 1);
        }

        if ($isMusiconn) {
            $base = strtolower($base);
        }

        $ext = $this->mimeToExt($mimeType);

        return $base . $ext;
    }

    private function extractYear(?string $dateIssued): string
    {
        if (empty($dateIssued)) {
            return '';
        }
        return substr($dateIssued, 0, 4);
    }

    private function extractName(Mods $mods): string
    {
        $authors = $mods->getAuthors();
        if (!empty($authors)) {
            return $this->cleanWord($this->firstFamilyName($authors[0]), self::MAX_NAME_LENGTH);
        }

        $editors = $mods->getEditors();
        if (!empty($editors)) {
            return $this->cleanWord($this->firstFamilyName($editors[0]), self::MAX_NAME_LENGTH);
        }

        return '';
    }

    private function firstFamilyName(string $fullName): string
    {
        // getAuthors/getEditors returns "Given Family" — take last word as family name
        $parts = explode(' ', trim($fullName));
        return end($parts);
    }

    private function extractTitle(?string $title): string
    {
        if (empty($title)) {
            return '';
        }
        // Split into words, PascalCase each word, concatenate (no separator within title)
        $words = preg_split('/\s+/', trim($title));
        $pascal = '';
        foreach ($words as $word) {
            $word = $this->transliterateGerman($word);
            $word = preg_replace('/[^A-Za-z0-9]/', '', $word);
            if ($word !== '') {
                $pascal .= ucfirst($word);
            }
        }
        return substr($pascal, 0, self::MAX_TITLE_LENGTH);
    }

    private function resolveSuffix(bool $isMusiconn, string $swordNamespace): string
    {
        if ($isMusiconn) {
            return 'musiconn';
        }

        $ns = strtolower(trim($swordNamespace));

        if (isset(self::NAMESPACE_TO_LABEL[$ns])) {
            return self::NAMESPACE_TO_LABEL[$ns];
        }

        // Fallback: strip qucosa: prefix and uppercase
        if (strpos($ns, 'qucosa:') === 0) {
            return strtoupper(substr($ns, 7));
        }

        return strtoupper($ns);
    }

    private function cleanWord(string $value, int $maxLength): string
    {
        $value = $this->transliterateGerman($value);
        $value = preg_replace('/[^A-Za-z0-9]/', '', $value);
        return substr($value, 0, $maxLength);
    }

    private function transliterateGerman(string $value): string
    {
        $map = [
            'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue',
            'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue',
            'ß' => 'ss',
        ];
        $value = str_replace(array_keys($map), array_values($map), $value);
        $transliterated = iconv('utf-8', 'us-ascii//TRANSLIT', $value);
        return $transliterated !== false ? $transliterated : $value;
    }

    private function mimeToExt(string $mimeType): string
    {
        $mime = strtolower(trim(explode(';', $mimeType)[0]));
        return self::MIME_TO_EXT[$mime] ?? '';
    }
}
