<?php
namespace EWW\Dpf\Domain\Model;

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

use EWW\Dpf\Services\Suggestion\GroupChange;

class DocumentFormPage extends AbstractFormElement
{
    /**
     * @return bool
     */
    public function isFilePage()
    {
        foreach ($this->getItems() as $group) {
            foreach ($group as $groupItem) {
                if ($groupItem->isFileGroup()) {
                    return true;
                }
            }
        }
        return false;
    }


    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->uid . "-0";
    }

    /**
     * Applies the given change
     *
     * @param GroupChange $groupChange
     */
    public function applyGroupChange(GroupChange $groupChange)
    {
        if ($groupChange->isAdded()) {
            foreach ($this->getItems() as $keyGroup => $valueGroup) {
                foreach ($valueGroup as $keyRepeatGroup => $valueRepeatGroup) {
                    if ($keyGroup === $groupChange->getGroup()->getUid()) {
                        $this->addItem($groupChange->getGroup());
                        return;
                    }
                }
            }
        } elseif ($groupChange->isDeleted()) {
            foreach ($this->getItems() as $keyGroup => $valueGroup) {
                foreach ($valueGroup as $keyRepeatGroup => $valueRepeatGroup) {
                    if ($valueRepeatGroup->getId() === $groupChange->getGroup()->getId()) {
                        $this->removeItem($groupChange->getGroup());
                         return;
                    }
                }
            }
        } elseif ($groupChange->hasFieldChanges()) {
            foreach ($this->getItems() as $keyGroup => $valueGroup) {
                foreach ($valueGroup as $keyRepeatGroup => $valueRepeatGroup) {
                    if ($valueRepeatGroup->getId() === $groupChange->getGroup()->getId()) {
                        $valueRepeatGroup->applyChanges($groupChange);
                        return;
                    }
                }
            }
        }
    }

    public function addItem($item)
    {
        $uid = $item->getUid();

        if ($item->getId()) {
            $id = explode('-', $item->getId());
            $index = $id[1];
        } else {
            $index = 0;
        }

        $this->items[$uid][$index] = $item;
    }

}
