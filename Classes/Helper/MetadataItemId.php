<?php

namespace EWW\Dpf\Helper;

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class MetadataItemId
{
    private $id;

    /**
     * @param string $id
     */
    public function __construct(string $id)
    {
        if (preg_match("/^[1-9][0-9]*-[0-9]+(-[1-9][0-9]*-[0-9]+){0,1}$/", $id, $matches)) {
            $this->id = explode('-', $id);
        } else {
            throw new \Exception("Invalid Metadata-Item-Id: " . $id);
        }
    }

    public function getPart($index) {
        if (isset($this->id[$index])) {
            return is_numeric($this->id[$index]) ? $this->id[$index] : "error";
        } else {
            throw new \Exception("Metadata-Item-Id: Invalid index " . $index);
        }
    }

    public function getGroupId() {
        return $this->getPart(0);

    }

    public function getGroupIndex()
    {
        return $this->getPart(1);
    }

    public function getFieldId()
    {
        return $this->getPart(2);
    }

    public function getFieldIndex()
    {
        return $this->getPart(3);
    }

    public function __toString()
    {
        return implode('-', $this->id);
    }
}
