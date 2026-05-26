<?php

/**
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
 *
 * API to return METS dissemination and Attachments from Fedora.
 * Also renders METS XML for preview. Structure of the URIs totally
 * depend on proper RealURL configuration.
 *
 * Examples:
 *
 * 1. METS from Fedora
 *    http://localhost/api/qucosa:1234/mets/
 *
 *    This always returns METS which is supplemented with additional information.
 *    The embedded MODS record is not the original MODS as it is stored in the
 *    repository datastream.
 *
 * 2. Attachment from Fedora
 *    http://localhost/api/qucosa:1234/attachment/ATT-0/
 *
 * 3. METS from Kitodo.Publication (this extension)
 *    http://localhost/api/3/preview/
 *
 * 4. DataCite from Kitodo.Publication (this extension)
 *    http://localhost/api/3/datacite
 *    http://localhost/api/qucosa:1234/datacite
 *
 *    Returns a DataCite XML document generated from either a local preview METS
 *    document or from a remote repository METS document.
 *
 * 5. Zip from Fedora
 *    http://localhost/api/qucosa:1234/zip
 *
 *    Returns a ZIP transfer file containing all attachments required for DNB harvesting.
 *
 * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @author Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @author Florian Rügamer <florian.ruegamer@slub-dresden.de>
 */

namespace EWW\Dpf\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use EWW\Dpf\Helper\DataCiteXml;
use EWW\Dpf\Helper\SlubInfoHelper;
use EWW\Dpf\Services\MetsExporter;
use Exception;

/**
 * GetFileController
 */
