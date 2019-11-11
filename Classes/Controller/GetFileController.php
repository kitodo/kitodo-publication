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

use DOMXPath;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use EWW\Dpf\Configuration\ClientConfigurationManager;
use EWW\Dpf\Domain\Repository\DocumentRepository;
use EWW\Dpf\Helper\DataCiteXml;
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
     * @var DocumentRepository
     * @inject
     */
    protected $documentRepository;

    /**
     * clientConfigurationManager
     *
     * @var ClientConfigurationManager
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

            $this->assertAccessAllowed(
                $isRepositoryObject,
                $deliverInactiveKey,
                $deliverInactiveKeySecretKey,
                $fedoraHost,
                $qid,
                $attachmentId
            );

            switch ($action) {
                case 'mets':
                    $this->metsAction($fedoraHost, $qid);
                    break;
                case 'preview':
                    return $this->previewAction($qid);
                    break;
                case 'attachment':
                    $this->attachmentAction($fedoraHost, $qid, $attachmentId, $isRepositoryObject);
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
        $resourceHeaders = get_headers($contentUri);
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
            $metsXml = file_get_contents($metsUri);
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

    private function attachmentAction(string $fedoraHost, string $qid, string $attachmentId, bool $isRepositoryObject)
    {
        if ($isRepositoryObject) {
            $contentUri = $this->buildAttachmentURI($fedoraHost, $qid, $attachmentId);
            $contentType = null; // use content type from remote resource
            if (empty($contentUri)) {
                throw new Exception("No file found", 404);
            }
        } else {
            $document = $this->documentRepository->findByUid($qid);
            if (!$document) {
                throw new Exception("No such document", 404);
            }
            $file = $this->findFileObject($document, $attachmentId);
            if (!$file) {
                throw new Exception("No file found", 404);
            }
            $contentUri = $file['path'];
            $contentType = $file['type']; // override default content-type
        }

        $resourceHeaders = get_headers($contentUri);
        $this->copyHeaderOrSetDefault($resourceHeaders, 'Content-Disposition', 'attachment');
        $this->copyHeaderOrSetDefault($resourceHeaders, 'Content-Type', $contentType);
        $this->copyHeaderOrSetDefault($resourceHeaders, 'Content-Length', null);
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

        $resourceHeaders = get_headers($contentUri);
        $this->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $this->copyHeaderOrSetDefault($resourceHeaders, 'Content-Type', "text/xml; charset=UTF-8");
        $this->copyHeaderOrSetDefault($resourceHeaders, 'Content-Length', null);
        $this->streamAndExit($contentUri);
    }

    private function assertAccessAllowed($isRepositoryObject, $givenKey, $secretKey, $fedoraHost, $pid, $dsid)
    {
        // if the given secret key matches the configured secret key, lift restriction
        $restrictToActiveDocuments = ($secretKey !== $givenKey);

        // if restriction applies, check object state before dissemination
        if ($isRepositoryObject && $restrictToActiveDocuments) {
            $this->assertActiveFedoraObject($fedoraHost, $pid);
        }

        // if datastream id is given, check datastream download metadata
        if ($dsid !== null) {
            $downloadable = $this->datastreamDownloadCondition($fedoraHost, $pid, $dsid);
            if (!$downloadable && $restrictToActiveDocuments) {
                throw new Exception("File is not accessible", 403);
            }
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

    /**
     * Check if a datastream is downloadable.
     *
     * Loads SLUB-INFO metadata for the specified object and checks for appropriate
     * metadata which grants download access to the specified datastream.
     *
     * @param string $fedoraHost Host of the Fedora system
     * @param string $pid        Fedora object identifier
     * @param string $dsid       Fedora object datastream identifier
     * @return bool True if datastream is downloadable. False otherwise.
     */
    private function datastreamDownloadCondition(string $fedoraHost, string $pid, $dsid)
    {
        $slubInfoURI = rtrim('http://' . $fedoraHost, "/")
            . '/fedora/objects/' . $pid
            . '/datastreams/SLUB-INFO/content';
        $slubInfoXML = file_get_contents($slubInfoURI);

        if (false !== $slubInfoXML) {
            $slubInfoDOM = new \DOMDocument('1.0', 'UTF-8');
            if (true === $slubInfoDOM->loadXML($slubInfoXML)) {
                $xpath = new DOMXPath($slubInfoDOM);
                $xpath->registerNamespace('slub', 'http://slub-dresden.de/');

                $query = '//slub:attachment[@ref="' . $dsid . '" and @isDownloadable="yes"]';
                $match = $xpath->evaluate($query);

                return ($match !== null) && ($match->count() > 0);
            }
        }

        throw new Exception("Cannot obtain datastream access conditions", 500);
    }

    private function fedoraObjectState($fedoraHost, $pid)
    {
        $objectProfileURI = rtrim('http://' . $fedoraHost, "/") . '/fedora/objects/' . $pid . '?format=XML';
        $objectProfileXML = file_get_contents($objectProfileURI);

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

        foreach ($files['download'] as $id => $file) {
            if ($file['id'] == $attachmentId) {
                return $file;
                break;
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
