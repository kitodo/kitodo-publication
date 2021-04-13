<?php

namespace EWW\Dpf\Helper;

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

class XPath
{

    /**
     * Returns a new XPath object for the given DOMDocument,
     * all required namespaces are already registered.
     *
     * These namespace prefixes are often used in configuration and code, assuming the given namespace.
     * Since Kitodo.Publication 4.x namespaces and prefixes are part of the configuration.
     *
     * @param \DOMDocument $dom
     * @return \DOMXPath
     */
    public static function create($dom)
    {
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('mods', "http://www.loc.gov/mods/v3");
        $xpath->registerNamespace('slub', "http://slub-dresden.de/");
        $xpath->registerNamespace('foaf', "http://xmlns.com/foaf/0.1/");
        $xpath->registerNamespace('person', "http://www.w3.org/ns/person#");
        $xpath->registerNamespace('rdf', "http://www.w3.org/1999/02/22-rdf-syntax-ns#");
        $xpath->registerNamespace('xlink', "http://www.w3.org/1999/xlink");
        return $xpath;
    }
}
