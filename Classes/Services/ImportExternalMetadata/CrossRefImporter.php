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

use EWW\Dpf\Domain\Model\CrossRefMetadata;
use EWW\Dpf\Domain\Model\ExternalMetadata;
use Httpful\Request;
use Httpful\Response;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

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
            $response = Request::get($this->apiUrl . $this->resource . "/" . $identifier)
                // CrossRef API  bug breaks autoparsing
                // see https://crossref.atlassian.net/jira/software/c/projects/CR/issues/CR-1013
                ->autoParse(false)
                ->send();

            $isJsonResponse = preg_match('/application\/json/i', $response->content_type);

            if ($response->code == 200 && $isJsonResponse) {
                $data = json_decode($response->raw_body, true);

                $encoder = new XmlEncoder();

                /** @var CrossRefMetadata $crossRefMetadata */
                $crossRefMetadata = $this->objectManager->get(CrossRefMetadata::class);

                $crossRefMetadata->setSource(get_class($this));
                $crossRefMetadata->setFeUser($this->security->getUser()->getUid());
                $crossRefMetadata->setData($encoder->encode($data, 'xml'));
                $crossRefMetadata->setPublicationIdentifier($identifier);

                return $crossRefMetadata;
            } else {
                return null;
            }
        } catch (\Throwable $throwable) {
            $this->logger->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, $throwable->getMessage());
            throw $throwable;
        }

        return null;
    }


    /**
     * @param string $query
     * @param int $rows
     * @param int $offset
     * @param string $searchField
     * @return array|mixed
     * @throws \Throwable
     */
    public function search($query, $rows = 10, $offset = 0, $searchField = 'author')
    {
        $requestUri = $this->apiUrl . $this->resource . '?rows='.$rows;

        if ($offset > 0) $requestUri .= '&offset=' . $offset;

        if ($searchField) {
           $requestUri .= '&query.'.$searchField.'='.urlencode($query);
        } else {
            switch (PublicationIdentifier::determineIdentifierType(trim($query))) {
                case 'DOI':
                    $requestUri .= "&filter=doi:".urlencode(trim($query));
                    break;
                case 'ISBN':
                    $isbn = str_replace(['-',' '], '', $query);
                    $requestUri .= "&filter=isbn:".$isbn;
                    break;
                case 'ISSN':
                    $requestUri .= "&filter=issn:".trim($query);
                    break;
                default:
                    $requestUri .= "&query=".urlencode($query);
                    break;
            }
        }

        try {
            $response = Request::get($requestUri)->send();

            if (!$response->hasErrors() && $response->code == 200) {

                $jsonString = $response->__toString();
                if ($jsonString) {

                    $data = json_decode($response->__toString(),true);
                    $encoder = new XmlEncoder();

                    if ($data['message']['total-results'] < 1) {
                        return [];
                    }

                    foreach ($data['message']['items'] as $item) {

                        /** @var CrossRefMetadata $crossRefMetadata */
                        $crossRefMetadata = $this->objectManager->get(CrossRefMetadata::class);

                        $crossRefMetadata->setSource(get_class($this));
                        $crossRefMetadata->setFeUser($this->security->getUser()->getUid());
                        $crossRefMetadata->setData($encoder->encode(['message' => $item], 'xml'));
                        $crossRefMetadata->setPublicationIdentifier($item['DOI']);

                        $results['items'][$crossRefMetadata->getPublicationIdentifier()] = $crossRefMetadata;
                    }

                    $results['total-results'] = $data['message']['total-results'];
                    $results['items-per-page'] = $data['message']['items-per-page'];

                    // Because the CrossRef api does not allow an offset more than 10000 we need to limit the result total
                    $maxPages = ceil(10000 / $results['items-per-page']);
                    $pages = ceil($results['total-results'] / $results['items-per-page']);
                    if ($pages > $maxPages) {
                        $results['total-results'] = $maxPages *  $results['items-per-page'];
                    }

                    return $results;
                }

            } else {
                return [];
            }

        } catch (\Throwable $throwable) {
            $this->logger->log(\TYPO3\CMS\Core\Log\LogLevel::ERROR, $throwable->getMessage());
            throw $throwable;
        }

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
     * @param DocumentType $documentType
     * @return \EWW\Dpf\Domain\Model\TransformationFile|null
     */
    protected function getXsltTransformationByDocumentType($documentType)
    {
        return $documentType->getCrossrefTransformation()->current();
    }


    /**
     * @return string
     */
    protected function getImporterName()
    {
        return 'crossref';
    }

}
