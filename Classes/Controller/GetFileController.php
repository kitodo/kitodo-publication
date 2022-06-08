<?php /** @noinspection PhpUnused */
/** @noinspection PhpRedundantCatchClauseInspection */
declare(strict_types=1);

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

use DOMDocument;
use EWW\Dpf\Configuration\ClientConfigurationManager;
use EWW\Dpf\Domain\Repository\DocumentRepository;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use EWW\Dpf\Helper\DataCiteXml;
use EWW\Dpf\Helper\XSLTransformator;
use EWW\Dpf\Services\Identifier\Identifier;
use EWW\Dpf\Services\Storage\Fedora\ResourceTuple;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Error\Http\BadRequestException;
use TYPO3\CMS\Core\Error\Http\ForbiddenException;
use TYPO3\CMS\Core\Error\Http\StatusException;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\RequestInterface as ExtbaseRequestInterface;
use TYPO3\CMS\Extbase\Mvc\ResponseInterface as ExtbaseResponseInterface;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use function sprintf;
use function str_replace;

/**
 * Provides endpoint controller to access METS dissemination and Attachments and
 * other dissemination services for a configured Fedora repository.
 *
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
 * 3. DataCite from Kitodo.Publication (this extension)
 *
 * 4. ZIP file with allowed attachments for a given object
 *    http://localhost/api/3/preview/
 *
 * @author Alexander Bigga <alexander.bigga@slub-dresden.de>
 * @author Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @author Florian Rügamer <florian.ruegamer@slub-dresden.de>
 */
class GetFileController extends ActionController
{
    /*
     * FIXME Implicit knowledge that should be encapsulated closer to the core
     *
     * This class implements a lot of the concepts of how Kitodo.Publication uses
     * a Fedora repository and serves as a gateway to other web services.
     */

    /**
     * documentRepository
     *
     * @var DocumentRepository
     */
    private $documentRepository;

    /**
     * clientConfigurationManager
     *
     * @var ClientConfigurationManager
     */
    private $clientConfigurationManager;

    /**
     * Secret key from settings to get access to inactive Fedora objects
     *
     * @var string
     */
    private $secretKey;

    /**
     * URI template of the METS dissemination service.
     *
     * `$pid` gets replaced by the requested identifier.
     *
     * @var string
     */
    private $metsUriTemplate;
    /**
     * URI template of the METS dissemination service.
     *
     * `$metsUri` gets replaced by the METS URI for the requested identifier
     *
     * @var string
     */
    private $zipUriTemplate;

    /**
     * Construct the controller object.
     *
     * Dependencies are injected when called by the ObjectManager.
     *
     * @param DocumentRepository $documentRepository
     * @param ClientConfigurationManager $clientConfigurationManager
     */
    public function __construct(
        DocumentRepository         $documentRepository,
        ClientConfigurationManager $clientConfigurationManager)
    {
        parent::__construct();
        $this->documentRepository = $documentRepository;
        $this->clientConfigurationManager = $clientConfigurationManager;
    }

    /**
     * Default to `HTTP 400 Bad Request` response if an unknown action is given.
     *
     * Extbase always defaults to the first configured action in ext_localconf.php
     * when the specified action in the request does not match any of the configured ones.
     * So `index` is the first action and only here to return an error message.
     *
     * @return string `No such action`
     */
    public function indexAction(): string
    {
        $msg = "No such action";
        $this->response->setStatus(400, $msg);
        return $msg;
    }

    /**
     * Disseminate METS XML by delegating to a METS dissemination service.
     * If the given PID points to a local TYPO3 document METS is generated
     * without accessing the configured METS dissemination service.
     *
     * Example query parameters (without speaking URLs):
     * `&tx_dpf_getfile[action]=mets&tx_dpf_getfile[qid]=qucosa-1234`
     *
     * @param string $qid PID of the requested Qucosa resource
     * @param string $deliverInactive Secret key for delivering inactive documents
     * @return void Streams data to output buffer and exits
     * @throws StatusException
     */
    public function metsAction(string $qid, string $deliverInactive = ""): void
    {
        if ($this->isRemote($qid)) {
            $state = $this->getKitodoPublicationState($this->resourcePathFor($qid));
            if (!$this->canProceedWithState($state, $deliverInactive)) {
                throw new ForbiddenException(sprintf("Access to requested document %s is not permitted", $qid), 403);
            }
            $uri = $this->metsUriFor($qid);
            try {
                $response = (new Client())->get($uri);
            } catch (GuzzleException $exception) {
                throw new Exception($exception->getMessage(), $exception->getCode(), $exception);
            }
            $this->assertStatusOk($response, $uri);
            $this->copyOrSetHeader($response,
                'Content-Disposition',
                'attachment; filename="' . str_replace([':'], "-", "$qid-mets.xml") . '"');
            $this->copyOrSetHeader($response, 'Content-Type', 'application/mets+xml');
            $this->copyOrSetHeader($response, 'Content-Length');
            $this->response->setContent($response->getBody());
        } else {
            $document = $this->documentRepository->findByIdentifier($qid);
            if ($document === null) {
                $msg = "Document not found";
                $this->response->setStatus(404, $msg);
                $this->response->setContent($msg);
                return;
            }
            $state = $document->getRemoteState();
            if (!$this->canProceedWithState($state, $deliverInactive)) {
                throw new ForbiddenException(sprintf("Access to requested document %s is not permitted", $qid), 403);
            }
            $xml = (new XSLTransformator)->getTransformedOutputXML($document);
            $this->response->setHeader(
                'Content-Disposition',
                'attachment; filename="' . str_replace([':'], "-", "$qid-mets.xml") . '"');
            $this->response->setHeader('Content-Type', 'application/mets+xml');
            $this->response->setHeader('Content-Length', mb_strlen($xml));
            $this->response->setContent($xml);
        }
    }

