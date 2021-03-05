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

use \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to calc index in search results
 *
 * # Example: Basic example
 * <code>
 * <dpf:showNewsTitle news="123">
 *    <span>Blog Artikel about...</span>
 * </code>
 * <output>
 * Will output the news title of the given news id
 * </output>
 *
 * @package TYPO3
 * @subpackage tx_slub_news_extend
 */

class CalcIndexViewHelper extends AbstractViewHelper
{
    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument('currentIndex', 'int', '', true);
        $this->registerArgument('currentPage', 'int', '', true);
        $this->registerArgument('itemsPerPage', 'int', '', true);
    }

    /**
     * Render the supplied DateTime object as a formatted date.
     *
     * @return int
     * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
     * @api
     */
    public function render()
    {
        $currentIndex = $this->arguments['currentIndex'];
        $currentPage = $this->arguments['currentPage'];
        $itemsPerPage = $this->arguments['itemsPerPage'];

        return ($currentPage - 1) * $itemsPerPage + $currentIndex;
    }
}
