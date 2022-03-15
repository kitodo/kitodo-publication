<?php
namespace EWW\Dpf\Services;
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

use DOMDocument;
use Exception;

/**
 * Class XPathXMLGenerator
 * Generates XML elements for a given xpath
 * Notice: Not all sytax from the original xpath is implemented
 */
class XPathXMLGenerator
{
    protected $regex = '/[a-zA-Z:]+|[<=>]|[@][a-zA-Z][a-zA-Z0-9_\-\:\.]*|\[|\'.*?\'|".*?"|\]|\//';

    private $xmlWriter;

    public function __construct()
    {
        $this->xmlWriter = new \XMLWriter();
        $this->xmlWriter->openMemory();
    }

    function replaceLineBreaks($string) {
        return str_replace("\n", "%%nl%%", $string);
    }

    function undoReplaceLineBreaks($string) {
        return str_replace("%%nl%%", "\n", $string);
    }

    function generateXmlFromXPath($xpath)
    {
        // split xpath to find predicates, attributes and texts
        preg_match_all($this->regex, $this->replaceLineBreaks($xpath), $matches);
        $i = 0;
        $predicateStack = 0;
        $tokenStack = 0;
        $insidePredicate = false;
        $predicateString = "";
        $loopStack = array();

        foreach ($matches[0] as $key => $value) {
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
                            $this->generateXmlFromXPath(trim($predicateString, "[]"));
                            $predicateString = "";
                        }
                    }
                } else if ($value === "[") {
                    $predicateStack++;
                }

            } else if ($firstChar === '@') {
                // attribute found
                $this->startAttribute(substr($value, 1, strlen($value)));
                $loopStack[] = 'attribute';

            } else if ($firstChar === '"' || $firstChar === "'") {
                // string found
                $this->setText(trim($this->undoReplaceLineBreaks($value), "'\""));
            } else if ($firstChar === ']') {
                $this->endAttribute();
                array_pop($loopStack);
                $predicateStack--;
            } else if ($firstChar !== '=' && $firstChar !== '[' && $firstChar !== '@' && $firstChar !== '/' && $firstChar!== '.') {
                $this->startToken($value);
                $loopStack[] = 'token';
                if ($predicateStack > 0) {
                    $tokenStack++;
                }
            }
            $i++;
        }

        // end all open token and attributes in stacked order
        foreach (array_reverse($loopStack) as $key => $value) {
            if ($value === "attribute") {
                $this->endAttribute();
            } else if ($value === "token") {
                $this->endToken();
            }
        }
        $this->stack = array();

    }

    /**
     * @param $value
     * Starts an XML element
     * <$value>
     */
    function startToken($value)
    {
        $this->xmlWriter->startElement($value);
    }

    /**
     * Ends the last token
     */
    function endToken()
    {
        $this->xmlWriter->endElement();
    }

    /**
     * @param $value
     * Adds content to an XML element or attribute
     * <mods:mods>$value</mods:mods>
     */
    function setText($value)
    {
        $this->xmlWriter->text($value);
    }

    /**
     * @param $name
     * @param $value
     * It adds an attribute with name and value to an XML element
     * <mods:mods $name=$value>
     */
    function addAttribute($name, $value)
    {
        $this->xmlWriter->writeAttribute($name, $value);
    }

    /**
     * @param $name
     * Starts an attribute
     */
    function startAttribute($name)
    {
        $this->xmlWriter->startAttribute($name);
    }

    /**
     * Ends the last started attribute
     */
    function endAttribute()
    {
        $this->xmlWriter->endAttribute();
    }

    /**
     * @return string
     * Returns the created XML
     */
    function getXML()
    {
        return $this->xmlWriter->outputMemory();
    }

    /**
     * Return the generated XML as DOM document.
     *
     * @param Array $namespaceConfiguration Optional namespace prefix declarations in of the form
     *                                      "prefix=uri".
     * @return Generated XML DOM
     * @throws Exception if the generated XML could not be parsed
     */
    function getDocument($namespaceConfiguration = null):DOMDocument {
        $oldErrorValue = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $xml = $this->getXML();
        $domLoaded = $dom->loadXML($xml);
        libxml_use_internal_errors($oldErrorValue);

        // fabricate namespace declarations if specified
        if ($namespaceConfiguration) {
            /** @var DOMElement $element */
            $element = $dom->documentElement;
            foreach ($namespaceConfiguration as $value) {
                $nsDeclaration = explode("=", $value);
                $prefix = $nsDeclaration[0];
                $nsuri  = $nsDeclaration[1];
                $element->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:' . $prefix, $nsuri);
            }
        }

        if ($domLoaded === FALSE) {
            throw new Exception("Failed to parse generated XML.");
        }
        return $dom;
    }
}
