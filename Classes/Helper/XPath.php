<?php
namespace EWW\Dpf\Helper;

class XPath {
    
    /**
     * Returns a new XPath object for the given DOMDocument, 
     * all required namespaces are already registered.
     *       
     * @param \DOMDocument $dom
     * @return \DOMXPath
     */
    static function create($dom) {  
        $xpath = new \DOMXPath($dom);  
        $xpath->registerNamespace('mods', "http://www.loc.gov/mods/v3");
        $xpath->registerNamespace('slub', "http://slub-dresden.de/");
        $xpath->registerNamespace('foaf', "http://xmlns.com/foaf/0.1/");
        $xpath->registerNamespace('person', "http://www.w3.org/ns/person#");
        $xpath->registerNamespace('rdf', "http://www.w3.org/1999/02/22-rdf-syntax-ns#");      
        return $xpath;
    }    
    
}

?>
