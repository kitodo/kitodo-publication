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
use EWW\Dpf\Services\Transformer\DocumentTransformer;
use EWW\Dpf\Services\ProcessNumber\ProcessNumberGenerator;
use EWW\Dpf\Domain\Model\DocumentType;
use EWW\Dpf\Domain\Model\PubMedMetadata;
use EWW\Dpf\Domain\Model\ExternalMetadata;

class PubMedImporter extends AbstractImporter implements Importer
{
    /**
     * @var string
     */
    protected $apiUrl = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esummary.fcgi?version=2.0&db=pubmed";

    /**
     * @var string
     */
    protected $resource = "&id=";


    /**
     * Returns the list of all publication types
     *
     * @return array
     */
    public static function types()
    {
        return [
            'Collected Works',
            'Congress',
            'Dataset',
            'Dictionary',
            'Journal Article'
        ];
    }

    /**
     * @param string $identifier
     * @return ExternalMetadata|null
     */
    public function findByIdentifier($identifier)
    {
        try {
            $response = Request::get($this->apiUrl . $this->resource . $identifier)->send();

            if (!$response->hasErrors() && $response->code == 200) {

                /** @var PubMedMetadata $pubMedMetadata */
                $pubMedMetadata = $this->objectManager->get(PubMedMetadata::class);

                $pubMedMetadata->setSource(get_class($this));
                $pubMedMetadata->setFeUser($this->security->getUser()->getUid());
                $pubMedMetadata->setData($response);
                $pubMedMetadata->setPublicationIdentifier($identifier);

                $xmlDataXpath = $pubMedMetadata->getDataXpath();

                $node = $xmlDataXpath->query('/eSummaryResult/DocumentSummarySet/DocumentSummary/error');
                if ($node->length > 0) {
                    return null;
                }

                return $pubMedMetadata;

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
        return $client->getPubmedTransformation()->current();
    }

    /**
     * @return string
     */
    protected function getDefaultXsltFilePath() : string
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(
            'EXT:dpf/Resources/Private/Xslt/pubmed-default.xsl'
        );
    }

    /**
     * @return string
     */
    protected function getImporterName()
    {
        return 'pubmed';
    }

}
