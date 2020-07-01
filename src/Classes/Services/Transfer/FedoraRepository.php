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

$extpath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('dpf');

\Httpful\Bootstrap::init();


use \EWW\Dpf\Services\Logger\TransferLogger;
use \Httpful\Request;

class FedoraRepository implements Repository
{

    /**
     * documentTransferLogRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTransferLogRepository
     * @inject
     */
    protected $documentTransferLogRepository;

    /**
    * clientConfigurationManager
    *
    * @var \EWW\Dpf\Configuration\ClientConfigurationManager
    * @inject
    */
    protected $clientConfigurationManager;

    /**
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     * @inject
     */
    protected $objectManager;

    protected $response;

    protected $errors = array();

    const X_ON_BEHALF_OF = 'X-On-Behalf-Of';
    const QUCOSA_TYPE    = 'application/vnd.qucosa.mets+xml';


    /**
     * Saves a new document into the Fedora repository
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return string
     * @throws \Exception
     */
    public function ingest($document, $metsXml)
    {
        try {

            $response = Request::post($this->clientConfigurationManager->getSwordHost() . "/sword/" . $this->getSWORDCollection())
                ->sendsXml()
                ->body($metsXml)
                ->authenticateWith($this->clientConfigurationManager->getSwordUser(), $this->clientConfigurationManager->getSwordPassword())
                ->sendsType(FedoraRepository::QUCOSA_TYPE)
                ->addHeader(FedoraRepository::X_ON_BEHALF_OF, $this->getOwnerId())
                ->addHeader('Slug', $document->getReservedObjectIdentifier())
                ->send();

            // if transfer successful
            if (!$response->hasErrors() && $response->code == 201) {
                return $this->getRemoteDocumentId($response);
            } else {
                TransferLogger::Log('INGEST', $document->getUid(), null, $response);
                throw new \EWW\Dpf\Exceptions\IngestDocumentErrorException("Fedora error while ingest document.");
            }
        }
        catch (\Exception $exception) {
            TransferLogger::Log('INGEST', $document->getUid(), null, $exception->getMessage());

            if ($exception instanceof \Httpful\Exception\ConnectionErrorException) {
                $message = $exception->getMessage();
                throw new \EWW\Dpf\Exceptions\RepositoryConnectionErrorException($message);
            } else {
                throw $exception;
            }
        }

        return null;
    }

    /**
     * Updates an existing document in the Fedora repository
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @param string $metsXml
     * @return string
     * @throws \Exception
     */
    public function update($document, $metsXml)
    {

        $remoteId = $document->getObjectIdentifier();

        try {
            $response = Request::put($this->clientConfigurationManager->getSwordHost() . "/sword/" . $this->getSWORDCollection() . "/" . $remoteId)
                ->sendsXml()
                ->body($metsXml)
                ->authenticateWith($this->clientConfigurationManager->getSwordUser(), $this->clientConfigurationManager->getSwordPassword())
                ->sendsType(FedoraRepository::QUCOSA_TYPE)
                ->addHeader(FedoraRepository::X_ON_BEHALF_OF, $this->getOwnerId($document->getPid()))
                ->send();

            // if transfer successful
            if (!$response->hasErrors() && $response->code == 200) {
                return $this->getRemoteDocumentId($response);
            } else {
                TransferLogger::Log('UPDATE', $document->getUid(), $remoteId, $response);
                throw new \EWW\Dpf\Exceptions\UpdateDocumentErrorException("Fedora error while update document.");
            }
        } catch (\Exception $exception) {
            TransferLogger::Log('UPDATE', $document->getUid(), null, $exception->getMessage());

            if ($exception instanceof \Httpful\Exception\ConnectionErrorException) {
                $message = $exception->getMessage();
                throw new \EWW\Dpf\Exceptions\RepositoryConnectionErrorException($message);
            } else {
                throw $exception;
            }
        }

        return null;
    }

    /**
     * Gets an existing document from the Fedora repository
     *
     * @param string $remoteId
     * @return string
     * @throws \Exception
     */
    public function retrieve($remoteId)
    {

        try {
            $response = Request::get($this->clientConfigurationManager->getFedoraHost() . "/fedora/objects/" . $remoteId . "/methods/qucosa:SDef/getMETSDissemination")
                ->authenticateWith($this->clientConfigurationManager->getFedoraUser(), $this->clientConfigurationManager->getFedoraPassword())
                ->addHeader(FedoraRepository::X_ON_BEHALF_OF, $this->getOwnerId())
                ->send();

            // if transfer successful
            if (!$response->hasErrors() && $response->code == 200) {
                return $response->__toString();
            } else {
                TransferLogger::Log('RETRIEVE', null, $remoteId, $response);
                throw new \EWW\Dpf\Exceptions\RetrieveDocumentErrorException("Fedora has returned an error.");
            }
        } catch (\Exception $exception) {
            TransferLogger::Log('RETRIEVE', null, $remoteId, $exception->getMessage());

            if ($exception instanceof \Httpful\Exception\ConnectionErrorException) {
                $message = $exception->getMessage();
                throw new \EWW\Dpf\Exceptions\RepositoryConnectionErrorException($message);
            } else {
                throw $exception;
            }
        }

        return null;
    }