    private function isRemote(string $pid): bool
    {
        // assuming that a Fedora PID references an remote resource and everything is local
        return Identifier::isFedoraPid($pid);
    }

    /**
     * Obtain the Kitodo.Publication state of a remote resource.
     *
     * @param string $uri Remote Kitodo.Publication resource URI
     * @return string Document workflow state
     */
    private function getKitodoPublicationState(string $uri): string
    {
        $client = $this->getAuthenticatedFedoraClient();
        $response = $client->get($uri, [
            'headers' => [
                'Accept' => 'application/ld+json'
            ]
        ]);

        $remoteState = ResourceTuple::create($response->getBody()->getContents())->getValue('kp:state');
        if ($remoteState == NULL) {
            throw new Exception("Failed to read state property from remote resource `$uri`");
        }
        return $remoteState;
    }

    /**
     * Returns a ready-to-use authenticated Fedora HTTP client
     * configured with host and REST endpoint base URI.
     *
     * @return Client Configured HTTP client.
     */
    private function getAuthenticatedFedoraClient(): Client
    {
        $cfgm = $this->clientConfigurationManager;
        $host = $cfgm->getFedoraHost();
        $endpoint = $cfgm->getFedoraEndpoint();
        // FIXME Make URI scheme configurable
        return new Client([
            'base_uri' => "http://$host/$endpoint/",
            'auth' => [$cfgm->getFedoraUser(), $cfgm->getFedoraPassword()]
        ]);
    }

    /**
     * Return Fedora path to access a resource and it's attachment.
     *
     * @param string $qid Kitodo.Publication resource ID
     * @param string|null $attachment (Optional) Attachment ID
     * @return string Relative path to access the Fedora resource
     */
    private function resourcePathFor(string $qid, string $attachment = null): string
    {
        $root = $this->clientConfigurationManager->getFedoraRootContainer();
        // FIXME Make URI scheme configurable
        $resourceUri = "$root/$qid";
        if ($attachment !== null && $attachment !== '') {
            $resourceUri .= "/$attachment";
        }
        return $resourceUri;
    }

    /**
     * Check whether the specified object is considered active in the
     * Kitodo.Publication workflow. And if not if an inactive document should be
     * delivered because of the matching secret key.
     *
     * @param string $state State of a Kitodo.Publication object
     * @param string|null $givenSecretKey Secret API key passed from the request
     * @return bool True, if the given state and secret key combination allows delivery
     */
    private function canProceedWithState(string $state, string $givenSecretKey = null): bool
    {
        return $state === DocumentWorkflow::REMOTE_STATE_ACTIVE
            || $this->canProceedWithSecretKey($givenSecretKey);
    }

    /**
     * Check whether the given key matches the systems configured secret key.
     *
     * @param string $givenSecretKey Secret API key passed from the request
     * @return bool True, if the given state and secret key combination allows delivery
     */
    private function canProceedWithSecretKey(string $givenSecretKey): bool
    {
        return ($givenSecretKey !== null && $this->secretKey === $givenSecretKey);
    }


    /**
     * Return the METS Dissemination URI for the given resource identifier.
     *
     * The URI is calculated from the configured METS URI template.
     *
     * @param string $qid
     * @return array|string|string[]
     */
    private function metsUriFor(string $qid)
    {
        return str_replace("\$pid", $qid, $this->metsUriTemplate);
    }

