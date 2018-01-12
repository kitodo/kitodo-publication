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


use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * GndController
 */
class GndController extends \EWW\Dpf\Controller\AbstractController
{
    protected $gndHost = 'http://lobid.org/gnd/';

    protected $searchUrl = 'search?format=json:suggest&filter=type:SubjectHeading&size=100&q=';

    /**
     *
     * @param string $search
     * @return string
     */
    public function searchAction($search) {

        $url = $this->gndHost . $this->searchUrl . $search;
        $content = file_get_contents($url);
        $json = json_decode($content);


        $listArray = array();
        $i = 0;
        foreach ($json as $value) {
            $listArray[$i]['value'] = $value->label;
            $listArray[$i]['label'] = htmlentities($value->label);
            $listArray[$i]['gnd'] = $value->id;
            $i++;
        }

        if (empty($listArray)) {
            echo json_encode(null);
        } else {
            echo json_encode($listArray);
        }

        return '';
    }

}