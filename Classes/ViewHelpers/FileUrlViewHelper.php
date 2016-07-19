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

class FileUrlViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{

    /**
     *
     * @param string $uri
     *
     */
    public function render($uri)
    {
        return $this->buildFileUri($uri);
    }

    protected function buildFileUri($uri)
    {

        $uploadFileUrl = new \EWW\Dpf\Helper\UploadFileUrl;

        $regex = '/\/(\w*:\d*)\/datastreams\/(\w*-\d*)/';
        preg_match($regex, $uri, $treffer);

        if (!empty($treffer)) {
            $qid = $treffer[1];
            $fid = $treffer[2];
            return $uploadFileUrl->getBaseUrl() . '/api/' . urlencode($qid) . '/attachment/' . $fid;
        }

        return $uri;
    }

}
