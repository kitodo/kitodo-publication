<?php
namespace EWW\Dpf\Services\Transfer;

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

use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Services\Transfer\ElasticsearchRepository;
use EWW\Dpf\Domain\Model\File;

class DocumentTransferManager
{

    /**
     * documenRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @inject
     */
    protected $documentRepository;

    /**
     * documenTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     * @inject
     */
    protected $documentTypeRepository;

    /**
     * fileRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FileRepository
     * @inject
     */
    protected $fileRepository;

    /**
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     * @inject
     */
    protected $objectManager;

    /**
     * persistence manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     * @inject
     */
    protected $persistenceManager;

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     * @inject
     */
    protected $configurationManager;

    /**
     * @var \EWW\Dpf\Configuration\ClientConfigurationManager
     * @inject
     */
    protected $clientConfigurationManager;

    /**
     * remoteRepository
     *
     * @var \EWW\Dpf\Services\Transfer\Repository
     */
    protected $remoteRepository;

    /**
     * Sets the remote repository into which the documents will be stored
     *
     * @param \EWW\Dpf\Services\Transfer\Repository $remoteRepository
     */
    public function setRemoteRepository($remoteRepository)
    {

        $this->remoteRepository = $remoteRepository;

    }

    /**
     * Stores a document into the remote repository
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return boolean
     */
    public function ingest($document)
    {

        $document->setTransferStatus(Document::TRANSFER_QUEUED);
        $this->documentRepository->update($document);

        $exporter = new \EWW\Dpf\Services\MetsExporter();

        $fileData = $document->getFileData();

        $exporter->setFileData($fileData);

        $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData());

        // Set current date as publication date
        $dateIssued = (new \DateTime)->format(\DateTime::ISO8601);
        $mods->setDateIssued($dateIssued);

        $hostUrn = $mods->getHostUrn();

        $exporter->setMods($mods->getModsXml());

        $exporter->setSlubInfo($document->getSlubInfoData());

        $exporter->setObjId($document->getObjectIdentifier());

        $exporter->buildMets();

        $metsXml = $exporter->getMetsData();

        // remove document from local index
        $elasticsearchRepository = $this->objectManager->get(ElasticsearchRepository::class);
        $elasticsearchRepository->delete($document, "");

        $remoteDocumentId = $this->remoteRepository->ingest($document, $metsXml);

