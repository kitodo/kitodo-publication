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

class GroupNode
{
    /**
     * @var \EWW\Dpf\Domain\Model\MetadataGroup
     */
    protected $metadataGroup = null;

    /**
     * @var \DOMNode
     */
    protected $mainNode = null;

    /**
     * @var \DOMNode
     */
    protected $extensionNode = null;

    /**
     * @return \DOMNode
     */
    public function getMainNode(): ?\DOMNode
    {
        return $this->mainNode;
    }

    /**
     * @param \DOMNode $mainNode
     */
    public function setMainNode(?\DOMNode $mainNode): void
    {
        $this->mainNode = $mainNode;
    }

    /**
     * @return \DOMNode
     */
    public function getExtensionNode(): ?\DOMNode
    {
        return $this->extensionNode;
    }

    /**
     * @param \DOMNode $extensionNode
     */
    public function setExtensionNode(?\DOMNode $extensionNode): void
    {
        $this->extensionNode = $extensionNode;
    }

    /**
     * @return \EWW\Dpf\Domain\Model\MetadataGroup
     */
    public function getMetadataGroup(): ?\EWW\Dpf\Domain\Model\MetadataGroup
    {
        return $this->metadataGroup;
    }

    /**
     * @param \EWW\Dpf\Domain\Model\MetadataGroup $metadataGroup
     */
    public function setMetadataGroup(?\EWW\Dpf\Domain\Model\MetadataGroup $metadataGroup): void
    {
        $this->metadataGroup = $metadataGroup;
    }

}
