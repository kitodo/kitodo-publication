<?php
namespace EWW\Dpf\Controller;

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

use EWW\Dpf\Domain\Model\InputOptionList;
use EWW\Dpf\Services\FeUser\GndDataService;

/**
 * DataServiceAjaxController
 */
class DataServiceAjaxController extends \EWW\Dpf\Controller\AbstractController
{
    /**
     * @param string $searchTerm
     * @return false|string
     */
    public function searchGndKeywordAction(string $searchTerm) {
        $gndUserDataService = new GndDataService();
        $result = $gndUserDataService->searchKeywordRequest($searchTerm);

        $listArray = array();
        $i = 0;
        foreach ($result as $value) {
            $listArray[$i]['value'] = $value->label;
            $listArray[$i]['label'] = htmlentities($value->label);
            $listArray[$i]['key'] = $value->id;
            $i++;
        }

        return json_encode($listArray);
    }

    /**
     * @param InputOptionList $inputOptionList
     * @param string $searchTerm
     * @return false|string
     */
    public function autocompleteAction(InputOptionList $inputOptionList, string $searchTerm)
    {
        $listArray = array();
        $i = 0;

        if (
            !empty(
                $arr = preg_grep(
                    '/.*?'.$searchTerm.'.*?/i', $inputOptionList->getInputOptions()
                )
            )
        ) {
            foreach ($arr as $key => $value) {
                $listArray[$i]['value'] = $value;
                $listArray[$i]['label'] = htmlentities($value);
                $listArray[$i]['key'] = $key;
                $i++;
            }
        }

        return json_encode($listArray);
    }
}
