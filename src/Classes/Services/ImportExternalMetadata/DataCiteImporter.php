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

use \Httpful\Request;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use EWW\Dpf\Domain\Model\DataCiteMetadata;
use EWW\Dpf\Domain\Model\ExternalMetadata;

class DataCiteImporter extends AbstractImporter implements Importer
{
    /**
     * @var string
     */
    protected $apiUrl = "https://api.datacite.org";

    /**
     * @var string
     */
    protected $resource = "/dois";


    /**
     * Returns the list of all publication types
     *
     * @return array
     */
    public static function types()
    {
        return [
            'Audiovisual',
            'Collection',
            'DataPaper',
            'Dataset',
            'Event',
            'Image',
            'InteractiveResource',
            'Model',
            'PhysicalObject',
            'Service',
            'Software',
            'Sound',
            'Text',
            'Workflow',
            'Other'
        ];
    }

    /**
     * @param string $identifier
     * @return ExternalMetadata|null
     */
    public function findByIdentifier($identifier)
    {
        try {
            $response = Request::get($this->apiUrl . $this->resource . "/".$identifier)->send();

            if (!$response->hasErrors() && $response->code == 200) {
                $jsonString = $response->__toString();
                if ($jsonString) {
                    $data = json_decode($response->__toString(),true);
                    $encoder = new XmlEncoder();

                    /** @var DataCiteMetadata $dataCiteMetadata */
                    $dataCiteMetadata = $this->objectManager->get(DataCiteMetadata::class);

                    $dataCiteMetadata->setSource(get_class($this));
                    $dataCiteMetadata->setFeUser($this->security->getUser()->getUid());
                    $dataCiteMetadata->setData($encoder->encode($data, 'xml'));
                    $dataCiteMetadata->setPublicationIdentifier($identifier);

                    return $dataCiteMetadata;
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
        return $client->getDataciteTransformation()->current();
    }

    /**
     * @return string
     */
    protected function getDefaultXsltFilePath() : string
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(
            'EXT:dpf/Resources/Private/Xslt/datacite-default.xsl'
        );
    }

    /**
     * @return string
     */
    protected function getImporterName()
    {
        return 'datacite';
    }


}
