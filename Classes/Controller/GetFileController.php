<?php /** @noinspection PhpUnused */

namespace EWW\Dpf\Controller;

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
use EWW\Dpf\Domain\Model\File;
use EWW\Dpf\Helper\XSLTransformator;
use EWW\Dpf\Services\ParserGenerator;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides endpoint controller to access METS dissemination and Attachments and
 * other dissemination services for a configured Fedora repository.
 * Also renders METS XML for preview and DataCite XML.
 * Structure of the endpoint URIs totally depend on proper RealURL configuration.
 *
 * Examples:
 *
 * 1. METS from Fedora
 *   http://localhost/api/qucosa-1234/mets/
 *
 *   This always returns METS which is supplemented with additional information.
 *   The embedded MODS record is not the original MODS as it is stored in the
 *   repository datastream.
 *
 * 2. Attachment from Fedora
 *   http://localhost/api/qucosa-1234/attachment/ATT-0/
 *
 * 3. METS from Kitodo.Publication (this extension)
 *   http://localhost/api/3/preview/
 *
 * 4. DataCite from Kitodo.Publication (this extension)
 *
 * 5. ZIP file with allowed attachments for a given object
 *    http://localhost/api/3/preview/
 *
 * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @author Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @author Florian Rügamer <florian.ruegamer@slub-dresden.de>
 */
class GetFileController extends AbstractController
{

    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentRepository;

