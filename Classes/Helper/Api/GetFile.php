<?PHP
namespace EWW\Dpf\Helper\Api;

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

/**
 * API to return METS or Attachement from Fedora
 *
 * Example:
 *
 * 1. METS from Fedora
 *   http://localhost/api/qucosa:1234/mets
 *
 *   This always returns METS which is supplemented with additional information.
 *   The embedded MODS record is not the original MODS as it is stored in the
 *   repository datastream.
 *
 * 2. Attachment from Fedora
 *   http://localhost/api/qucosa:1234/attachment/ATT-0
 *
 * 3. METS from Goobi.Publication (this extension)
 *   http://localhost/api/3/preview
 *
 *
 * @author    Alexander Bigga <alexander.bigga@slub-dresden.de>
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

class GetFile
{

    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @inject
     */
    protected $documentRepository;

    /**
    * clientConfigurationManager
    * 
    * @var \EWW\Dpf\Configuration\ClientConfigurationManager 
    * @inject
    */
    protected $clientConfigurationManager;



    public function attachement($content, $conf)
    {

        $piVars = GeneralUtility::_GP('tx_dpf'); // get GET params from powermail

        $objectManager = GeneralUtility::makeInstance('\TYPO3\CMS\Extbase\Object\ObjectManager');

        $this->clientConfigurationManager = $objectManager->get('\EWW\Dpf\Configuration\ClientConfigurationManager');

        $fedoraHost = $this->clientConfigurationManager->getFedoraHost();

        switch ($piVars['action']) {
            case 'mets':
                $path = rtrim('http://' . $fedoraHost,"/").'/fedora/objects/'.$piVars['qid'].'/methods/qucosa:SDef/getMETSDissemination?supplement=yes';
                break;
            case 'preview':

                $this->documentRepository = $objectManager->get('\EWW\Dpf\Domain\Repository\DocumentRepository');

                $document = $this->documentRepository->findByUid($piVars['qid']);

                if ($document) {

                    // Build METS-Data
                    $exporter = new \EWW\Dpf\Services\MetsExporter();

                    $fileData = $document->getCurrentFileData();

                    $exporter->setFileData($fileData);

                    $exporter->setMods($document->getXmlData());

                    $exporter->setSlubInfo($document->getSlubInfoData());

                    $exporter->buildMets();

                    $metsXml = $exporter->getMetsData();

                    header('Content-Type: text/xml; charset=UTF-8');

                }

                return $metsXml;

            case 'attachment':
                $path = rtrim('http://' . $fedoraHost, "/") . '/fedora/objects/' . $piVars['qid'] . '/datastreams/' . $piVars['attachment'] . '/content';
                break;
            default:
                break;
        }

        // get remote header and set it before passtrough
        $headers = get_headers($path);

        foreach ($headers as $key => $value) {
            // set remote header information
            preg_match('/filename="(.*)"/', $value, $fileName);

            if ($fileName[1]) {
                header('Content-Disposition: inline; filename="' . $fileName[1] . '";');
                continue;
            }

            if (substr($value, 0, 13) == "Content-Type:") {
                header($value);
                continue;
            }

            if (substr($value, 0, 13) == "Content-Length:") {
                header($value);
                continue;
            }
        }

        if ($stream = fopen($path, 'r')) {

            fpassthru($stream);

            fclose($stream);

        }

    }

}
