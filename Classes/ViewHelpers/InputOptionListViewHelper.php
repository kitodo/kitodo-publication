<?php
namespace EWW\Dpf\ViewHelpers;

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

class InputOptionListViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * InputOptionListRepository
     *
     * @var \EWW\Dpf\Domain\Repository\InputOptionListRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $inputOptionListRepository = null;

    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('uid', 'integer', '', true);
    }

    /**
     * Returns an array with the input options: value => label.
     *
     * @return array
     */
    public function render()
    {
        $uid = $this->arguments['uid'];
        $options = $this->inputOptionListRepository->findByUid($uid);

        if ($options) {
            $inputOptions = [];
            foreach ($options->getInputOptions() as $key => $value) {
                $inputOptions[str_replace(":", "\\:", $key)] = $value;
            }

            return $inputOptions;
        }

        return [];
    }
}
