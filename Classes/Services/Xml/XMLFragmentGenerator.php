<?php

namespace EWW\Dpf\Services\Xml;
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

use XMLWriter;

/**
 * Generates XML fragments for a given XPath expression
 *
 * Notice: Only a subset of XPath is supported
 */
class XMLFragmentGenerator
{
    protected static $regex = '/[a-zA-Z:]+|[<=>]|@[a-zA-Z][a-zA-Z0-9_\-:.]*|\[|\'.*?\'|".*?"|]|\//';

    /**
     * Generates an XML fragment literal from an XPath expression
     *
     * The given XPath expression should match the fragment when
     * it is part of an XML document.
     *
     * @param string $xpath An XPath expression describing elements and values of the XML fragment
     * @return string XML fragment for inserting into other DOMDocument instances
     */
    public static function fragmentFor(string $xpath): string
    {
        $writer = new XMLWriter();
        $writer->openMemory();
        self::generate($xpath, $writer);
        return $writer->outputMemory();
    }

    /**
     * Parse XPath expression recursively and issue elements to given XMLWriter
     *
     * @param string $xpath An XPath expression describing elements and values of the XML fragment
     * @param XMLWriter $writer XMLWriter to use
     * @return void
     */
    private static function generate(string $xpath, XMLWriter $writer)
    {
        // split xpath to find predicates, attributes and texts
        preg_match_all(self::$regex, self::replaceSpecialCharacter($xpath), $matches);
        $predicateStack = 0;
        $insidePredicate = false;
        $predicateString = "";
        $loopStack = array();

        foreach ($matches[0] as $value) {
            $firstChar = substr($value, 0, 1);
            if ($firstChar === '[' || $insidePredicate) {
                // get corresponding bracket
                $insidePredicate = true;
                $predicateString .= $value;
                if ($value === "]") {
                    $predicateStack--;
                    if ($predicateStack === 0) {
                        // corresponding bracket found
                        $insidePredicate = false;
                        if (!empty($predicateString)) {
                            // recursive call with predicate string
                            self::generate(trim($predicateString, "[]"), $writer);
                            $predicateString = "";
                        }
                    }
                } else if ($value === "[") {
                    $predicateStack++;
                }
            } else if ($firstChar === '@') {
                // attribute found
                $writer->startAttribute(substr($value, 1, strlen($value)));
                $loopStack[] = 'attribute';
            } else if ($firstChar === '"' || $firstChar === "'") {
                // string found
                $trimmed = trim($value, "'\"");
                $replaced = self::undoReplaceSpecialCharacters($trimmed);
                $plain = self::removeEscapeCharacters($replaced);
                $writer->text($plain);
            } else if ($firstChar === ']') {
                $writer->endAttribute();
                array_pop($loopStack);
                $predicateStack--;
            } else if (($firstChar !== '=') && ($firstChar !== '/') && ($firstChar !== '.')) {
                $writer->startElement($value);
                $loopStack[] = 'token';
            }
        }

        // end all open token and attributes in stacked order
        foreach (array_reverse($loopStack) as $value) {
            if ($value === "attribute") {
                $writer->endAttribute();
            } else if ($value === "token") {
                $writer->endElement();
            }
        }
    }

    /**
     * Replacing newlines and escaped quotes to allow naive tokenizing
     *
     * @param $string
     * @return array|string|string[]
     */
    private static function replaceSpecialCharacter($string)
    {
        return str_replace(
            ["\n", '\"'],
            ["%%nl%%", "%%sq%%"],
            $string);
    }

    /**
     * Reverse replacing newlines and escaped quotes
     *
     * @param $string
     * @return array|string|string[]
     */
    private static function undoReplaceSpecialCharacters($string)
    {
        return str_replace(
            ["%%nl%%", "%%sq%%"],
            ["\n", '\"'],
            $string);
    }

    /**
     * Remove quotation characters from string
     *
     * @param string $s
     * @return string Given string without escaped characters
     */
    private static function removeEscapeCharacters(string $s): string
    {
        return str_replace('\"', '"', $s);
    }

}
