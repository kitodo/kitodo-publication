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
use EWW\Dpf\Configuration\ClientConfigurationManager;
use EWW\Dpf\Domain\Repository\DocumentRepository;
use EWW\Dpf\Helper\DataCiteXml;
use EWW\Dpf\Services\MetsExporter;

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
        $queryParams = $this->getQueryParameters();

        $action = $queryParams['action'];
        $qid = $queryParams['qid'];
        $attachmentId = $queryParams['attachment'];
        $deliverInactiveKey = $queryParams['deliverInactive'];

        if ($this->isForbidden($action)) {
            $this->response->setStatus(403);
            return "Forbidden";
        }

        if (!$qid) {
            $this->response->setStatus(400);
            return 'Bad Request';
        }

        $fedoraHost = $this->clientConfigurationManager->getFedoraHost();
        $isRepositoryObject = !is_numeric($qid);
        $contentType = "text/xml; charset=UTF-8"; // default content-type

        switch ($action) {
            case 'mets':
                $contentUri = $this->buildMetsURI($fedoraHost, $qid);
                break;

            case 'preview':
                $metsXml = $this->buildPreviewDocument($qid);

                if (!$metsXml) {
                    $this->response->setStatus(404);
                    return 'No such document';
                }

                $this->response->setHeader('Content-Type', 'text/xml; charset=UTF-8');
                return $metsXml;

                break;

            case 'attachment':
                if ($isRepositoryObject) {
                    $contentUri = $this->buildAttachmentURI($fedoraHost, $qid, $attachmentId);
                    if (empty($contentUri)) {
                        $this->response->setStatus(404);
                        return 'No file found';
                    }
                } else {
                    $document = $this->documentRepository->findByUid($qid);
                    if (!$document) {
                        $this->response->setStatus(404);
                        return 'No such document';
                    }
                    $file = $this->findFileObject($document, $attachmentId);
                    if (!$file) {
                        $this->response->setStatus(404);
                        return 'No file found';
                    }
                    $contentUri = $file['path'];
                    $contentType = $file['type']; // override default content-type
                }
                break;

            case 'dataCite':
                if ($isRepositoryObject) {
                    $contentUri = $this->buildMetsURI($fedoraHost, $qid);
                    $metsXml = file_get_contents($contentUri);
                } else {
                    $metsXml = $this->buildPreviewDocument($qid);
                }

                if (!$metsXml) {
                    $this->response->setStatus(404);
                    return 'No such document';
                }

                $dataCiteRecord = $this->buildDataCiteRecord($metsXml);
                $this->response->setHeader('Content-Type', 'text/xml; charset=UTF-8');
                $this->response->setHeader(
                    'Content-Disposition',
                    'attachment; filename="' . $dataCiteRecord['filename'] . '"'
                );
                return $dataCiteRecord['content'];

                break;

            case 'zip':
                // FIXME Service locations on Fedora host are hard coded
                $metsUrl = rtrim('http://' . $fedoraHost, "/") . '/mets?pid=' . $qid;
                $contentUri = rtrim('http://' . $fedoraHost, "/")
                    . '/zip?xmdpfilter=true&metsurl='
                    . rawurlencode($metsUrl);
                break;

            default:
                $this->response->setStatus(404);
                return 'No such action';
        }

        // default is to restrict access
        $restrictToActiveDocuments = true;

        // if the given secret key matches the configured secret key, lift above restriction
        $deliverInactiveKeySecretKey = $this->settings['deliverInactiveSecretKey'];
        if ($deliverInactiveKeySecretKey == $deliverInactiveKey) {
            $restrictToActiveDocuments = false;
        }

        // if restriction applies, check object state before dissemination
        if ($isRepositoryObject && $restrictToActiveDocuments) {
            $objectState = $this->fedoraObjectState($fedoraHost, $qid);

            if ($objectState === null) {
                $this->response->setStatus(500);
                return 'Internal Server Error';
            }
            if ($objectState === 'I') {
                $this->response->setStatus(403);
                return 'Forbidden';
            }
            if ($objectState === 'D') {
                $this->response->setStatus(404);
                return 'Not Found';
            }
        }

        // Get headers from from remote resource and copy them to the response
        $resourceHeaders = get_headers($contentUri);
        $this->copyHeaderOrSetIfNotNull($resourceHeaders, 'Content-Disposition', 'attachment');
        $this->copyHeaderOrSetIfNotNull($resourceHeaders, 'Content-Type', $contentType);
        $this->copyHeaderOrSetIfNotNull($resourceHeaders, 'Content-Length', null);

        if ($this->streamFile($contentUri)) {
            exit; // Hard exit PHP script to avoid sending TYPO3 framework HTTP artifacts
        } else {
            $this->response->setStatus(500);
            return 'Error while streaming content';
        }
    }

    /**
     * Stream the file content at $uri to output buffer.
     *
     * Ends all open sessions and disables all output buffering for
     * streaming potentially large files.
     *
     * @param string $uri URI of the content to stream.
     * @return bool True if streaming was successful, false if not.
     */
    private function streamFile(string $uri)
    {
        $stream = fopen($uri, 'r');
        if ($stream === false) {
            return false;
        }
        session_write_close(); // close active session if any
        ob_end_clean(); // stop output buffering
        fpassthru($stream);
        fclose($stream);
        return true;
    }

    private function copyHeaderOrSetIfNotNull(array $headers, string $header, $value)
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

    public function fedoraObjectState($fedoraHost, $pid)
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

    private function buildMetsURI($fedoraHost, $pid)
    {
        return rtrim('http://' . $fedoraHost, "/")
            . '/fedora/objects/' . $pid
            . '/methods/qucosa:SDef/getMETSDissemination?supplement=yes';
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

    private function isForbidden($action)
    {
        $allowed =
            array_key_exists('allowedActions', $this->settings)
            && is_array($this->settings['allowedActions'])
            && in_array($action, $this->settings['allowedActions']);
        return !$allowed;
    }

    /**
     * Returns an associative array of query parameters.
     *
     * If a parameter is not set, it's array value is null.
     *
     * @return array Associative array containing the parameters.
     */
    private function getQueryParameters()
    {
        $queryParams = GeneralUtility::_GP('tx_dpf');
        if ($queryParams === null) {
            $queryParams = [];
        }
        $result = [];
        $params = ["action", "attachment", "deliverInactive", "qid"];
        foreach ($params as $p) {
            $result[$p] = (array_key_exists($p, $queryParams)) ? $queryParams[$p] : null;
        }
        return $result;
    }
}
