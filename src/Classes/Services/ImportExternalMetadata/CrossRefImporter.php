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
use EWW\Dpf\Domain\Model\CrossRefMetadata;
use EWW\Dpf\Domain\Model\ExternalMetadata;

class CrossRefImporter extends AbstractImporter implements Importer
{
    /**
     * @var string
     */
    protected $apiUrl = "https://api.crossref.org";

    /**
     * @var string
     */
    protected $resource = "/works";

    /**
     * Returns the list of all publication types
     *
     * @return array
     */
    public static function types()
    {
        return [
            'book-section',
            'monograph',
            'report',
            'peer-review',
            'book-track',
            'journal-article',
            'book-part',
            'other',
            'book',
            'journal-volume',
            'book-set',
            'reference-entry',
            'proceedings-article',
            'journal',
            'component',
            'book-chapter',
            'proceedings-series',
            'report-series',
            'proceedings',
            'standard',
            'reference-book',
            'posted-content',
            'journal-issue',
            'dissertation',
            'dataset',
            'book-series',
            'edited-book',
            'standard-series'
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

                    /** @var CrossRefMetadata $crossRefMetadata */
                    $crossRefMetadata = $this->objectManager->get(CrossRefMetadata::class);

                    $crossRefMetadata->setSource(get_class($this));
                    $crossRefMetadata->setFeUser($this->security->getUser()->getUid());
                    $crossRefMetadata->setData($encoder->encode($data, 'xml'));
                    $crossRefMetadata->setPublicationIdentifier($identifier);

                    return $crossRefMetadata;
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
        return [];
    }


    /**
     * @return \EWW\Dpf\Domain\Model\TransformationFile
     */
    protected function getDefaultXsltTransformation() : ?\EWW\Dpf\Domain\Model\TransformationFile
    {
        /** @var \EWW\Dpf\Domain\Model\Client $client */
        $client = $this->clientRepository->findAll()->current();

        /** @var \EWW\Dpf\Domain\Model\TransformationFile $xsltTransformationFile */
        return $client->getCrossrefTransformation()->current();
    }

    /**
     * @return string
     */
    protected function getDefaultXsltFilePath() : string
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(
                'EXT:dpf/Resources/Private/Xslt/crossref-default.xsl'
        );
    }

    /**
     * @return string
     */
    protected function getImporterName()
    {
        return 'crossref';
    }

}