    /**
     * Reserves a new DocumentId (qucosa id)
     *
     * @param string $remoteId
     * @return string
     * @throws \Exception
     */
    public function getNextDocumentId()
    {
        try {
            $response = Request::get($this->clientConfigurationManager->getFedoraHost() . "/fedora/management/getNextPID?numPIDs=1&namespace=qucosa&xml=true")
                ->authenticateWith($this->clientConfigurationManager->getFedoraUser(), $this->clientConfigurationManager->getFedoraPassword())
                ->addHeader(FedoraRepository::X_ON_BEHALF_OF, $this->getOwnerId())
            //->addHeader()
                ->send();

            // if transfer successful
            if (!$response->hasErrors() && $response->code == 200) {
                return $response->__toString();
            } else {
                TransferLogger::Log('GET_NEXT_DOCUMENT_ID', null, null, $response);
                throw new \EWW\Dpf\Exceptions\NextDocumentIdErrorException("Fedora error while getting a document id.");
            }
        } catch (\Exception $exception) {
            TransferLogger::Log('GET_NEXT_DOCUMENT_ID', null, null, $exception->getMessage());

            if ($exception instanceof \Httpful\Exception\ConnectionErrorException) {
                $message = $exception->getMessage();
                throw new \EWW\Dpf\Exceptions\RepositoryConnectionErrorException($message);
            } else {
                throw $exception;
            }
        }

        return null;
    }

    /**
     * Removes an existing document from the Fedora repository
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @param $state
     * @return boolean
     * @throws \Exception
     */
    public function delete($document, $state)
    {

        $remoteId = $document->getObjectIdentifier();

        $stateParam = ($state)? "?" . $state : "";

        try {
            $response = Request::delete($this->clientConfigurationManager->getSwordHost() . "/sword/" . $this->getSWORDCollection() . "/" . $remoteId . $stateParam)
                ->authenticateWith($this->clientConfigurationManager->getSwordUser(), $this->clientConfigurationManager->getSwordPassword())
                ->addHeader(FedoraRepository::X_ON_BEHALF_OF, $this->getOwnerId())
                ->send();

            // if transfer successful
            if (!$response->hasErrors() && $response->code == 204) {
                return true;
            } else {
                TransferLogger::Log('DELETE', $document->getUid(), $remoteId, $response);
                switch ($state) {
                    case "revert":
                        throw new \EWW\Dpf\Exceptions\ActivateDocumentErrorException("Fedora error while activate document.");
                        break;
                    case "inactivate":
                        throw new \EWW\Dpf\Exceptions\InactivateDocumentErrorException("Fedora error while inactivate document.");
                        break;
                    default:
                        throw new \EWW\Dpf\Exceptions\DeleteDocumentErrorException("Fedora error while delete document.");
                        break;
                }
            }
        } catch (\Exception $exception) {
            TransferLogger::Log('DELETE', $document->getUid(), null, $exception->getMessage());

            if ($exception instanceof \Httpful\Exception\ConnectionErrorException) {
                $message = $exception->getMessage();
                throw new \EWW\Dpf\Exceptions\RepositoryConnectionErrorException($message);
            } else {
                throw $exception;
            }
        }

        return false;
    }

    /**
     * Gets the remoteDocumentId from the repository XML response.
     *
     * @param  \Httpful\Response $response
     * @return string
     */
    protected function getRemoteDocumentId($response)
    {

        // Get repository ID and write into document
        $responseDom = new \DOMDocument();
        $responseDom->loadXML($response->raw_body);
        $responseXpath = new \DOMXPath($responseDom);
        $responseXpath->registerNamespace("atom", "http://www.w3.org/2005/Atom");
        $responseNodes = $responseXpath->query("/atom:entry/atom:id");

        if ($responseNodes->length > 0) {
            $objectIdentifier = $responseNodes->item(0)->nodeValue;
            return $objectIdentifier;
        }

        return null;
    }

    protected function getOwnerId($pid = NULL)
    {
        if ($pid) {
            $this->clientConfigurationManager->setConfigurationPid($pid);
        }

        $ownerId = $this->clientConfigurationManager->getOwnerId();
        if (empty($ownerId)) {
            throw new \Exception('Owner id can not be empty or null!');
        }

        return $ownerId;
    }

    protected function getSWORDCollection()
    {
        return $this->clientConfigurationManager->getSwordCollectionNamespace();
    }
}