    /**
     * clientConfigurationManager
     *
     * @var \EWW\Dpf\Configuration\ClientConfigurationManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $clientConfigurationManager;

    /**
     * Here shit happens
     *
     * @return string|void
     */
    public function attachmentAction()
    {

        $piVars = GeneralUtility::_GP('tx_dpf');

        $fedoraHost = $this->clientConfigurationManager->getFedoraHost();

        if ($this->isForbidden($piVars['action'])) {
            $this->response->setStatus(403);
            return 'Forbidden';
        }

        $isRepositoryObject = !is_numeric($piVars['qid']);

        $fedoraNamespace = $this->clientConfigurationManager->getFedoraNamespace();

        switch ($piVars['action']) {
            case 'mets':
                $path = rtrim('http://' . $fedoraHost,
                        "/") . '/fedora/objects/' . $piVars['qid'] . '/methods/' . $fedoraNamespace . ':SDef/getMETSDissemination?supplement=yes';
                break;

            case 'preview':
                // Fixme: Can be removed due to the details page.
                $document = $this->documentRepository->findByUid($piVars['qid']);

                if ($document) {

                    $metsXml = $this->buildMetsXml($document);
                    $this->response->setHeader('Content-Type', 'text/xml; charset=UTF-8');
                    return $metsXml;

                }

                $this->response->setStatus(404);
                return 'No such document';

            case 'attachment':

                $qid = $piVars['qid'];

                $attachment = $piVars['attachment'];

                if (is_numeric($piVars['qid'])) {

                    // qid is local uid
                    /** @var Document $document */
                    $document = $this->documentRepository->findByUid($piVars['qid']);

                    /** @var File $file */
                    if (is_a($this->getFile(), '\TYPO3\CMS\Extbase\Persistence\ObjectStorage')) {
                        foreach ($document->getFile() as $file) {
                            if (!$file->isFileGroupDeleted()) {
                                if ($file->getDownload()) {
                                    if ($file->getDatastreamIdentifier() == $attachment) {
                                        $path = $file->getUrl();
                                        $contentType = $file->getContentType();
                                        break;
                                    }
                                }
                            }
                        }
                    }

                } else {

                    $path = rtrim('http://' . $fedoraHost,
                            "/") . '/fedora/objects/' . $qid . '/datastreams/' . $attachment . '/content';

                }

                if (empty($path)) {
                    $this->response->setStatus(404);
                    return 'No file found';
                }

                break;

            case 'dataCite':

                $qid = $piVars['qid'];
                $source = explode(':', $qid);
                if ($source[0] == $fedoraNamespace) {

                    $path = rtrim('http://' . $fedoraHost,
                            "/") . '/fedora/objects/' . $piVars['qid'] . '/methods/' . $fedoraNamespace . ':SDef/getMETSDissemination?supplement=yes';
                    $metsXml = str_replace('&', '&amp;', file_get_contents($path));
                    $dataCiteXml = \EWW\Dpf\Helper\DataCiteXml::convertFromMetsXml($metsXml);

                } elseif ($document = $this->documentRepository->findByUid($piVars['qid'])) {

                    $metsXml = str_replace('&', '&amp;', $this->buildMetsXml($document));
                    $dataCiteXml = \EWW\Dpf\Helper\DataCiteXml::convertFromMetsXml($metsXml);

                } else {

                    $this->response->setStatus(404);
                    return 'No such document';

                }
                $dom = new \DOMDocument('1.0', 'UTF-8');
                $dom->loadXML($dataCiteXml);
                $title = $dom->getElementsByTagName('title')[0];

                $this->response->setHeader('Content-Disposition',
                    'attachment; filename="' . self::sanitizeFilename($title->nodeValue) . '.DataCite.xml"');
                $this->response->setHeader('Content-Type', 'text/xml; charset=UTF-8');
                return $dataCiteXml;

                break;

            case 'zip':
                // FIXME Service locations on Fedora host are hard coded
                $metsUrl = rtrim('http://' . $fedoraHost, "/") . '/mets?pid=' . $piVars['qid'];
                $path = rtrim('http://' . $fedoraHost, "/") . '/zip?xmdpfilter=true&metsurl=' . rawurlencode($metsUrl);
                break;

            default:

                $this->response->setStatus(404);

                return 'No such action';
        }

        // stop here, if inactive Fedora objects are not allowed to be disseminated

        // allow dissemination if a request parameter 'deliverInactive' has the secret
        // TYPOScript configuration value 'deliverInactiveSecretKey'

        $restrictToActiveDocuments = true;
        $deliverInactiveSecretKey = $this->settings['deliverInactiveSecretKey'];

        if ($deliverInactiveSecretKey == $piVars['deliverInactive']) {
            $restrictToActiveDocuments = false;
        }

        if (true === $isRepositoryObject) {
            if (true === $restrictToActiveDocuments) {
                // if restriction applies, check object state before dissemination
                $objectProfileURI = rtrim('http://' . $fedoraHost,
                        "/") . '/fedora/objects/' . $piVars['qid'] . '?format=XML';
                $objectProfileXML = file_get_contents($objectProfileURI);
                if (false !== $objectProfileXML) {
                    $objectProfileDOM = new \DOMDocument('1.0', 'UTF-8');
                    if (true === $objectProfileDOM->loadXML($objectProfileXML)) {
                        $objectState = $objectProfileDOM->getElementsByTagName('objState')[0];
                        if ('I' === $objectState->nodeValue) {
                            $this->response->setStatus(403);
                            return 'Forbidden';
                        }
                        if ('D' === $objectState->nodeValue) {
                            $this->response->setStatus(404);
                            return 'Not Found';
                        }
                    }
                } else {
                    $this->response->setStatus(500);
                    return 'Internal Server Error';
                }
            }
        }

        // get remote header and set it before passtrough
        $headers = get_headers($path);

        if (false === $headers) {
            $this->response->setStatus(500);
            return 'Error while fetching headers';
        }

        $contentDispFlag = false;
        $contentTypeFlag = false;

        foreach ($headers as $value) {

            if (false !== stripos($value, 'Content-Disposition')) {
                header($value);
                $contentDispFlag = true;
                continue;
            }

            if (false !== stripos($value, 'Content-Type')) {
                header($value);
                $contentTypeFlag = true;
                continue;
            }

            if (false !== stripos($value, 'Content-Length')) {
                header($value);
                continue;
            }
        }

        if (!$contentDispFlag) {
            header('Content-Disposition: attachment');
        }

        if (!$contentTypeFlag) {
            header('Content-Type: ' . $contentType);
        }

        if ($stream = fopen($path, 'r')) {

            // close active session if any
            session_write_close();

            // stop output buffering
            ob_end_clean();

            fpassthru($stream);

            fclose($stream);

            // Hard exit PHP script to avoid sending TYPO3 framework HTTP artifacts
            exit;

        } else {
            $this->response->setStatus(500);
            return 'Error while opening stream';
        }

    }

    private static function sanitizeFilename($filename)
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
        $parserGenerator = new ParserGenerator();
        $parserGenerator->setXML($document->getXmlData());

        if (empty($document->getObjectIdentifier())) {
            $parserGenerator->setObjId($document->getUid());
        } else {
            $parserGenerator->setObjId($document->getObjectIdentifier());
        }

        $document->setXmlData($parserGenerator->getXMLData());

        $XSLTransformator = new XSLTransformator();
        return $XSLTransformator->getTransformedOutputXML($document);
    }

    private function isForbidden($action)
    {
        $allowed =
            array_key_exists('allowedActions', $this->settings)
            && is_array($this->settings['allowedActions'])
            && in_array($action, $this->settings['allowedActions']);
        return !$allowed;
    }
}

