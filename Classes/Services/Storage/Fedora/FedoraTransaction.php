<?php

namespace EWW\Dpf\Services\Storage\Fedora;

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
 *
 */

use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use EWW\Dpf\Services\Storage\Fedora\Exception\FedoraException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException as GuzzleConnectException;
use GuzzleHttp\Exception\GuzzleException;
use PhpParser\Node\Expr\Cast\String_;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FedoraTransaction
{
    /**
     * clientConfigurationManager
     *
     * @var \EWW\Dpf\Configuration\ClientConfigurationManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $clientConfigurationManager;

    /**
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $objectManager;

    /**
     * logger
     *
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    protected $logger = null;

    public function __construct()
    {
        /** @var $logger \TYPO3\CMS\Core\Log\Logger */
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

    /**
     * Create a container resource
     *
     * @param string $transactionUri
     * @param string $containerIdentifier
     * @param string $state
     * @param string $state
     * @throws FedoraException
     */
    public function createContainer(
        string $transactionUri, string $containerIdentifier, string $state = DocumentWorkflow::REMOTE_STATE_ACTIVE
    )
    {
        $requestUri = $this->baseUri() . $containerIdentifier;

        $errorMessage = 'Error while creating resource container "' . $requestUri . '". ';

        try {
            if ($this->isResourceExist($containerIdentifier)) {
                $this->logger->warning(
                    $errorMessage . 'Resource already exist. Create container aborted.'
                );

                throw FedoraException::create(
                    'Resource container could not be created. Resource already exists.',
                    FedoraException::ALREADY_EXISTS, $requestUri
                );
            }

            $client = new Client(['base_uri' => $this->baseUri()]);

            $response = $client->request('POST', '',
                [
                    'auth' => [
                        $this->clientConfigurationManager->getFedoraUser(),
                        $this->clientConfigurationManager->getFedoraPassword()
                    ],
                    'headers' => [
                        'Atomic-ID' => $transactionUri,
                        'Content-Type' => 'text/turtle',
                        'Slug' => $containerIdentifier,
                        'Link' => '<http://fedora.info/definitions/v4/repository#ArchivalGroup>;rel="type"',
                    ],
                    'body' => 'PREFIX kp: <https://www.kitodo.org/kitodo-publication/>  <> kp:state "' . $state . '"'
                ]
            );

            if ($response->getStatusCode() !== 201) {
                $this->logger->warning(
                    $errorMessage . 'Unexpected http response: ' . $response->getStatusCode()
                    . '. Create container aborted.'
                );

                throw FedoraException::create('Create resource container failed. Unexpected http response',
                    FedoraException::UNEXPECTED_RESPONSE, $requestUri, $response->getStatusCode()
                );
            }

        } catch (GuzzleConnectException $guzzleConnectException) {
            $this->logger->warning($errorMessage . 'No connection to fedora. Create container aborted.');

            throw FedoraException::create('Create resource container failed. No connection to fedora.',
                FedoraException::NO_CONNECTION, $requestUri, $guzzleConnectException->getStatusCode()
            );

        } catch (GuzzleException $guzzleException) {

            if ($guzzleException->getCode() === 401) {
                $this->logger->warning(
                    $errorMessage . 'Not authorized. Create container aborted.'
                );
            } else {
                $this->logger->warning(
                    $errorMessage . 'Http status: ' . $guzzleException->getCode() . '. Create container aborted.'
                );
            }

            throw FedoraException::create('Create resource container failed.',
                ($guzzleException->getCode() === 401 ? FedoraException::NOT_AUTHORIZED : 0),
                $requestUri, $guzzleException->getCode()
            );
        }
    }

    /**
     * Create a binary resource
     *
     * @param string $transactionUri
     * @param string $containerIdentifier
     * @param string $binaryIdentifier
     * @param string $contentType
     * @param string $fileName
     * @param string $fileSrc File URI or path to a local file
     * @param string $state
     * @throws FedoraException
     */
    public function createBinary(
        string $transactionUri, string $containerIdentifier, string $binaryIdentifier, string $contentType,
        string $fileName, string $fileSrc, string $state = DocumentWorkflow::REMOTE_STATE_ACTIVE
    )
    {
        $requestUri = $this->baseUri() . $containerIdentifier . '/' . $binaryIdentifier;

        $errorMessage = 'Error while creating binary resource "' . $requestUri . '". ';

        try {
            if ($this->isResourceExist($containerIdentifier, $binaryIdentifier)) {
                $this->logger->warning($errorMessage . 'Resource already exist. Create binary aborted.');

                throw FedoraException::create('Create binary resource failed.',
                    FedoraException::ALREADY_EXISTS, $requestUri
                );
            }

            $client = new Client(['base_uri' => $this->baseUri()]);

            if (strpos(strtolower($fileSrc), 'http') === 0) {

                $link = '<' . $fileSrc . '>; rel="http://fedora.info/definitions/fcrepo#ExternalContent";' .
                    ' handling="copy"; type="' . $contentType .'"';

                $response = $client->request('POST', $containerIdentifier,
                    [
                        'auth' => [
                            $this->clientConfigurationManager->getFedoraUser(),
                            $this->clientConfigurationManager->getFedoraPassword()
                        ],
                        'headers' => [
                            'Atomic-ID' => $transactionUri,
                            'Link' => $link,
                            'Slug' => $binaryIdentifier,
                        ]
                    ]
                );
            } else {
                if (0 == filesize($fileSrc)) {
                    $this->logger->warning(
                        $errorMessage . 'Invalid file: ' . $fileSrc . '. Create binary aborted.'
                    );

                    throw FedoraException::create(
                        'Create binary resource failed. Unexpected http response. '
                        . 'The given file "' . $fileSrc . '" is empty or does not exist.',
                        FedoraException::INVALID_FILE, $requestUri
                    );
                }

                $body = fopen($fileSrc, 'r');

                $response = $client->request('POST', $containerIdentifier,
                    [
                        'auth' => [
                            $this->clientConfigurationManager->getFedoraUser(),
                            $this->clientConfigurationManager->getFedoraPassword()
                        ],
                        'headers' => [
                            'Atomic-ID' => $transactionUri,
                            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                            'Content-Type' => $contentType,
                            'Slug' => $binaryIdentifier,
                        ],
                        'body' => $body
                    ]
                );
            }

            if ($response->getStatusCode() !== 201) {
                $this->logger->warning(
                    $errorMessage . 'Unexpected http response: ' . $response->getStatusCode()
                    . '. Create binary aborted.'
                );

                throw FedoraException::create('Create binary resource failed. Unexpected http response.',
                    FedoraException::UNEXPECTED_RESPONSE, $requestUri, $response->getStatusCode()
                );
            }

            if ($state) {
                $resourceTuple = $this->getResourceTuple($transactionUri, $containerIdentifier, $binaryIdentifier);
                $resourceTuple->setValue('kp:state', $state);
                $this->updateResourceTuple($transactionUri, $resourceTuple, $containerIdentifier, $binaryIdentifier);
            }

        } catch (GuzzleConnectException $guzzleConnectException) {
            $this->logger->warning($errorMessage . 'No connection to fedora. Create binary aborted.');

            throw FedoraException::create('Create binary resource failed. No Connection to fedora.',
                FedoraException::NO_CONNECTION, $requestUri, $guzzleConnectException->getCode()
            );

        } catch (GuzzleException $guzzleException) {

            if ($guzzleException->getCode() === 401) {
                $this->logger->warning(
                    $errorMessage . 'Not authorized. Create binary aborted.'
                );
            } else {
                $this->logger->warning(
                    $errorMessage . 'Http status: ' . $guzzleException->getCode() . '. Create binary aborted.'
                );
            }

            throw FedoraException::create('Create binary resource failed.',
                ($guzzleException->getCode() === 401 ? FedoraException::NOT_AUTHORIZED : 0),
                $requestUri, $guzzleException->getCode()
            );
        }
    }

    /**
     * Updates the content (file) of a binary resource.
     *
     * @param string $transactionUri
     * @param string $containerIdentifier
     * @param string $binaryIdentifier
     * @param string $contentType
     * @param string $fileName
     * @param string $fileSrc File URI or path to a local file
     * @param string|null $state
     * @throws FedoraException
     */
    function updateContent(
        string $transactionUri, string $containerIdentifier, string $binaryIdentifier, string $contentType,
        string $fileName, string $fileSrc, string $state = null
    )
    {
        $resourceUri = $this->baseUri() . $containerIdentifier . '/' . $binaryIdentifier;

        $errorMessage = 'Error while updating resource content "' . $resourceUri .'". ';

        try {
            if (!$this->isResourceExist($containerIdentifier, $binaryIdentifier)) {
                $this->logger->warning($errorMessage . 'Resource does not exist. Update content aborted.');

                throw FedoraException::create('Update binary resource failed. Resource does not exist.',
                    FedoraException::NOTHING_FOUND, $resourceUri
                );
            }

            $client = new Client(['base_uri' => $this->baseUri()]);

            if (strpos(strtolower($fileSrc), 'http') === 0) {

                $link = '<' . $fileSrc . '>; rel="http://fedora.info/definitions/fcrepo#ExternalContent";' .
                    ' handling="copy"; type="' . $contentType .'"';

                $response = $client->request('PUT', $containerIdentifier,
                    [
                        'auth' => [
                            $this->clientConfigurationManager->getFedoraUser(),
                            $this->clientConfigurationManager->getFedoraPassword()
                        ],
                        'headers' => [
                            'Atomic-ID' => $transactionUri,
                            'Link' => $link,
                        ]
                    ]
                );
            } else {
                if (0 == filesize($fileSrc)) {
                    $this->logger->warning(
                        $errorMessage . 'Invalid file: ' . $fileSrc . '. Update content aborted.'
                    );

                    throw FedoraException::create(
                        'Update binary resource content failed. '
                        . 'The given file "' . $fileSrc . '" is empty or does not exist.',
                        FedoraException::INVALID_FILE, $resourceUri
                    );
                }

                $body = fopen($fileSrc, 'r');

                $response = $client->request('PUT', $containerIdentifier . '/' . $binaryIdentifier,
                    [
                        'auth' => [
                            $this->clientConfigurationManager->getFedoraUser(),
                            $this->clientConfigurationManager->getFedoraPassword()
                        ],
                        'headers' => [
                            'Atomic-ID' => $transactionUri,
                            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                            'Content-Type' => $contentType,
                        ],
                        'body' => $body
                    ]
                );
            }

            if ($response->getStatusCode() !== 204) {
                $this->logger->warning(
                    $errorMessage . 'Unexpected http response: ' . $response->getStatusCode()
                    . '. Update content aborted.'
                );

                throw FedoraException::create('Update binary resource failed. Unexpected http response.',
                    FedoraException::UNEXPECTED_RESPONSE, $resourceUri, $response->getStatusCode()
                );
            }

            if ($state) {
                $resourceTuple = $this->getResourceTuple($transactionUri, $containerIdentifier, $binaryIdentifier);
                $resourceTuple->setValue('kp:state', $state);
                $this->updateResourceTuple($transactionUri, $resourceTuple, $containerIdentifier, $binaryIdentifier);
            }

        } catch (GuzzleConnectException $guzzleConnectException) {
            $this->logger->warning($errorMessage . 'No connection to fedora. Update content aborted.');

            throw FedoraException::create('Update binary resource failed. No connection to fedora.',
                FedoraException::NO_CONNECTION, $resourceUri, $guzzleConnectException->getStatusCode()
            );

        } catch (GuzzleException $guzzleException) {
            if ($guzzleException->getCode() === 401) {
                $this->logger->warning(
                    $errorMessage . 'Not authorized. Update content aborted.'
                );
            } else {
                $this->logger->warning(
                    $errorMessage . 'Http status ' . $guzzleException->getCode() . '. Update content aborted.'
                );
            }

            throw FedoraException::create('Update binary resource failed.',
                ($guzzleException->getCode() === 401 ? FedoraException::NOT_AUTHORIZED : 0),
                $resourceUri, $guzzleException->getStatusCode()
            );
        }
    }

    /**
     * @param string $transactionUri
     * @param string $containerIdentifier
     * @param string $binaryIdentifier
     * @return string
     * @throws FedoraException
     */
    function getContent(?string $transactionUri, string $containerIdentifier, string $binaryIdentifier) : ?string
    {
        $path = $containerIdentifier . '/' . $binaryIdentifier;
        $requestUri = $this->baseUri() . $path;

        $errorMessage = 'Error while reading resource content "'.  $requestUri . '". ';

        try {
            $client = new Client(['base_uri' => $requestUri]);

            $response = $client->request('GET', '',
                [
                    'auth' => [
                        $this->clientConfigurationManager->getFedoraUser(),
                        $this->clientConfigurationManager->getFedoraPassword()
                    ],
                    'header' => [
                        'Atomic-ID' => $transactionUri
                    ]
                ]
            );

            if ($response->getStatusCode() !== 200) {
                $this->logger->warning(
                    $errorMessage . 'Unexpected http response: ' . $response->getStatusCode()
                    . '. Read content aborted.'
                );

                throw FedoraException::create('Read resource content failed. Unexpected http response.',
                    FedoraException::UNEXPECTED_RESPONSE, $requestUri
                );
            }

            return $response->getBody()->getContents();

        } catch (GuzzleConnectException $guzzleConnectException) {
            $this->logger->warning($errorMessage . 'No connection to fedora. Read content aborted.');

            throw FedoraException::create('Read resource content failed. No connection to fedora.',
                FedoraException::NO_CONNECTION, $requestUri, $guzzleConnectException->getCode()
            );

        } catch (GuzzleException $guzzleException) {
            if ($guzzleException->getCode() === 401) {
                $this->logger->warning(
                    $errorMessage . 'Not authorized.  Read content aborted.'
                );
            } else {
                $this->logger->warning(
                    $errorMessage . 'Http status: ' . $guzzleException->getCode() . '. Read content aborted.'
                );
            }

            throw FedoraException::create('Read resource content failed.',
                ($guzzleException->getCode() === 401 ? FedoraException::NOT_AUTHORIZED : 0),
                $requestUri, $guzzleException->getCode()
            );
        }
    }

    /**
     * @param string|null $transactionUri
     * @param string $containerIdentifier
     * @param string|null $binaryIdentifier
     * @return ResourceTuple
     * @throws FedoraException
     */
    function getResourceTuple(
        ?string $transactionUri, string $containerIdentifier, string $binaryIdentifier = null
    ) : ResourceTuple
    {
        $path = $containerIdentifier . ($binaryIdentifier? '/' . $binaryIdentifier . '/fcr:metadata' : '');
        $requestUri = $this->baseUri() . $path;

        $errorMessage = 'Error while reading resource tuple "' . $requestUri. '". ';

        try {
            $client = new Client(['base_uri' => $requestUri]);

            $response = $client->request('GET', '',
                [
                    'auth' => [
                        $this->clientConfigurationManager->getFedoraUser(),
                        $this->clientConfigurationManager->getFedoraPassword()
                    ],
                    'headers' => [
                        'Atomic-ID' => $transactionUri,
                        'Accept' => 'application/ld+json',
                    ]
                ]
            );

            if ($response->getStatusCode() !== 200) {
                $this->logger->warning(
                    $errorMessage . 'Unexpected http response: ' . $response->getStatusCode()
                    . '. Read tuple aborted.'
                );

                throw FedoraException::create('Read resource tuple failed. Unexpected http response.',
                    FedoraException::UNEXPECTED_RESPONSE, $requestUri
                );
            }

            return ResourceTuple::create($response->getBody()->getContents());

        } catch (GuzzleConnectException $guzzleConnectException) {
            $this->logger->warning(
                $errorMessage . 'No connection to fedora. Read tuple aborted.'
            );

            throw FedoraException::create('Read resource tuple failed. No connection to fedora.',
                FedoraException::NO_CONNECTION, $requestUri, $guzzleConnectException->getCode()
            );

        } catch (GuzzleException $guzzleException) {
            if ($guzzleException->getCode() === 401) {
                $this->logger->warning(
                    $errorMessage . 'Not authorized. Read tuple aborted.'
                );
            } else {
                $this->logger->warning(
                    $errorMessage . 'Http status: ' . $guzzleException->getCode() . '. Read tuple aborted.'
                );
            }

            throw FedoraException::create('Read resource tuple failed.',
                ($guzzleException->getCode() === 404 ? FedoraException::NOTHING_FOUND : 0),
                $requestUri, $guzzleException->getCode()
            );
        }
    }

    /**
     * @param string $transactionUri
     * @param ResourceTuple $resourceTuple
     * @param string $containerIdentifier
     * @param string|null $binaryIdentifier
     * @throws FedoraException
     */
    function updateResourceTuple(
        string $transactionUri, ResourceTuple $resourceTuple, string $containerIdentifier,
        string $binaryIdentifier = null
    )
    {
        $path = $containerIdentifier . ($binaryIdentifier? '/' . $binaryIdentifier . '/fcr:metadata' : '');
        $requestUri = $this->baseUri() . $path;

        $errorMessage = 'Error while updating resource tuple "'. $requestUri .'". ';

        try {
            $client = new Client(['base_uri' => $this->baseUri()]);

            $changedValues = $resourceTuple->getModifiedValues();

            // To avoid multiple entries of the same field we need to delete all existing entries of each changed field.
            foreach ($changedValues as $key => $value) {
                 $response = $client->request('PATCH', $path,
                    [
                        'auth' => [
                            $this->clientConfigurationManager->getFedoraUser(),
                            $this->clientConfigurationManager->getFedoraPassword()
                        ],
                        'headers' => [
                            'Atomic-ID' => $transactionUri,
                            'Content-Typ' => 'application/sparql-update'
                        ],
                        'body' => 'DELETE WHERE { <> <' . $value['uri'] . '> ?v}'
                    ]
                );

                if ($response->getStatusCode() !== 204) {
                    $this->logger->warning(
                        $errorMessage . 'Unexpected http response: ' . $response->getStatusCode()
                        . '. Update aborted.'
                    );

                    throw FedoraException::create('Update resource tuple failed. Unexpected http response.',
                        FedoraException::UNEXPECTED_RESPONSE, $requestUri, $response->getStatusCode()
                    );
                }

            }

            $insert = [];
            foreach ($changedValues as $key => $value) {
                $insert[] = '<> <' . $value['uri'] . '> "' . $value['value'] . '"';
            }

            $insertResponse = $client->request('PATCH', $path,
                [
                    'auth' => [
                        $this->clientConfigurationManager->getFedoraUser(),
                        $this->clientConfigurationManager->getFedoraPassword()
                    ],
                    'headers' => [
                        'Atomic-ID' => $transactionUri,
                        'Content-Typ' => 'application/sparql-update'
                    ],
                    'body' => 'INSERT {' .  implode(' . ', $insert) .'} WHERE {}'
                ]
            );

            if ($insertResponse->getStatusCode() !== 204) {
                $this->logger->warning(
                    $errorMessage . 'Unexpected http response: ' . $insertResponse->getStatusCode()
                    . '. Update aborted.'
                );

                throw FedoraException::create('Update resource tuple failed. Unexpected http response.',
                    FedoraException::UNEXPECTED_RESPONSE, $requestUri, $insertResponse->getStatusCode()
                );
            }

        } catch (GuzzleConnectException $guzzleConnectException) {
            $this->logger->warning(
                $errorMessage . 'No connection to fedora. Update aborted.'
            );

            throw FedoraException::create('Update resource tuple failed. No connection to fedora.',
                FedoraException::UNEXPECTED_RESPONSE, $requestUri, $guzzleConnectException->getCode()
            );

        } catch (GuzzleException $guzzleException) {
            if ($guzzleException->getCode() === 401) {
                $this->logger->warning(
                    $errorMessage . 'Not authorized. Update aborted.'
                );
            } else {
                $this->logger->warning(
                    $errorMessage . 'Http status: ' . $guzzleException->getCode() . '. Update aborted.'
                );
            }

            throw FedoraException::create('Update resource tuple failed.',
                ($guzzleException->getCode() === 401 ? FedoraException::NOT_AUTHORIZED : 0),
                $requestUri, $guzzleException->getCode()
            );
        }
    }

    /**
     * @param string $containerIdentifier
     * @param string|null $binaryIdentifier
     * @return bool
     * @throws GuzzleException
     */
    protected function isResourceExist(string $containerIdentifier, string $binaryIdentifier = null) : bool
    {
        $path = $containerIdentifier . ($binaryIdentifier? '/' . $binaryIdentifier : '');

        try {
            // Check for existence
            $client = new Client(['base_uri' => $this->baseUri()]);

            $response = $client->request('HEAD', $path,
                [
                    'auth' => [
                        $this->clientConfigurationManager->getFedoraUser(),
                        $this->clientConfigurationManager->getFedoraPassword()
                    ]
                ]
            );

            return $response->getStatusCode() === 200;

        } catch (GuzzleException $guzzleException) {
            if ($guzzleException->getCode() === 404) {
                return false;
            }

            throw $guzzleException;
        }
    }

    /**
     * Starts a transaction
     *
     * @return string|null
     * @throws FedoraException
     */
    function start() : ?string
    {
        $errorMessage = 'Error while creating a transaction. ';

        try {
            $client = new Client(['base_uri' => $this->transactionUri()]);

            $response = $client->request('POST', '',
                [
                    'auth' => [
                        $this->clientConfigurationManager->getFedoraUser(),
                        $this->clientConfigurationManager->getFedoraPassword()
                    ]
                ]
            );

            if ($response->getStatusCode() === 201) {
                $transaction = $response->getHeader('Location');
                return $transaction[0];
            }

            $this->logger->warning(
                $errorMessage . 'Unexpected http response: ' . $response->getStatusCode() . '. Start transaction aborted.'
            );

            throw FedoraException::create('Start transaction failed. Unexpected http response.',
                FedoraException::NO_TRANSACTION, null, $response->getStatusCode()
            );


        } catch (GuzzleConnectException $guzzleConnectException) {
            $this->logger->warning(
                $errorMessage . 'No connection to fedora. Start transaction aborted.'
            );

            throw FedoraException::create('Start transaction failed. No connection to fedora.',
                FedoraException::NO_CONNECTION, null, $guzzleConnectException->getCode()
            );

        } catch (GuzzleException $guzzleException) {
            if ($guzzleException->getCode() === 401) {
                $this->logger->warning(
                    $errorMessage . 'Not authorized. Start transaction aborted.'
                );
            } else {
                $this->logger->warning(
                    $errorMessage . 'Http status: ' . $guzzleException->getCode() . '. Start transaction aborted.'
                );
            }

            throw FedoraException::create('Start transaction failed.',
                ($guzzleException->getCode() === 401 ? FedoraException::NOT_AUTHORIZED : FedoraException::NO_TRANSACTION),
                null, $guzzleException->getCode()
            );

        }
    }

    /**
     * @param string $transactionUri
     * @throws FedoraException
     */
    function commit(string $transactionUri)
    {
        $errorMessage = 'Error while committing the transaction "' . $transactionUri .'". ';

        try {
            $client = new Client(['base_uri' => $transactionUri]);

            $response = $client->request('PUT', '',
                [
                    'auth' => [
                        $this->clientConfigurationManager->getFedoraUser(),
                        $this->clientConfigurationManager->getFedoraPassword()
                    ]
                ]
            );

            if ($response->getStatusCode() !== 204) {
                $this->logger->warning(
                    $errorMessage . 'Unexpected hht response: ' . $response->getStatusCode()
                    . '. Commit aborted.'
                );

                throw FedoraException::create('Commit transaction failed. Unexpected http response.',
                    FedoraException::NO_TRANSACTION, null, $response->getStatusCode()
                );
            }

        } catch (GuzzleConnectException $guzzleConnectException) {
            $this->logger->warning(
                $errorMessage . 'No connection to fedora. Commit aborted.'
            );

            throw FedoraException::create('Commit transaction failed. No connection to fedora.',
                FedoraException::NO_CONNECTION, null, $guzzleConnectException->getCode()
            );

        } catch (GuzzleException $guzzleException) {
            if ($guzzleException->getCode() === 401) {
                $this->logger->warning(
                    $errorMessage . 'Not authorized. Commit aborted.'
                );
            } else {
                $this->logger->warning(
                    $errorMessage . 'Http status: ' . $guzzleException->getCode() . '. Commit aborted.'
                );
            }

            throw FedoraException::create('Commit transaction failed.',
                ($guzzleException->getCode() === 401 ? FedoraException::NOT_AUTHORIZED : FedoraException::NO_TRANSACTION),
                null, $guzzleException->getCode()
            );
        }
    }

    /**
     * Rollbacks a transaction
     *
     * @param string $transactionUri
     * @throws FedoraException
     */
    function rollback(string $transactionUri)
    {
        $errorMessage = 'Error while rolling back the transaction "' . $transactionUri .'". ';

        try {
            $client = new Client(['base_uri' => $transactionUri]);

            $response = $client->request('DELETE', '',
                [
                    'auth' => [
                        $this->clientConfigurationManager->getFedoraUser(),
                        $this->clientConfigurationManager->getFedoraPassword()
                    ]
                ]
            );

            if ($response->getStatusCode() !== 204) {
                $this->logger->warning(
                    $errorMessage . ' Unexpected http response: ' . $response->getStatusCode()
                    . ' Rollback aborted.'
                );

                throw FedoraException::create('Rollback transaction failed. Unexpected http response.',
                    FedoraException::NO_TRANSACTION, null, $response->getStatusCode()
                );
            }

        } catch (GuzzleConnectException $guzzleConnectException) {
            $this->logger->warning(
                $errorMessage . ' No connection to fedora.' . ' Rollback aborted.'
            );

            throw FedoraException::create('Rollback transaction failed. No connection to fedora.',
                FedoraException::NO_CONNECTION, null, $guzzleConnectException->getCode()
            );

        } catch (GuzzleException $guzzleException) {
            if ($guzzleException->getCode() === 401) {
                $this->logger->warning(
                    $errorMessage . 'Not authorized. Rollback aborted.'
                );
            } else {
                $this->logger->warning(
                    $errorMessage . 'Http status: ' . $guzzleException->getCode() . '. Rollback aborted.'
                );
            }

            throw FedoraException::create('Rollback transaction failed.',
                ($guzzleException->getCode() === 401 ? FedoraException::NOT_AUTHORIZED : FedoraException::NO_TRANSACTION),
                null, $guzzleException->getCode()
            );

        }
    }


    /**
     * Return the URI of the Fedora endpoint where transactions can
     * be startet, stopped and committed.
     *
     * @return URI of the Fedora transactions endpoint
     */
    public function transactionUri():string
    {
        $uri  = $this->clientConfigurationManager->getFedoraHost();
        $uri .= $this->clientConfigurationManager->getFedoraEndpoint() ? '/' . $this->clientConfigurationManager->getFedoraEndpoint() : '';
        $uri .= "/fcr:tx";
        return $uri;
    }

    /**
     * @return string
     */
    public function baseUri()
    {
        $uri  = $this->clientConfigurationManager->getFedoraHost();
        $uri .= $this->clientConfigurationManager->getFedoraEndpoint() ? '/' . $this->clientConfigurationManager->getFedoraEndpoint() : '';
        $uri .= $this->clientConfigurationManager->getFedoraRootContainer() ? '/' . $this->clientConfigurationManager->getFedoraRootContainer() : '';
        $uri .= '/';
        return $uri;
    }
}
