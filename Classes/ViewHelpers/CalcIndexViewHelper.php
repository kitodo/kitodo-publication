<?php
namespace EWW\Dpf\ViewHelpers;

/**
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

use \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

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

    /**
     * Render the supplied DateTime object as a formatted date.
     *
     * @param int $currentIndex
     * @param int $currentPage
     * @param int itemsPerPage
     * @param int $index
     *
     * @return int
     * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
     * @api
     */
    public function render($currentIndex, $currentPage, $itemsPerPage)
    {
        return ($currentPage - 1) * $itemsPerPage + $currentIndex;
    }
}