    /**
     * Assert that the given HTTP response has status 200 OK.
     *
     * @param ResponseInterface $response Response to assert on
     * @param string $uri Requested URI used for error message
     * @return void
     * @throws StatusException thrown if the status code is not OK
     */
    private function assertStatusOk(ResponseInterface $response, string $uri): void
    {
        if ($response->getStatusCode() !== 200) {
            throw new Exception(sprintf('Failed to stream resource %s. Response was `%s`',
                $uri,
                $response->getReasonPhrase()),
                $response->getStatusCode());
        }
    }

    /**
     * Copy header line from given response. Set default if the specified header
     * does not exist in the given response.
     *
     * @param ResponseInterface $fromResponse
     * @param string $header Header name to copy (case insensitive)
     * @param string|null $default Default value to set, if the header does not exist
     * @return void
     */
    private function copyOrSetHeader(ResponseInterface $fromResponse,
                                     string            $header,
                                     string            $default = null): void
    {
        if ($fromResponse->hasHeader($header)) {
            $this->response->setHeader($header, $fromResponse->getHeaderLine($header));
        } else if ($default !== null) {
            $this->response->setHeader($header, $default);
        }
    }

    /**
     * Disseminate Zip file
     *
     * Example query parameters (without speaking URLs):
     * `&tx_dpf_getfile[action]=zip&tx_dpf_getfile[qid]=qucosa-1234`
     *
     * @param string $qid PID of the requested Qucosa resource
     * @return StreamInterface Result stream
     * @throws Exception If method is not allowed for the specified PID
     */
    public function zipAction(string $qid): StreamInterface
    {
        if (!$this->isRemote($qid)) {
            throw new Exception("ZIP method not allowed for local documents", 405);
        }

        $state = $this->getKitodoPublicationState($this->resourcePathFor($qid));
        if (!$this->canProceedWithState($state)) {
            throw new ForbiddenException(sprintf("Access to requested document %s is not permitted", $qid), 403);
        }

        $metsUri = $this->metsUriFor($qid);
        $zipUri = str_replace('$metsUri', rawurlencode($metsUri), $this->zipUriTemplate);
        try {
            $response = (new Client())->get($zipUri);
        } catch (GuzzleException $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode(), $exception);
        }

        $this->assertStatusOk($response, $zipUri);
        $this->copyOrSetHeader($response,
            'Content-Disposition',
            'attachment; filename="' . str_replace([':'], "-", "$qid.zip") . '"');
        $this->copyOrSetHeader($response, 'Content-Type', 'application/zip');
        $this->copyOrSetHeader($response, 'Content-Length');

