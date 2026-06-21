<?php
namespace EWW\Dpf\Helper;

/**
 * Extract metadata from a MODS SimpleXMLElement using configured XPath rows.
 *
 * Uses DOMXPath::evaluate() to handle both node-set XPaths and string-function
 * XPaths such as concat(). Registers mods: and xlink: namespaces.
 *
 * Pure PHP — no TYPO3 dependencies. Testable without bootstrap.
 */
class MetadataExtractor
{
    /**
     * @param \SimpleXMLElement $mods MODS root element
     * @param array $metaList index_name => ['xpath' => string, 'default_value' => string, ...]
     * @return array index_name => [value, ...]
     */
    public static function extract(\SimpleXMLElement $mods, array $metaList)
    {
        $domNode = dom_import_simplexml($mods);
        $xpathEval = new \DOMXPath($domNode->ownerDocument);
        $xpathEval->registerNamespace('mods', 'http://www.loc.gov/mods/v3');
        $xpathEval->registerNamespace('xlink', 'http://www.w3.org/1999/xlink');

        $result = [];
        foreach ($metaList as $indexName => $conf) {
            $xpath = $conf['xpath'] ?? '';
            $default = $conf['default_value'] ?? '';

            if ($xpath === '') {
                if ($default !== '') {
                    $result[$indexName] = [$default];
                }
                continue;
            }

            $xpathResult = @$xpathEval->evaluate($xpath, $domNode);
            $values = [];

            if ($xpathResult instanceof \DOMNodeList) {
                for ($i = 0; $i < $xpathResult->length; $i++) {
                    $val = trim($xpathResult->item($i)->nodeValue);
                    if ($val !== '') {
                        $values[] = $val;
                    }
                }
            } elseif (is_string($xpathResult)) {
                $val = trim($xpathResult);
                if ($val !== '') {
                    $values[] = $val;
                }
            }

            if (!empty($values)) {
                $result[$indexName] = $values;
            } elseif ($default !== '') {
                $result[$indexName] = [$default];
            }
        }

        return $result;
    }
}
