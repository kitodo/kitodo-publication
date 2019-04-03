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

/**
 * Class XPathXMLGenerator
 * Generates XML elements for a given xpath
 * Notice: Not all sytax from the original xpath is implemented
 */
class XPathXMLGenerator
{
    protected $regex = '/[a-zA-Z:]+|[<=>]|[@][a-z][a-z0-9_\-\:\.]*|\[|\'.*?\'|".*?"|\]|\//';

    private $xmlWriter;

    public function __construct()
    {
        $this->xmlWriter = new \XMLWriter();
        $this->xmlWriter->openMemory();
    }

    function loop($xpath)
    {
        // split xpath to find predicates, attributes and texts
        preg_match_all($this->regex, $xpath, $matches);
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
                            $this->loop(trim($predicateString, "[]"));
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
                $this->setText(trim($value, "'\""));
            } else if ($firstChar === ']') {
                $this->endAttribute();
                array_pop($loopStack);
                $predicateStack--;

            } else if ($firstChar !== '=' && $firstChar !== '[' && $firstChar !== '@' && $firstChar !== '/') {
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
}