        // FIXME This calls StreamInterface::__toString(). Is it buffering?
        return $response->getBody();
    }

    /**
     * Disseminate DataCite XML
     *
     * Example query parameters (without speaking URLs):
     * `&tx_dpf_getfile[action]=dataCite&tx_dpf_getfile[qid]=qucosa-1234`
     *
     * @param string $qid
     * @throws Exception If method is not allowed for the specified PID
     */
    public function dataCiteAction(string $qid): void
    {
        if ($this->isRemote($qid)) {
            $state = $this->getKitodoPublicationState($this->resourcePathFor($qid));
            if (!$this->canProceedWithState($state)) {
                throw new ForbiddenException(sprintf("Access to requested document %s is not permitted", $qid), 403);
            }
            $uri = $this->metsUriFor($qid);
            try {
                $response = (new Client())->get($uri);
            } catch (GuzzleException $exception) {
                throw new Exception($exception->getMessage(), $exception->getCode());
            }
            $this->assertStatusOk($response, $uri);
            $metsXml = $response->getBody()->getContents();
        } else {
            $document = $this->documentRepository->findByIdentifier($qid);
            if ($document === null) {
                $msg = "Document not found";
                $this->response->setStatus(404, $msg);
                $this->response->setContent($msg);
                return;
            }
            $state = $document->getRemoteState();
            if (!$this->canProceedWithState($state)) {
                throw new ForbiddenException(sprintf("Access to requested document %s is not permitted", $qid), 403);
            }
            $metsXml = (new XSLTransformator)->getTransformedOutputXML($document);
        }

        $dataCiteXml = DataCiteXml::convertFromMetsXml($metsXml);
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($dataCiteXml);
        $title = $dom->getElementsByTagName('title')[0];

        $this->response->setHeader('Content-Disposition',
            'attachment; filename="' . self::sanitizeFilename($title->nodeValue) . '.DataCite.xml"');
        $this->response->setHeader('Content-Type', 'text/xml; charset=UTF-8');
        $this->response->setContent($dataCiteXml);
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

    /**
     * Disseminate attachment for local or remote objects.
     *
     * If the requested object is a remote it needs to be in an active state to be delivered. To deliver
     * attachments of inactive objects the secret delivery key has to be passed. If the requested object
     * is local the secret key is mandatory. Local file identifiers can have TYPO3 temp names like
     * `1649348148624f0e3488a737.68511031` instead of the usual remote attachment names like `ATT-*`.
     *
     * Example query parameters (without speaking URLs):
     * `&tx_dpf_getfile[action]=attachment
     *          &tx_dpf_getfile[qid]=qucosa-1234
     *          &tx_dpf_getfile[attachment]=ATT-0
     *          &tx_dpf_getfile[deliverInactive]=<configured secret key>`
     *
     * @param string $qid
     * @param string $attachment
     * @param string $deliverInactive
     * @throws Exception If method is not allowed for the specified PID
     */
    public function attachmentAction(string $qid, string $attachment, string $deliverInactive = ""): void
    {
        if ($this->isRemote($qid)) {
            $state = $this->getKitodoPublicationState($this->resourcePathFor($qid));
            if (!$this->canProceedWithState($state, $deliverInactive)) {
                throw new ForbiddenException(sprintf("Access to requested document %s is not permitted", $qid), 403);
            }
            $resourceUri = $this->resourcePathFor($qid, $attachment);
            $client = $this->getAuthenticatedFedoraClient();
            try {
                $response = ($client)->get($resourceUri);
            } catch (GuzzleException $exception) {
                throw new Exception($exception->getMessage(), $exception->getCode(), $exception);
            }
            $this->assertStatusOk($response, $resourceUri);
            $this->copyOrSetHeader($response,
                'Content-Disposition',
                'attachment; filename="' . str_replace([':'], "-", "$qid-$attachment.bin") . '"');
            $this->copyOrSetHeader($response, 'Content-Type');
            $this->copyOrSetHeader($response, 'Content-Length');
            // FIXME This calls StreamInterface::__toString(). Is it buffering?
            $this->response->setContent($response->getBody());
        } else {
            if (!$this->canProceedWithSecretKey($deliverInactive)) {
                throw new ForbiddenException("Access is not permitted", 403);
            }
            $document = $this->documentRepository->findByIdentifier($qid);
            if ($document === null) {
                $msg = "Document not found";
                $this->response->setStatus(404, $msg);
                $this->response->setContent($msg);
                return;
            }
            $file = $document->getFileByFileIdentifier($attachment);
            if ($file === null) {
                throw new Exception(sprintf("Attachment %s/%s not found", $qid, $attachment), 404);
            }
            if (!$file->getDownload()) {
                throw new ForbiddenException(sprintf("Access to requested attachment %s/%s is not permitted", $qid, $attachment), 403);
            }
            $this->response->setHeader('Content-Type', $file->getContentType());
            $resource = fopen($file->getFilePath(), 'rb');
            $stream = Utils::streamFor($resource);
            // FIXME This calls StreamInterface::__toString(). Is it buffering?
            $this->response->setContent($stream);
        }
    }

    /**
     * Wrap actual request processing to catch Exceptions and translate them into a proper HTTP error response.
     *
     * @param ExtbaseRequestInterface $request
     * @param ExtbaseResponseInterface $response
     * @return void
     */
    public function processRequest(ExtbaseRequestInterface $request, ExtbaseResponseInterface $response): void
    {
        try {
            parent::processRequest($request, $response);
        } catch (Exception $exception) {
            // wild ass guess that the error code might be an HTTP status code
            if ($exception->getCode() > 300 && $exception->getCode() < 600) {
                $response->setStatus($exception->getCode());
            } else {
                $response->setStatus(500);
            }
            $response->setContent($exception->getMessage());
        }
    }

    /**
     * Check if the dispatched action is allowed in the current TypoScript setup.
     *
     * @throws ForbiddenException if dispatched action is not currently allowed
     * @throws BadRequestException if dispatched action is unknown
     */
    protected function initializeAction(): void
    {
        parent::initializeAction();

        $action = $this->request->getControllerActionName();

        if ($action !== 'index' && $this->isForbidden($action)) {
            $this->response->setStatus(403);
            throw new ForbiddenException();
        }

        if (!empty($this->settings['api'])) {
            $this->secretKey = $this->settings['api']['deliverInactiveSecretKey'];
            $this->metsUriTemplate = $this->settings['api']['metsDisseminationUri'];
            $this->zipUriTemplate = $this->settings['api']['zipDisseminationUri'];
        }
    }

    private function isForbidden(string $action): bool
    {
        $allowed =
            array_key_exists('allowedActions', $this->settings)
            && is_array($this->settings['allowedActions'])
            && in_array($action, $this->settings['allowedActions'], true);
        return !$allowed;
    }

    protected function initializeView(ViewInterface $view)
    {
        // remove default view
        $this->view = null;
    }
}

