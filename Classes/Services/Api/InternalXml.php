<?php

namespace EWW\Dpf\Services\Api;

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
use DOMElement;
use DOMNode;
use DOMXPath;
use EWW\Dpf\Domain\Model\MetadataGroup;
use EWW\Dpf\Domain\Model\MetadataObject;
use EWW\Dpf\Services\Xml\XMLFragmentGenerator;
use EWW\Dpf\Services\Xml\XPath;

class InternalXml
{
    /**
     * @var string
     */
    protected $rootNode = '//data/';

    /**
     * xml
     *
     * @var DOMDocument
     */
    protected $xml;

    /**
     * @var string
     */
    protected $namespaces = '';

    /**
     * @return string
     */
    public function getXml(): string
    {
        return $this->xml->saveXML();
    }

    public function setXml($xml)
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml);
        $this->xml = $dom;
    }

    /**
     * @param string $namespaces
     */
    public function setNamespaces(string $namespaces)
    {
        $this->namespaces = $namespaces;
    }

    public function getXpath(): DOMXPath
    {
        return XPath::create($this->xml, $this->namespaces);
    }

    public function getNextReference(): string
    {
        $references = [];

        $xpath = $this->getXpath();
        $nodes = $xpath->query("//@ID");
        if ($nodes->length > 0) {
            foreach ($nodes as $node) {
                $references[strtoupper($node->nodeValue)] = $node->nodeValue;
            }
            ksort($references);
            $key = explode("_", array_key_last($references));
            $referenceCounter = intval($key[1]);
            return sprintf("QUCOSA_%03d", $referenceCounter + 1);
        }
        return "QUCOSA_000";
    }

    /**
     * @param MetadataGroup $metadataGroup
     * @param int $groupIndex
     * @return GroupNode|null
     */
    public function findGroup(MetadataGroup $metadataGroup, int $groupIndex = 0): ?GroupNode
    {
        $groupMapping = $metadataGroup->getMapping();
        $groupMappingForReading = trim($metadataGroup->getMappingForReading(), '/ ');
        $extensionMapping = trim($metadataGroup->getModsExtensionMapping(), '/ ');
        $extensionReference = trim($metadataGroup->getModsExtensionReference());

        $xpath = $this->getXpath();

        $nodes = $xpath->query(
            $this->getRootNode() . ($groupMappingForReading ? $groupMappingForReading : $groupMapping)
        );

        if ($nodes->length - $groupIndex > 0) {
            $group = new GroupNode();
            $group->setMetadataGroup($metadataGroup);
            $group->setMainNode($nodes->item($groupIndex));

            if ($extensionMapping && $extensionReference) {
                /** @var DOMElement $mainNode */
                $mainNode = $group->getMainNode();
                $reference = $mainNode->getAttribute('ID');
                if ($reference) {
                    $extensionNodes = $xpath->query(
                        $this->getRootNode() .
                        '/' . $extensionMapping . '[@' . $extensionReference . '="' . $reference . '"]'
                    );
                    if ($extensionNodes->length > 0) {
                        $group->setExtensionNode($extensionNodes->item(0));
                    }
                }
            }
            return $group;
        }

        return null;
    }

    /**
     * @param GroupNode $group
     * @param MetadataObject $metadataObject
     * @param int $fieldIndex
     * @return DOMNode|false|null
     */
    public function findField(GroupNode $group, MetadataObject $metadataObject, int $fieldIndex = 0)
    {
        $fieldMapping = $metadataObject->getMapping();

        $xpath = $this->getXpath();

        if ($group->getMainNode()) {
            $nodes = $xpath->query(trim($fieldMapping, "/"), $group->getMainNode());
            if ($nodes->length - $fieldIndex > 0) {
                return $nodes->item($fieldIndex);
            } else {
                if ($group->getExtensionNode()) {
                    $extensionNodes = $xpath->query(trim($fieldMapping, "/"), $group->getExtensionNode());
                    if ($extensionNodes->length - $fieldIndex > 0) {
                        return $extensionNodes->item($fieldIndex);
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param MetadataGroup $metadataGroup
     * @param array $fieldData
     * @return GroupNode|null
     */
    public function addGroup(MetadataGroup $metadataGroup, array $fieldData = [], $index = 0): ?GroupNode
    {
        $groupMapping = $metadataGroup->getMapping();

        $fragment = XMLFragmentGenerator::fragmentFor($groupMapping);

        // FIXME: XMLFragmentGenerator does not generate namespace declarations,
        // which DOMDocument cannot cope with. Actually, namespaces should not be necessary here,
        // since it is about child elements that are then added to the overall XML.
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $domLoaded = $dom->loadXML($fragment);
        libxml_use_internal_errors(false);

        if ($domLoaded) {
            $newGroup = new GroupNode();
            $newGroup->setMetadataGroup($metadataGroup);

            $tempNode = $dom->firstChild;
            $importedNode = $this->xml->importNode($tempNode);
            $importedParentNode = $importedNode;
            while ($tempNode->hasChildNodes()) {
                $tempNode = $tempNode->firstChild;
                $importedChildNode = $this->xml->importNode($tempNode);
                $importedChildNode->setAttribute("metadata-item-id", $metadataGroup->getUid() . '-' . $index);
                $importedParentNode->appendChild($importedChildNode);
                $importedParentNode = $importedChildNode;
            }

            $this->xml->documentElement->appendChild($importedNode);
            $newGroup->setMainNode($importedParentNode);

            if ($newGroup->getMainNode()) {
                foreach ($fieldData as $fieldItem) {
                    $this->addField($newGroup, $fieldItem["metadataObject"], $fieldItem["value"]);
                }
                return $newGroup;
            }
        }

        return null;
    }

    /**
     * @param GroupNode $group
     * @param MetadataObject $metadataObject
     * @param string $value
     * @return bool
     */
    public function addField(GroupNode $group, MetadataObject $metadataObject, string $value = ''): bool
    {
        $fieldMapping = $metadataObject->getMapping();

        /** @var DOMElement $mainNode */
        $mainNode = $group->getMainNode();

        if ($mainNode) {
            if (str_starts_with(trim($fieldMapping, "/"), "@")) {
                $mainNode->setAttribute(trim($fieldMapping, "/@"), $value);
                return true;
            } else {
                $fragment = XMLFragmentGenerator::fragmentFor("$fieldMapping='$value'");

                // FIXME: XMLFragmentGenerator does not generate namespace declarations,
                // which DOMDocument cannot cope with. Actually, namespaces should not be necessary here,
                // since it is about child elements that are then added to the overall XML.
                libxml_use_internal_errors(true);
                $dom = new DOMDocument();
                $domLoaded = $dom->loadXML($fragment);
                libxml_use_internal_errors(false);

                if ($domLoaded) {
                    $newField = $this->xml->importNode($dom->firstChild, true);

                    if ($metadataObject->getModsExtension()) {
                        if ($group->getExtensionNode()) {
                            $group->getExtensionNode()->appendChild($newField);
                        } else {
                            // Extension node needs to be created.
                            $reference = $mainNode->getAttribute("ID");

                            if (empty($reference)) {
                                $reference = $this->getNextReference();
                            }

                            $fragment = XMLFragmentGenerator::fragmentFor(
                                sprintf("%s[@%s=\"%s\"]",
                                    $group->getMetadataGroup()->getModsExtensionMapping(),
                                    $group->getMetadataGroup()->getModsExtensionReference(),
                                    $reference)
                            );

                            // FIXME: XMLFragmentGenerator does not generate namespace declarations,
                            // which DOMDocument cannot cope with. Actually, namespaces should not be necessary here,
                            // since it is about child elements that are then added to the overall XML.
                            libxml_use_internal_errors(true);
                            $domExtension = new DOMDocument();
                            $domExtensionLoaded = $domExtension->loadXML($fragment);
                            libxml_use_internal_errors(false);

                            if ($domExtensionLoaded) {
                                $newExtension = $this->xml->importNode($domExtension->firstChild, true);
                                $newExtension->firstChild->appendChild($newField);
                                $group->setExtensionNode(
                                    $mainNode->parentNode->appendChild($newExtension)
                                );
                                $mainNode->setAttribute('ID', $reference);
                            }
                        }
                        return true;
                    } else {
                        $mainNode->appendChild($newField);
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param GroupNode $group
     * @param MetadataObject $metadataObject
     * @param int $fieldIndex
     * @param string $value
     * @return bool
     */
    public function setField(GroupNode $group, MetadataObject $metadataObject, int $fieldIndex, string $value): bool
    {
        $fieldMapping = $metadataObject->getMapping();

        /** @var DOMElement $mainNode */
        $mainNode = $group->getMainNode();

        if ($mainNode) {
            if (str_starts_with(trim($fieldMapping, "/"), "@")) {
                $mainNode->setAttribute(trim($fieldMapping, "/@"), $value);
            } else {
                $field = $this->findField($group, $metadataObject, $fieldIndex);
                if ($field) {
                    $field->nodeValue = $value;
                } else {
                    return false;
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param GroupNode $group
     * @param MetadataObject $metadataObject
     * @param int $fieldIndex
     * @return bool
     */
    public function removeField(GroupNode $group, MetadataObject $metadataObject, int $fieldIndex = 0): bool
    {
        $fieldMapping = $metadataObject->getMapping();

        /** @var DOMElement $mainNode */
        $mainNode = $group->getMainNode();

        if ($mainNode) {
            if (str_starts_with(trim($fieldMapping, "/"), "@")) {
                $mainNode->removeAttribute(trim($fieldMapping, "/@"));
            } else {
                $field = $this->findField($group, $metadataObject, $fieldIndex);
                if ($field) {
                    if ($metadataObject->isModsExtension()) {
                        //$group->getExtensionNode()->removeChild($field);
                        $this->removeNode($field, $group->getExtensionNode()->nodeName);
                    } else {
                        //$group->getMainNode()->removeChild($field);
                        $this->removeNode($field, $mainNode->nodeName);
                    }
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param MetadataGroup $metadataGroup
     * @param int $groupIndex
     */
    public function removeGroup(MetadataGroup $metadataGroup, int $groupIndex = 0)
    {
        $group = $this->findGroup($metadataGroup, $groupIndex);
        if ($group instanceof GroupNode) {

            $this->removeNode($group->getMainNode(), $group->getMainNode()->parentNode->nodeName);

            if ($group->getExtensionNode() instanceof DOMNode) {
                $outerExtensionNode = $group->getExtensionNode()->parentNode;
                $this->removeNode($group->getExtensionNode(), $outerExtensionNode->nodeName);

                if (!$outerExtensionNode->hasChildNodes()) {
                    $outerExtensionNode->parentNode->removeChild($outerExtensionNode);
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getRootNode(): string
    {
        return $this->rootNode;
    }

    /**
     * @param string $rootNode
     */
    public function setRootNode(string $rootNode): void
    {
        $this->rootNode = $rootNode;
    }

    /**
     * @param DOMNode $node
     * @param string $outerNode
     */
    protected function removeNode(DOMNode $node, string $outerNode)
    {
        $innerGroupNode = $node;
        $outerGroupNode = $node;
        $parentNode = $node->parentNode;
        while ($parentNode && $parentNode->nodeName != trim($outerNode, '/')) {
            $innerGroupNode = $outerGroupNode;
            $outerGroupNode = $parentNode;
            $parentNode = $outerGroupNode->parentNode;
        }

        $innerGroupNode->parentNode->removeChild($innerGroupNode);

        if (!$outerGroupNode->hasChildNodes()) {
            if ($outerGroupNode->nodeName != $innerGroupNode->nodeName) {
                $this->xml->documentElement->removeChild($outerGroupNode);
            }
        }
    }
}
