<?php
namespace EWW\Dpf\ViewHelpers;

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
