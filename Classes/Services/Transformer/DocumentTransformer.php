<?php
namespace EWW\Dpf\Services\Transformer;

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

class DocumentTransformer
{

    public function transform($xslt, $xml, $params = [])
    {
        $xslDoc = new \DOMDocument();
        $xslDoc->load($xslt);

        $xmlDoc = new \DOMDocument();
        $xmlDoc->loadXML($xml);

        $processor = new \XSLTProcessor();

        foreach ($params as $key => $value) {
            $processor->setParameter('', $key, $value);
        }

        libxml_use_internal_errors(true);
        $result = $processor->importStyleSheet($xslDoc);
        if (!$result) {
            foreach (libxml_get_errors() as $error) {
                echo "Libxml error: {$error->message}\n";
            }
        }
        libxml_use_internal_errors(false);

        return $processor->transformToXml($xmlDoc);
    }

}