class GetFileController extends \EWW\Dpf\Controller\AbstractController
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

    public function dispatchAction()
    {
        $queryParams = $this->collectEntries(
            ["action", "attachment", "deliverInactive", "qid"],
            GeneralUtility::_GP('tx_dpf')
        );
        $settings = $this->collectEntries(["allowedActions", "deliverInactiveSecretKey"], $this->settings);

        $params = array_merge($queryParams, $settings);

        $action = $params['action'];
        $attachmentId = $params['attachment'];
        $qid = $params['qid'];
        $deliverInactiveKey = $params['deliverInactive'];
        $deliverInactiveKeySecretKey = $params['deliverInactiveSecretKey'];
        $restrictToActive = !hash_equals((string)$deliverInactiveKeySecretKey, (string)$deliverInactiveKey);

        $allowedActions = $params['allowedActions'];

        try {
            // check if required parameters are present
            if (!$action) {
                throw new Exception("No action given", 400);
            }
            if (!$qid) {
                throw new Exception("Missing parameter `qid`", 400);
            }

            // check if action is allowed
            if ($allowedActions === null) {
                $allowedActions = [];
            }
            $this->assertActionAllowed($allowedActions, $action);

            $fedoraHost = $this->clientConfigurationManager->getFedoraHost();
            $isRepositoryObject = !is_numeric($qid);

            if ($isRepositoryObject && !SlubInfoHelper::isValidPid($qid)) {
                throw new Exception("Invalid document identifier", 400);
            }
            if (!empty($attachmentId) && !SlubInfoHelper::isValidDsid($attachmentId)) {
                throw new Exception("Invalid attachment identifier", 400);
            }

            $this->assertAccessAllowed(
                $isRepositoryObject,
                $restrictToActive,
                $fedoraHost,
                $qid
            );

            switch ($action) {
                case 'mets':
                    $this->metsAction($fedoraHost, $qid);
                    break;
                case 'preview':
                    return $this->previewAction($qid);
                    break;
                case 'attachment':
                    if (!$attachmentId) {
                        throw new Exception("Missing parameter `attachment`", 400);
                    }
                    $this->attachmentAction($fedoraHost, $qid, $attachmentId, $isRepositoryObject, $restrictToActive);
                    break;
                case 'dataCite':
                    return $this->dataCiteAction($fedoraHost, $qid, $isRepositoryObject);
                    break;
                case 'zip':
                    $this->zipAction($fedoraHost, $qid);
                    break;
                default:
                    throw new Exception("No such action", 400);
            }
        } catch (Exception $e) {
            $this->response->setStatus($e->getCode());
            return $e->getMessage();
        }
    }

    private function zipAction(string $fedoraHost, string $pid)
    {
        // FIXME Service locations on Fedora host are hard coded
        $metsUrl = rtrim('http://' . $fedoraHost, "/") . '/mets?pid=' . $pid;
        $contentUri = rtrim('http://' . $fedoraHost, "/")
            . '/zip?xmdpfilter=true&metsurl='
            . rawurlencode($metsUrl);
        $headCtx = stream_context_create(['http' => ['method' => 'HEAD', 'timeout' => 90]]);
        $resourceHeaders = get_headers($contentUri, false, $headCtx);
        $this->copyHeaders($resourceHeaders);
        header_remove("Location"); // don't communicate backend URL
        $this->streamAndExit($contentUri, "application/zip");
    }

    private function dataCiteAction(string $fedoraHost, string $qid, bool $isRepositoryObject)
    {
        if ($isRepositoryObject) {
            $metsUri = rtrim('http://' . $fedoraHost, "/")
                . '/fedora/objects/' . $qid
                . '/methods/qucosa:SDef/getMETSDissemination?supplement=yes';
            $ctx = stream_context_create(['http' => ['timeout' => 90]]);
            $metsXml = file_get_contents($metsUri, false, $ctx);
        } else {
            $metsXml = $this->buildPreviewDocument($qid);
        }

        if (!$metsXml) {
            throw new Exception("No such document", 404);
        }

        $dataCiteRecord = $this->buildDataCiteRecord($metsXml);
        $this->setHeader('Content-Type', 'text/xml; charset=UTF-8');
        $this->setHeader(
            'Content-Disposition',
            'attachment; filename="' . $dataCiteRecord['filename'] . '"'
        );
        return $dataCiteRecord['content'];
    }

    private function attachmentAction(string $fedoraHost, string $qid, string $attachmentId, bool $isRepositoryObject, bool $restrictToActive)
    {
        $document = null;
        $fedoraBase = rtrim('http://' . $fedoraHost, '/');
        $slubInfoXml = null;

        $redis = $this->createRedisConnection();

        if ($isRepositoryObject) {
            $contentUri = $this->buildAttachmentURI($fedoraHost, $qid, $attachmentId);
            $contentType = null; // use content type from remote resource
            if (empty($contentUri)) {
                throw new Exception("No file found", 404);
            }

            // Fetch SLUB-INFO once — used for both the downloadable check and filename generation
            $slubInfoXml = $this->fetchFedoraDatastream(
                $fedoraBase . '/fedora/objects/' . $qid . '/datastreams/SLUB-INFO/content',
                'slub-info:' . $qid,
                $redis
            );
            if ($slubInfoXml === false) {
                throw new Exception("Cannot obtain datastream access conditions", 500);
            }
            if ($restrictToActive && !SlubInfoHelper::isDownloadable($slubInfoXml, $attachmentId)) {
                throw new Exception("File is not accessible", 403);
            }

            $document = $this->documentRepository->findByObjectIdentifier($qid);
        } else {
            $document = $this->documentRepository->findByUid($qid);
            if (!$document) {
                throw new Exception("No such document", 404);
            }
            $file = $this->findFileObject($document, $attachmentId);
            if (!$file) {
                throw new Exception("No such file", 404);
            }
            if (!$file["download"]) {
                throw new Exception("File is not accessible", 403);
            }
            $contentUri = $file['path'];
            $contentType = $file['type']; // override default content-type
        }

        $headCtx = stream_context_create(['http' => ['method' => 'HEAD', 'timeout' => 90]]);
        $resourceHeaders = get_headers($contentUri, false, $headCtx);
        if (!$resourceHeaders) {
            throw new Exception("Cannot fetch remote resource headers", 500);
        }
        $this->copyHeaderOrSetDefault($resourceHeaders, 'Content-Disposition', 'attachment');
        $this->copyHeaderOrSetDefault($resourceHeaders, 'Content-Type', $contentType);
        $this->copyHeaderOrSetDefault($resourceHeaders, 'Content-Length', null);

        if ($document) {
            $mimeType = $contentType;
            if (empty($mimeType)) {
                foreach ($resourceHeaders as $header) {
                    if (stripos($header, 'Content-Type:') === 0) {
                        $mimeType = trim(substr($header, 13));
                        break;
                    }
                }
            }

            $allFiles = $document->getCurrentFileData();
            $downloadFiles = array_values(isset($allFiles['download']) ? $allFiles['download'] : []);
            $totalFiles = count($downloadFiles);
            $fileIndex = 0;
            foreach ($downloadFiles as $idx => $f) {
                if ($f['id'] == $attachmentId) {
                    $fileIndex = $idx;
                    break;
                }
            }

            if ($attachmentId !== \EWW\Dpf\Domain\Model\File::PRIMARY_DATASTREAM_IDENTIFIER && isset($downloadFiles[$fileIndex])) {
                $fileLabel = $downloadFiles[$fileIndex]['title'] ?? '';
                if (!empty($fileLabel)) {
                    $ext = \EWW\Dpf\Service\FilenameGenerator::mimeToExtension($mimeType);
                    $filename = SlubInfoHelper::sanitizeFilenameLabel($fileLabel, $ext);
                    if (!empty($filename)) {
                        $this->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
                        $this->streamAndExit($contentUri);
                    }
                }
            }

            $generator = GeneralUtility::makeInstance(\EWW\Dpf\Service\FilenameGenerator::class);
            $swordNamespace = $this->clientConfigurationManager->getSwordCollectionNamespace();
            $filename = $generator->generate(
                $document->getXmlData(),
                $document->getSlubInfoData(),
                $swordNamespace,
                $mimeType,
                $fileIndex,
                $totalFiles
            );

            if (!empty($filename)) {
                $this->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
            }
        } elseif ($isRepositoryObject) {
            $mimeType = '';
            foreach ($resourceHeaders as $header) {
                if (stripos($header, 'Content-Type:') === 0) {
                    $mimeType = trim(substr($header, 13));
                    break;
                }
            }

            if ($attachmentId !== \EWW\Dpf\Domain\Model\File::PRIMARY_DATASTREAM_IDENTIFIER) {
                $dsList = $this->fetchFedoraDatastreamList($fedoraBase, $qid, $redis);
                if ($dsList !== null && isset($dsList[$attachmentId]) && !empty($dsList[$attachmentId]['label'])) {
                    $ext = \EWW\Dpf\Service\FilenameGenerator::mimeToExtension($mimeType);
                    $filename = SlubInfoHelper::sanitizeFilenameLabel($dsList[$attachmentId]['label'], $ext);
                    if (!empty($filename)) {
                        $this->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
                        $this->streamAndExit($contentUri);
                    }
                }
            }

            // Primary file, or secondary with no usable label → FilenameGenerator
            $modsXml = $this->fetchFedoraDatastream(
                $fedoraBase . '/fedora/objects/' . $qid . '/datastreams/MODS/content',
                'mods:' . $qid,
                $redis
            );

            // $slubInfoXml already fetched above for the access check — reused here
            if (!empty($modsXml) && !empty($slubInfoXml)) {
                $generator = GeneralUtility::makeInstance(\EWW\Dpf\Service\FilenameGenerator::class);
                $swordNamespace = $this->clientConfigurationManager->getSwordCollectionNamespace();
                $filename = $generator->generate($modsXml, $slubInfoXml, $swordNamespace, $mimeType, 0, 1);
                if (!empty($filename)) {
                    $this->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
                }
            }
        }

        $this->streamAndExit($contentUri);
    }

    private function previewAction(string $qid)
    {
        $metsXml = $this->buildPreviewDocument($qid);
        if (!$metsXml) {
            throw new Exception("No such document", 404);
        }
        $this->setHeader('Content-Type', 'text/xml; charset=UTF-8');
        return $metsXml;
    }

    private function metsAction(string $fedoraHost, string $pid)
    {
        $contentUri = rtrim('http://' . $fedoraHost, "/")
            . '/fedora/objects/' . $pid
            . '/methods/qucosa:SDef/getMETSDissemination?supplement=yes';

        $filename = $this->sanitizeFilename($pid . ".mets.xml");
        $cacheKey = 'mets:' . $pid;

        $ttl = 86400;
        if (isset($this->settings['metsCacheTtl'])) {
            $ttl = (int)$this->settings['metsCacheTtl'];
        }

        $redisHost = '127.0.0.1';
        if (isset($this->settings['redisHost']) && $this->settings['redisHost'] !== '') {
            $redisHost = $this->settings['redisHost'];
        }

        $redisPort = 6379;
        if (isset($this->settings['redisPort']) && (int)$this->settings['redisPort'] > 0) {
            $redisPort = (int)$this->settings['redisPort'];
        }

        $redisDb = 4;
        if (isset($this->settings['redisDatabase'])) {
            $redisDb = (int)$this->settings['redisDatabase'];
        }

        $redisTimeout = 1.0;
        if (isset($this->settings['redisConnectTimeout']) && $this->settings['redisConnectTimeout'] !== '') {
            $redisTimeout = (float)$this->settings['redisConnectTimeout'];
        }

        try {
            $redis = new \Redis();
            if ($redis->connect($redisHost, $redisPort, $redisTimeout)) {
                $redis->select($redisDb);
                $cached = $redis->get($cacheKey);
                if ($cached !== false) {
                    $this->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
                    $this->setHeader('Content-Type', 'text/xml; charset=UTF-8');
                    $this->setHeader('Content-Length', (string)strlen($cached));
                    $this->setHeader('X-Cache', 'HIT');
                    session_write_close();
                    ob_end_clean();
                    echo $cached;
                    exit;
                }
            }
        } catch (\Throwable $e) {
            // Redis ext missing, unavailable, or error — continue to live Fedora fetch
        }

        $ctx = stream_context_create(['http' => ['timeout' => 90]]);
        $metsXml = file_get_contents($contentUri, false, $ctx);
        if ($metsXml === false) {
            throw new Exception("Error while fetching METS content", 500);
        }

        try {
            $redis = new \Redis();
            if ($redis->connect($redisHost, $redisPort, $redisTimeout)) {
                $redis->select($redisDb);
                $redis->set($cacheKey, $metsXml, $ttl);
            }
        } catch (\Throwable $e) {
            // Redis ext missing, unavailable, or error — response still served without caching
        }

        $this->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $this->setHeader('Content-Type', 'text/xml; charset=UTF-8');
        $this->setHeader('Content-Length', (string)strlen($metsXml));
        $this->setHeader('X-Cache', 'MISS');
        session_write_close();
        ob_end_clean();
        echo $metsXml;
        exit;
    }

    /**
     * Check document-level access: object must be active unless the caller holds the bypass key.
     * Attachment-level (datastream downloadable) check is handled in attachmentAction().
     */
    private function assertAccessAllowed(bool $isRepositoryObject, bool $restrictToActive, string $fedoraHost, string $pid)
    {
        if ($isRepositoryObject && $restrictToActive) {
            $this->assertActiveFedoraObject($fedoraHost, $pid);
        }
    }

    private function assertActionAllowed(array $allowedActions, string $action)
    {
        if (!in_array($action, $allowedActions)) {
            throw new Exception("Forbidden", 403);
        }
    }

    private function assertActiveFedoraObject(string $fedoraHost, string $pid)
    {
        $objectState = $this->fedoraObjectState($fedoraHost, $pid);

        if ($objectState === null) {
            throw new Exception("Cannot obtain object state", 500);
        }
        if ($objectState === 'I') {
            throw new Exception("Forbidden", 403);
        }
        if ($objectState === 'D') {
            throw new Exception("Not Found", 404);
        }
    }

    /**
     * Stream the file content at $uri to output buffer.
     *
     * Ends all open sessions and disables all output buffering for
     * streaming potentially large files.
     *
     * @param string $uri URI of the content to stream.
     * @throws Exception thrown when file cannot be streamed.
     */
    private function streamAndExit(string $contentUri)
    {
        $streamingException = new Exception("Error while streaming content", 500);

        ini_set('default_socket_timeout', 1800);
        $stream = fopen($contentUri, 'r');
        if ($stream === false) {
            throw $streamingException;
        }
        session_write_close(); // close active session if any
        if (!ob_end_clean()) { // stop output buffering
            throw $streamingException;
        }
        if (fpassthru($stream) === false) {
            throw $streamingException;
        }
        if (!fclose($stream)) {
            throw $streamingException;
        }
        exit; // Hard exit PHP script to avoid sending TYPO3 framework HTTP artifacts
    }

    /**
     * Fetch the Fedora datastream list for a PID, returning a cached result from Redis.
     * Returns an associative array keyed by DSID with 'label' and 'mimeType', or null on failure.
     *
     * @param string $fedoraBase  Base Fedora URL (no trailing slash)
     * @param string $pid         Fedora PID
     * @param \Redis|null $redis  Connected Redis instance, or null
     * @return array|null
     */
    private function fetchFedoraDatastreamList(string $fedoraBase, string $pid, $redis)
    {
        $cacheKey = 'dslist:' . $pid;
        $ttl = 86400;
        if (isset($this->settings['metsCacheTtl'])) {
            $ttl = (int)$this->settings['metsCacheTtl'];
        }

        if ($redis !== null) {
            try {
                $cached = $redis->get($cacheKey);
                if ($cached !== false) {
                    $decoded = json_decode($cached, true);
                    if (is_array($decoded)) {
                        return $decoded;
                    }
                }
            } catch (\Throwable $e) {
                // Redis error — fall through to live fetch
            }
        }

        $url = $fedoraBase . '/fedora/objects/' . $pid . '/datastreams?format=xml';
        $ctx = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 10]]);
        $xml = @file_get_contents($url, false, $ctx);
        if ($xml === false) {
            return null;
        }

        $dom = new \DOMDocument();
        $prevErrors = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xml);
        libxml_use_internal_errors($prevErrors);
        if (!$loaded) {
            return null;
        }

        $result = [];
        $ns = 'http://www.fedora.info/definitions/1/0/access/';
        $nodes = $dom->getElementsByTagNameNS($ns, 'datastream');
        if ($nodes->length === 0) {
            $nodes = $dom->getElementsByTagName('datastream');
        }
        foreach ($nodes as $node) {
            $dsid = $node->getAttribute('dsid');
            if (empty($dsid)) {
                continue;
            }
            $result[$dsid] = [
                'label'    => $node->getAttribute('label'),
                'mimeType' => $node->getAttribute('mimeType'),
            ];
        }

        if (!empty($result) && $redis !== null) {
            try {
                $redis->set($cacheKey, json_encode($result), $ttl);
            } catch (\Throwable $e) {
                // Redis error — not critical
            }
        }

        return !empty($result) ? $result : null;
    }

    /**
     * Fetch a Fedora datastream XML, returning a cached copy from Redis when available.
     * Falls back to a live Fedora fetch on Redis miss or when Redis is unavailable.
     *
     * @param string $uri      Full URL of the Fedora datastream
     * @param string $cacheKey Redis key (e.g. "slub-info:qucosa:1234")
     * @param \Redis|null $redis Connected Redis instance selected to the correct DB, or null
     * @return string|false XML string, or false on fetch failure
     */
    private function fetchFedoraDatastream(string $uri, string $cacheKey, $redis)
    {
        $ttl = 86400;
        if (isset($this->settings['metsCacheTtl'])) {
            $ttl = (int)$this->settings['metsCacheTtl'];
        }

        if ($redis !== null) {
            try {
                $cached = $redis->get($cacheKey);
                if ($cached !== false) {
                    return $cached;
                }
            } catch (\Throwable $e) {
                // Redis error — fall through to live fetch
            }
        }

        $ctx = stream_context_create(['http' => ['timeout' => 90]]);
        $xml = file_get_contents($uri, false, $ctx);

        if ($xml !== false && $redis !== null) {
            try {
                $redis->set($cacheKey, $xml, $ttl);
            } catch (\Throwable $e) {
                // Redis error — cached value lost, not critical
            }
        }

        return $xml;
    }

    /**
     * Create and return a Redis connection selected to the configured datastream cache DB.
     * Returns null if Redis is unavailable or the extension is missing.
     *
     * @return \Redis|null
     */
    private function createRedisConnection()
    {
        $host = '127.0.0.1';
        if (isset($this->settings['redisHost']) && $this->settings['redisHost'] !== '') {
            $host = $this->settings['redisHost'];
        }

        $port = 6379;
        if (isset($this->settings['redisPort']) && (int)$this->settings['redisPort'] > 0) {
            $port = (int)$this->settings['redisPort'];
        }

        $db = 4;
        if (isset($this->settings['redisDatabase'])) {
            $db = (int)$this->settings['redisDatabase'];
        }

        $timeout = 1.0;
        if (isset($this->settings['redisConnectTimeout']) && $this->settings['redisConnectTimeout'] !== '') {
            $timeout = (float)$this->settings['redisConnectTimeout'];
        }

        try {
            $redis = new \Redis();
            if ($redis->connect($host, $port, $timeout)) {
                $redis->select($db);
                return $redis;
            }
        } catch (\Throwable $e) {
            // Redis ext missing, unavailable, or error
        }

        return null;
    }

    private function setHeader(string $header, string $value)
    {
        header(trim($header) . ": " . trim($value));
    }

    private function copyHeaders(array $headers)
    {
        foreach ($headers as $header) {
            header($header);
        }
    }

    private function copyHeaderOrSetDefault(array $headers, string $header, $value)
    {
        // case-insensitive check if $header is a value in $headers
        $matchingHeaders = preg_grep("/^" . $header . "/i", $headers);
        if ($matchingHeaders) {
            header(current($matchingHeaders));
        } else {
            if ($value != null) {
                header(trim($header) . ": " . trim($value));
            }
        }
    }

    private function fedoraObjectState($fedoraHost, $pid)
    {
        $objectProfileURI = rtrim('http://' . $fedoraHost, "/") . '/fedora/objects/' . $pid . '?format=XML';
        $ctx = stream_context_create(['http' => ['timeout' => 90]]);
        $objectProfileXML = file_get_contents($objectProfileURI, false, $ctx);

        if (false !== $objectProfileXML) {
            $objectProfileDOM = new \DOMDocument('1.0', 'UTF-8');
            if (true === $objectProfileDOM->loadXML($objectProfileXML)) {
                $objectState = $objectProfileDOM->getElementsByTagName('objState')[0];
                return $objectState->nodeValue;
            }
        }

        return null;
    }

    private function buildDataCiteRecord($metsXml)
    {
        $dataCiteXml = DataCiteXml::convertFromMetsXml($metsXml);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($dataCiteXml);
        $title = $dom->getElementsByTagName('title')[0];
        $filename = $this->sanitizeFilename($title->nodeValue) . '.DataCite.xml';
        return [
            "filename" => $filename,
            "content" => $dom->saveXML()
        ];
    }

    private function findFileObject($document, $attachmentId)
    {
        $files = $document->getCurrentFileData();

        foreach ($files['original'] as $file) {
            if ($file['id'] == $attachmentId) {
                return $file;
            }
        }
        foreach ($files['download'] as $file) {
            if ($file['id'] == $attachmentId) {
                return $file;
            }
        }

        return null;
    }

    private function buildAttachmentURI($fedoraHost, $pid, $dsid)
    {
        return rtrim('http://' . $fedoraHost, "/")
            . '/fedora/objects/' . $pid
            . '/datastreams/' . $dsid . '/content';
    }

    private function buildPreviewDocument($pid)
    {
        $document = $this->documentRepository->findByUid($pid);

        if ($document) {
            $metsXml = $this->buildMetsXml($document);
            return $metsXml;
        } else {
            return null;
        }
    }

    private function sanitizeFilename($filename)
    {
        // remove anything which isn't a word, whitespace, number or any of the following caracters -_~,;[]().
        $filename = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $filename);
        // turn diacritical characters to ASCII
        setlocale(LC_ALL, 'en_US.utf8');
        $filename = iconv('utf-8', 'us-ascii//TRANSLIT', trim($filename));
        // replace whitespaces with underscore
        $filename = preg_replace('/\s+/', '_', $filename);

        return $filename;
    }

    private function buildMetsXml($document)
    {
        $exporter = new MetsExporter();
        $fileData = $document->getCurrentFileData();
        $exporter->setFileData($fileData);
        $exporter->setMods($document->getXmlData());
        $exporter->setSlubInfo($document->getSlubInfoData());

        if (empty($document->getObjectIdentifier())) {
            $exporter->setObjId($document->getUid());
        } else {
            $exporter->setObjId($document->getObjectIdentifier());
        }

        $exporter->buildMets();
        $metsXml = $exporter->getMetsData();

        return $metsXml;
    }

    /**
     * Returns an array of key-value pairs from a given array.
     * If a particular key is not present, it's value is set to null.
     *
     * @param $keys Array of keys for which to collect entries.
     * @param $from Source array from which to collect entries.
     * @return array Associative array containing the values or nulls.
     */
    private function collectEntries(array $keys, $from = [])
    {
        $result = [];
        foreach ($keys as $k) {
            $result[$k] = (array_key_exists($k, $from)) ? $from[$k] : null;
        }
        return $result;
    }
}
