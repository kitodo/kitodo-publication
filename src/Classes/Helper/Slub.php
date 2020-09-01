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

    /**
     * Gets the creator uid of the document, the person who added and registered the document.
     *
     * @return int
     */
    public function getDocumentCreator()
    {
        $node = $this->getSlubXpath()->query("/slub:info/slub:documentCreator");
        return intval($node->item(0)->nodeValue);
    }

    /**
     * Sets the creator uid of the document, the person who added and registered the document.
     *
     * @param int $docCreator
     * @throws \Exception
     */
    public function setDocumentCreator($docCreator)
    {
        $creatorNode = $this->getSlubXpath()->query("/slub:info/slub:documentCreator");
        if ($creatorNode->length == 1) {
            $creatorNode->item(0)->nodeValue = $docCreator;
        } else {
            $slubInfoNode = $this->getSlubXpath()->query("/slub:info");
            if ($slubInfoNode->length == 1) {
                $creator = $this->slubDom->createElement('slub:documentCreator');
                $creator->nodeValue = $docCreator;
                $slubInfoNode->item(0)->appendChild($creator);
            } else {
                throw new \Exception('Invalid slubInfo data.');
            }
        }
    }

    /**
     * Gets the validation state of the document.
     *
     * @return bool
     */
    public function getValidation()
    {
        $node = $this->getSlubXpath()->query("/slub:info/slub:validation/slub:validated");
        $value = $node->item(0)->nodeValue;
        return ($value === 'true')? true : false;
    }

    /**
     * Sets the validation state of the document.
     *
     * @param bool $validated
     * @throws \Exception
     */
    public function setValidation($validated)
    {
        $validationNode = $this->getSlubXpath()->query("/slub:info/slub:validation/slub:validated");
        if ($validationNode->length == 1) {
            $validationNode->item(0)->nodeValue = $validated;
        } else {
            $slubInfoNode = $this->getSlubXpath()->query("/slub:info");
            if ($slubInfoNode->length == 1) {

                $validation = $this->slubDom->createElement('slub:validation');

                $validated = $this->slubDom->createElement('slub:validated');
                $validated->nodeValue = ($validated)? 'true' : 'false';

                $validation->appendChild($validated);

                $slubInfoNode->item(0)->appendChild($validation);
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

    public function getNotes()
    {
        $node = $this->getSlubXpath()->query("/slub:info/slub:note");

        $notes = array();

        for ($i=0; $i < $node->length; $i++)
        {
            $notes[] = $node->item($i)->nodeValue;
        }

        return $notes;
    }

    public function addNote($noteContent)
    {
        $slubInfoNode = $this->getSlubXpath()->query('/slub:info');

        if ($slubInfoNode->length == 1) {
            $note = $this->slubDom->createElement('slub:note');
            $note->setAttribute('type', 'private');
            $note->nodeValue = $noteContent;
            $slubInfoNode->item(0)->appendChild($note);
        } else {
            throw new \Exception('Invalid slubInfo data.');
        }
    }

    /**
     * @return string
     */
    public function getDepositLicense()
    {
        $node = $this->getSlubXpath()->query("/slub:info/slub:rights/slub:agreement/@given");
        return $node->item(0)->nodeValue;
    }

}
