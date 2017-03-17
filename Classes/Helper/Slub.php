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

class Slub
{

    protected $slubDom;

    public function __construct($slubXml)
    {
        $this->setSlubXml($slubXml);
    }

    public function setSlubXml($slubXml)
    {
        $slubDom = new \DOMDocument();
        if (!empty($slubXml)) {
            $slubDom->loadXML($slubXml);
        }
        $this->slubDom = $slubDom;
    }

    public function getSlubXml()
    {
        return $this->slubDom->saveXML();
    }

    public function getSlubXpath()
    {
        $xpath = \EWW\Dpf\Helper\XPath::create($this->slubDom);
        return $xpath;
    }

    public function getDocumentType()
    {
        $documentTypeNode = $this->getSlubXpath()->query("/slub:info/slub:documentType");
        return $documentTypeNode->item(0)->nodeValue;
    }

    public function getProcessNumber()
    {
        $processNumberNode = $this->getSlubXpath()->query("/slub:info/slub:processNumber");
        return $processNumberNode->item(0)->nodeValue;
    }

    public function setProcessNumber($processNumber)
    {
        $processNumberNode = $this->getSlubXpath()->query("/slub:info/slub:processNumber");
        if ($processNumberNode->length == 1) {
            $processNumberNode->item(0)->nodeValue = $processNumber;
        } else {
            $slubInfoNode = $this->getSlubXpath()->query("/slub:info");
            if ($slubInfoNode->length == 1) {
                $pNum = $this->slubDom->createElement('slub:processNumber');
                $pNum->nodeValue = $processNumber;
                $slubInfoNode->item(0)->appendChild($pNum);
            } else {
                throw new \Exception('Invalid slubInfo data.');
            }
        }
    }

    public function getSubmitterEmail()
    {
        $emailNode = $this->getSlubXpath()->query("/slub:info/slub:submitter/foaf:Person/foaf:mbox");
        return $emailNode->item(0)->nodeValue;
    }

    public function getSubmitterName()
    {
        $nameNode = $this->getSlubXpath()->query("/slub:info/slub:submitter/foaf:Person/foaf:name");
        return $nameNode->item(0)->nodeValue;
    }

    public function getSubmitterNotice()
    {
        $nameNode = $this->getSlubXpath()->query("/slub:info/slub:submitter/slub:notice");
        return $nameNode->item(0)->nodeValue;
    }

}