        if ($remoteDocumentId) {
            $document->setDateIssued($dateIssued);
            $document->setObjectIdentifier($remoteDocumentId);
            $document->setTransferStatus(Document::TRANSFER_SENT);
            $this->documentRepository->update($document);
            $this->documentRepository->remove($document);

            $this->invalidateHostCache($hostUrn);

            return true;
        } else {
            $document->setTransferStatus(Document::TRANSFER_ERROR);
            $this->documentRepository->update($document);
            return false;
        }

    }

    /**
     * Updates an existing document in the remote repository
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return boolean
     */
    public function update($document)
    {
        // remove document from local index
        $elasticsearchRepository = $this->objectManager->get(ElasticsearchRepository::class);
        $elasticsearchRepository->delete($document, "");

        $document->setTransferStatus(Document::TRANSFER_QUEUED);
        $this->documentRepository->update($document);

        $exporter = new \EWW\Dpf\Services\MetsExporter();

        $fileData = $document->getFileData();

        $exporter->setFileData($fileData);

        $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData());

        $exporter->setMods($mods->getModsXml());

        $exporter->setSlubInfo($document->getSlubInfoData());

        $exporter->setObjId($document->getObjectIdentifier());

        $exporter->buildMets();

        $metsXml = $exporter->getMetsData();

        if ($this->remoteRepository->update($document, $metsXml)) {
            $this->invalidateMetsCache($document->getObjectIdentifier());
            $this->invalidateHostCache($mods->getHostUrn());
            $document->setTransferStatus(Document::TRANSFER_SENT);
            $this->documentRepository->update($document);
            $this->documentRepository->remove($document);

            return true;
        } else {
            $document->setTransferStatus(Document::TRANSFER_ERROR);
            $this->documentRepository->update($document);
            return false;
        }

    }

    /**
     * Gets an existing document from the Fedora repository
     *
     * @param string $remoteId
     * @return boolean
     */
    public function retrieve($remoteId)
    {

        $metsXml = $this->remoteRepository->retrieve($remoteId);

        if ($this->documentRepository->findOneByObjectIdentifier($remoteId)) {
            throw new \Exception("Document already exist: $remoteId");
        };

        if ($metsXml) {
            $mets = new \EWW\Dpf\Helper\Mets($metsXml);
            $mods = $mets->getMods();
            $slub = $mets->getSlub();

            $title   = $mods->getTitle();
            $authors = $mods->getAuthors();

            $documentTypeName = $slub->getDocumentType();
            $documentType     = $this->documentTypeRepository->findOneByName($documentTypeName);

            if (empty($title) || empty($documentType)) {
                return false;
            }

            $state = $mets->getState();

            switch ($state) {
                case "ACTIVE":
                    $objectState = Document::OBJECT_STATE_ACTIVE;
                    break;
                case "INACTIVE":
                    $objectState = Document::OBJECT_STATE_INACTIVE;
                    break;
                case "DELETED":
                    $objectState = Document::OBJECT_STATE_DELETED;
                    break;
                default:
                    $objectState = "ERROR";
                    throw new \Exception("Unknown object state: " . $state);
                    break;
            }

            $document = $this->objectManager->get(Document::class);
            $document->setObjectIdentifier($remoteId);
            $document->setState($objectState);
            $document->setTitle($title);
            $document->setAuthors($authors);
            $document->setDocumentType($documentType);

            $document->setXmlData($mods->getModsXml());
            $document->setSlubInfoData($slub->getSlubXml());

            $document->setDateIssued($mods->getDateIssued());

            $document->setProcessNumber($slub->getProcessNumber());

            $this->documentRepository->add($document);
            $this->persistenceManager->persistAll();

            foreach ($mets->getFiles() as $attachment) {

                $file = $this->objectManager->get(File::class);
                $file->setContentType($attachment['mimetype']);
                $file->setDatastreamIdentifier($attachment['id']);
                $file->setLink($attachment['href']);
                $file->setTitle($attachment['title']);
                $file->setLabel($attachment['title']);
                $file->setDownload($attachment['download']);
                $file->setArchive($attachment['archive']);
                $file->setFileGroupDeleted($attachment['deleted']);

                if ($attachment['id'] == \EWW\Dpf\Domain\Model\File::PRIMARY_DATASTREAM_IDENTIFIER) {
                    $file->setPrimaryFile(true);
                }

                $file->setDocument($document);

                $this->fileRepository->add($file);
            }

            return true;

        } else {
            return false;
        }

        return false;
    }

    /**
     * Removes an existing document from the Fedora repository
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @param string $state
     * @return boolean
     */
    public function delete($document, $state)
    {

        $document->setTransferStatus(Document::TRANSFER_QUEUED);
        $this->documentRepository->update($document);

            switch ($state) {
                case "revert":
                    if ($this->remoteRepository->delete($document, $state)) {
                        $this->invalidateMetsCache($document->getObjectIdentifier());
                        $document->setTransferStatus(Document::TRANSFER_SENT);
                        $document->setState(Document::OBJECT_STATE_ACTIVE);
                        $this->documentRepository->update($document);
                        return true;
                    }
                    break;
                case "inactivate":
                    if ($this->remoteRepository->delete($document, $state)) {
                        $this->invalidateMetsCache($document->getObjectIdentifier());
                        $document->setTransferStatus(Document::TRANSFER_SENT);
                        $document->setState(Document::OBJECT_STATE_INACTIVE);
                        $this->documentRepository->update($document);
                        return true;
                    }
                    break;
                default:
                    // remove document from local index
                    $elasticsearchRepository = $this->objectManager->get(ElasticsearchRepository::class);
                    $elasticsearchRepository->delete($document, $state);

                    if ($this->remoteRepository->delete($document, $state)) {
                        $this->invalidateMetsCache($document->getObjectIdentifier());
                        $document->setTransferStatus(Document::TRANSFER_SENT);
                        $document->setState(Document::OBJECT_STATE_DELETED);
                        $this->documentRepository->update($document);
                        $this->documentRepository->remove($document);
                        return true;
                    }
                    break;
            }

            $document->setTransferStatus(Document::TRANSFER_ERROR);
            $this->documentRepository->update($document);
            return false;
    }

    private function getRedisSettings()
    {
        $settings = [];
        if ($this->configurationManager !== null) {
            $all = $this->configurationManager->getConfiguration(
                \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS
            );
            if (is_array($all)) {
                $settings = $all;
            }
        }

        $host = '127.0.0.1';
        if (isset($settings['redisHost']) && $settings['redisHost'] !== '') {
            $host = $settings['redisHost'];
        }

        $port = 6379;
        if (isset($settings['redisPort']) && (int)$settings['redisPort'] > 0) {
            $port = (int)$settings['redisPort'];
        }

        $db = 4;
        if (isset($settings['redisDatabase'])) {
            $db = (int)$settings['redisDatabase'];
        }

        $timeout = 1.0;
        if (isset($settings['redisConnectTimeout']) && $settings['redisConnectTimeout'] !== '') {
            $timeout = (float)$settings['redisConnectTimeout'];
        }

        return ['host' => $host, 'port' => $port, 'db' => $db, 'timeout' => $timeout];
    }

    private function invalidateMetsCache(string $pid): void
    {
        $logger = $this->getLogger();
        try {
            $cfg = $this->getRedisSettings();
            $redis = new \Redis();
            if ($redis->connect($cfg['host'], $cfg['port'], $cfg['timeout'])) {
                $redis->select($cfg['db']);
                $redis->del('mets:' . $pid);
                $redis->del('slub-info:' . $pid);
                $redis->del('mods:' . $pid);
                $redis->del('dslist:' . $pid);
                if ($logger) $logger->info('invalidateMetsCache: cleared Redis keys for ' . $pid);
            } else {
                if ($logger) $logger->warning('invalidateMetsCache: Redis connect failed for ' . $pid);
            }
        } catch (\Throwable $e) {
            if ($logger) $logger->warning('invalidateMetsCache: exception for ' . $pid . ': ' . $e->getMessage());
        }
    }

    /**
     * Invalidates the cached parent METS for a child whose MODS references
     * the parent via relatedItem[@type="series"]/identifier[@type="urn"].
     *
     * The legacy convention stores the parent reference as a URN, but the
     * Fedora PID — under which GetFileController caches parent METS — is
     * qucosa:NNNNN. We resolve URN → Fedora PID via findObjects, then drop
     * both keys (the URN-keyed fallback covers any edge cache writes).
     */
    private function invalidateHostCache($hostUrn): void
    {
        $logger = $this->getLogger();
        if (!is_string($hostUrn) || $hostUrn === '') {
            if ($logger) $logger->info('invalidateHostCache: skipped — no host URN in child MODS');
            return;
        }
        if ($logger) $logger->info('invalidateHostCache: invalidating parent URN ' . $hostUrn);
        $this->invalidateMetsCache($hostUrn);
        $fedoraPid = $this->resolveFedoraPid($hostUrn);
        if ($fedoraPid !== null && $fedoraPid !== $hostUrn) {
            if ($logger) $logger->info('invalidateHostCache: resolved ' . $hostUrn . ' → ' . $fedoraPid . ', clearing PID cache');
            $this->invalidateMetsCache($fedoraPid);
        } else {
            if ($logger) $logger->warning('invalidateHostCache: could not resolve URN to Fedora PID — ' . $hostUrn . ' — only URN-keyed cache cleared');
        }
    }

    protected function resolveFedoraPid(string $urn): ?string
    {
        $logger = $this->getLogger();
        if ($this->clientConfigurationManager === null) {
            if ($logger) $logger->warning('resolveFedoraPid: clientConfigurationManager not injected');
            return null;
        }
        $host = $this->clientConfigurationManager->getFedoraHost();
        if (empty($host)) {
            if ($logger) $logger->warning('resolveFedoraPid: fedoraHost not configured');
            return null;
        }
        $url = rtrim('http://' . $host, '/')
            . '/fedora/objects?terms=' . rawurlencode($urn)
            . '&resultFormat=xml&pid=true&identifier=true&maxResults=20';
        try {
            $xml = $this->fetchFindObjectsXml($url);
            if ($xml === false || $xml === '') {
                if ($logger) $logger->warning('resolveFedoraPid: empty/false response from Fedora findObjects for URN ' . $urn . ' (url: ' . $url . ')');
                return null;
            }
            $doc = new \DOMDocument();
            $previous = libxml_use_internal_errors(true);
            $loaded = $doc->loadXML($xml);
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            if (!$loaded) {
                if ($logger) $logger->warning('resolveFedoraPid: XML parse failed for Fedora response, URN ' . $urn);
                return null;
            }
            $xpath = new \DOMXPath($doc);
            $xpath->registerNamespace('t', 'http://www.fedora.info/definitions/1/0/types/');
            // terms= is a full-text DC search — the parent URN also appears in children's
            // DC records, so multiple PIDs are returned. Find the one whose identifier
            // exactly equals the searched URN (i.e. the document that owns this URN).
            $objectFields = $xpath->query('//t:objectFields');
            if ($objectFields === false) {
                if ($logger) $logger->warning('resolveFedoraPid: XPath query failed for URN ' . $urn);
                return null;
            }
            foreach ($objectFields as $obj) {
                $identifiers = $xpath->query('t:identifier', $obj);
                if ($identifiers === false) {
                    continue;
                }
                foreach ($identifiers as $id) {
                    if (trim($id->nodeValue) === $urn) {
                        $pidNodes = $xpath->query('t:pid', $obj);
                        if ($pidNodes !== false && $pidNodes->length > 0) {
                            $value = trim($pidNodes->item(0)->nodeValue);
                            return $value !== '' ? $value : null;
                        }
                    }
                }
            }
            if ($logger) $logger->warning('resolveFedoraPid: no exact-match identifier found in Fedora results for URN ' . $urn . ' (maxResults=20, ' . $objectFields->length . ' objects returned)');
            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return string|false
     */
    private function getLogger()
    {
        if (!class_exists('TYPO3\CMS\Core\Utility\GeneralUtility')) {
            return null;
        }
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Log\LogManager::class
        )->getLogger(__CLASS__);
    }

    protected function fetchFindObjectsXml(string $url)
    {
        $ctx = stream_context_create(['http' => ['timeout' => 5]]);
        $result = @file_get_contents($url, false, $ctx);
        if ($result === false) {
            $logger = $this->getLogger();
            if ($logger) $logger->warning('fetchFindObjectsXml: file_get_contents failed for ' . $url);
        }
        return $result;
    }

    public function getNextDocumentId()
    {
        $nextDocumentIdXML = $this->remoteRepository->getNextDocumentId();

        if (empty($nextDocumentIdXML)) {
            throw new \Exception("Couldn't get a valid document id from repository.");
        }

        $dom = new \DOMDocument();
        $dom->loadXML($nextDocumentIdXML);
        $xpath = new \DOMXpath($dom);

        $xpath->registerNamespace("management", "http://www.fedora.info/definitions/1/0/management/");
        $nextDocumentId = $xpath->query("/management:pidList/management:pid");

        return $nextDocumentId->item(0)->nodeValue;
    }

}
