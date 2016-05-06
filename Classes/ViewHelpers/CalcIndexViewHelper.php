<?php
namespace EWW\Dpf\ViewHelpers;
/***************************************************************
*  Copyright notice
*
*  (c) 2016 Alexander Bigga <alexander.bigga@slub-dresden.de>, SLUB Dresden
*
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 3 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

use \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * ViewHelper to calc index in search results
 *
 * # Example: Basic example
 * <code>
 * <dpf:showNewsTitle news="123">
 *	<span>Blog Artikel about...</span>
 * </code>
 * <output>
 * Will output the news title of the given news id
 * </output>
 *
 * @package TYPO3
 * @subpackage tx_slub_news_extend
 */

class CalcIndexViewHelper extends AbstractViewHelper {

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
