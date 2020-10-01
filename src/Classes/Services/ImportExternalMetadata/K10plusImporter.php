<?php
namespace EWW\Dpf\Services\ImportExternalMetadata;

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

\Httpful\Bootstrap::init();

use EWW\Dpf\Domain\Model\K10plusMetadata;
use \Httpful\Request;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use EWW\Dpf\Services\Transformer\DocumentTransformer;
use EWW\Dpf\Services\ProcessNumber\ProcessNumberGenerator;
use EWW\Dpf\Domain\Model\DocumentType;
use EWW\Dpf\Domain\Model\CrossRefMetadata;
use EWW\Dpf\Domain\Model\ExternalMetadata;

class K10plusImporter extends AbstractImporter implements Importer
{
    // sru.gbv.de/swb?version=1.1&operation=searchRetrieve&query=pica.isb%3D9783-960091295&maximumRecords=10&recordSchema=mods

    //http://sru.k10plus.de/opac-de-627?version=1.1&operation=searchRetrieve&query=pica.tit%3DMcCarthyism&maximumRecords=10&recordSchema=mods

    /**
     * @var string
     */
    protected $apiUrl = "http://sru.k10plus.de/opac-de-627";

    /**
     * @var string
     */
    protected $resource = "?version=1.1&operation=searchRetrieve&&maximumRecords=1&recordSchema=mods&query=pica.isb%3D";

    /**
     * Returns the list of all publication types
     *
     * @return array
     */
    public static function types()
    {
        return [];
    }

    /**
     * @param string $identifier
     * @return ExternalMetadata|null
     */
    public function findByIdentifier($identifier)
    {
        try {
            $response = Request::get($this->apiUrl . $this->resource .$identifier)->send();

            if (!$response->hasErrors() && $response->code == 200) {

                $xmlDoc = new \DOMDocument();
                $xmlDoc->loadXML($response);
                $xpath = new \DOMXPath($xmlDoc);
                $xpath->registerNamespace('mods', "http://www.loc.gov/mods/v3");
                $xpath->registerNamespace('zs', "http://www.loc.gov/zing/srw/");

                $dataNode = $xpath->query('/zs:searchRetrieveResponse/zs:records/zs:record[1]/zs:recordData');

                if ($dataNode->length > 0) {
                    $mods = $xmlDoc->saveXML($dataNode->item(0)->firstChild);

                    /** @var K10plusMetadata $metadata */
                    $metadata = $this->objectManager->get(K10plusMetadata::class);

                    $metadata->setSource(get_class($this));
                    $metadata->setFeUser($this->security->getUser()->getUid());
                    $metadata->setData($mods);
                    $metadata->setPublicationIdentifier($identifier);

                    return $metadata;
                } else {
                    return null;
                }
            } else {
                return null;
            }

        } catch (\Throwable $throwable) {
            $this->logger->error($throwable->getMessage());
            throw $throwable;
        }

        return null;
    }

    /**
     * @param string $query
     * @return mixed
     */
    public function search($query)
    {
        return null;
    }


    /**
     * @return \EWW\Dpf\Domain\Model\TransformationFile
     */
    protected function getDefaultXsltTransformation() : ?\EWW\Dpf\Domain\Model\TransformationFile
    {
        /** @var \EWW\Dpf\Domain\Model\Client $client */
        $client = $this->clientRepository->findAll()->current();

        /** @var \EWW\Dpf\Domain\Model\TransformationFile $xsltTransformationFile */
        return $client->getK10plusTransformation()->current();
    }

    /**
     * @return string
     */
    protected function getDefaultXsltFilePath() : string
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(
            'EXT:dpf/Resources/Private/Xslt/k10plus-default.xsl'
        );
    }

    /**
     * @return string
     */
    protected function getImporterName()
    {
        return 'k10plus';
    }

}
