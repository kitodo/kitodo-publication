<?php
namespace EWW\Dpf\Services\Email;

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

use EWW\Dpf\Configuration\ClientConfigurationManager;
use EWW\Dpf\Domain\Model\Client;
use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Domain\Model\FrontendUser;
use EWW\Dpf\Domain\Model\Message;
use EWW\Dpf\Domain\Repository\ClientRepository;
use EWW\Dpf\Domain\Repository\DepositLicenseRepository;
use EWW\Dpf\Domain\Repository\DocumentTypeRepository;
use EWW\Dpf\Domain\Repository\MessageRepository;
use EWW\Dpf\Security\Security;
use EWW\Dpf\Services\Api\InternalFormat;
use EWW\Dpf\Services\Logger\LoggerOld;
use Httpful\Exception\ConnectionErrorException;
use Httpful\Request;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

class Notifier
{
    const ACTIVE_MESSAGE_ERROR_TYPE_HTTP = 'http';
    const ACTIVE_MESSAGE_ERROR_TYPE_CURL = 'curl';
    const ACTIVE_MESSAGE_ERROR_TYPE_UNKNOWN = 'unknown';

    /**
     * clientRepository
     *
     * @var \EWW\Dpf\Domain\Repository\ClientRepository
     */
    protected $clientRepository = null;

    /**
     * documentTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     */
    protected $documentTypeRepository = null;

    /**
     * depositLicenseRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DepositLicenseRepository
     */
    protected $depositLicenseRepository = null;

    /**
     * security
     *
     * @var \EWW\Dpf\Security\Security
     */
    protected $security = null;

    /**
     * clientConfigurationManager
     *
     * @var \EWW\Dpf\Configuration\ClientConfigurationManager
     */
    protected $clientConfigurationManager;

    /**
     * MessageRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MessageRepository
     */
    protected $messageRepository = null;

    /**
     * PersistenceManager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;


    public function __construct(
        ClientRepository $clientRepository,
        DocumentTypeRepository $documentTypeRepository,
        DepositLicenseRepository $depositLicenseRepository,
        Security $security,
        ClientConfigurationManager $clientConfigurationManager,
        MessageRepository $messageRepository,
        PersistenceManagerInterface $persistenceManager
    )
    {
        $this->clientRepository = $clientRepository;
        $this->documentTypeRepository = $documentTypeRepository;
        $this->depositLicenseRepository = $depositLicenseRepository;
        $this->security = $security;
        $this->clientConfigurationManager = $clientConfigurationManager;
        $this->messageRepository = $messageRepository;
        $this->persistenceManager = $persistenceManager;
    }

    public function sendAdminNewSuggestionNotification(\EWW\Dpf\Domain\Model\Document $document) {
        try {
            /** @var $client \EWW\Dpf\Domain\Model\Client */
            $client = $this->clientRepository->findAll()->current();
            $clientAdminEmail = $client->getAdminEmail();
            $documentType = $this->documentTypeRepository->findOneByUid($document->getDocumentType());

            $args = $this->getMailMarkerArray($document, $client, $documentType);

            // Notify client admin
            if ($clientAdminEmail) {
                $subject = $client->getAdminNewSuggestionSubject();
                $body = $client->getAdminNewSuggestionBody();
                $mailType = 'text/html';

                if (empty($subject)) {
                    $subject = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.newSuggestion.admin.subject', 'dpf');
                }

                if (empty($body)) {
                    $body = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.newSuggestion.admin.body', 'dpf');
                    $mailType = 'text/plain';
                }

                $this->sendMail($clientAdminEmail, $subject, $body, $args, $mailType);

            }

        } catch (\Exception $e) {
            /** @var $logger \TYPO3\CMS\Core\Log\Logger */
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

            $logger->log(
                LogLevel::ERROR, "sendAdminNewSuggestionNotification failed",
                array(
                    'document' => $document
                )
            );
        }
    }


    public function sendAdminEmbargoExpiredNotification(\EWW\Dpf\Domain\Model\Document $document) {
        try {
            /** @var $client \EWW\Dpf\Domain\Model\Client */
            $client = $this->clientRepository->findAll()->current();
            $clientAdminEmail = $client->getAdminEmail();
            $documentType = $this->documentTypeRepository->findOneByUid($document->getDocumentType());

            $args = $this->getMailMarkerArray($document, $client, $documentType);

            // Notify client admin
            if ($clientAdminEmail) {
                $subject = $client->getAdminEmbargoSubject();
                $body = $client->getAdminEmbargoBody();
                $mailType = 'text/html';

                if (empty($subject)) {
                    $subject = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.embargoExpired.admin.subject', 'dpf');
                }

                if (empty($body)) {
                    $body = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.embargoExpired.admin.body', 'dpf');
                    $mailType = 'text/plain';
                }

                $this->sendMail($clientAdminEmail, $subject, $body, $args, $mailType);

            }

        } catch (\Exception $e) {
            /** @var $logger \TYPO3\CMS\Core\Log\Logger */
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

            $logger->log(
                LogLevel::ERROR, "sendAdminEmbargoExpiredNotification failed",
                array(
                    'document' => $document
                )
            );
        }
    }

    public function getMailMarkerArray(Document $document, $client, $documentType, $reason = "") {

        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);

        /** @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager */
        $configurationManager = $objectManager->get('TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface');
        $settings = $configurationManager->getConfiguration(
            \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT,
            'dpf',
            'backoffice'
        );

        $args['###CLIENT###'] = $client->getClient();
        $args['###PROCESS_NUMBER###'] = $document->getProcessNumber();

        $args['###DOCUMENT_IDENTIFIER###'] = $document->getObjectIdentifier();

        if ($documentType) {
            $args['###DOCUMENT_TYPE###'] = $documentType->getDisplayName();
        } else {
            $args['###DOCUMENT_TYPE###'] = '';
        }

        $args['###TITLE###'] = $document->getTitle();

        $internalFormat = new InternalFormat($document->getXmlData());
        $authors = $internalFormat->getAuthors();
        $author = array_shift($authors);
        $args['###AUTHOR###'] = $author['name'];

        $args['###SUBMITTER_NAME###'] = $internalFormat->getSubmitterName();
        $args['###SUBMITTER_EMAIL###'] = $internalFormat->getSubmitterEmail();
        $args['###SUBMITTER_NOTICE###'] = $internalFormat->getSubmitterNotice();

        $args['###DATE###'] = (new \DateTime)->format("d-m-Y H:i:s");
        $args['###URN###'] = $internalFormat->getPrimaryUrn();
        $args['###URL###'] = 'http://nbn-resolving.de/' . $internalFormat->getPrimaryUrn();

        $args['###REASON###'] = $reason;

        $host = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
        $backofficePageId = $settings['plugin.']['tx_dpf.']['settings.']['backofficePluginPage'];

        /** @var \EWW\Dpf\Domain\Model\DepositLicense $depositLicense */
        $depositLicense = $this->depositLicenseRepository->findOneByUri($document->getDepositLicense());
        if ($depositLicense instanceof \EWW\Dpf\Domain\Model\DepositLicense) {
            $args['###LICENSE_URI###'] = $depositLicense->getUri();
            $args['###LICENSE_TEXT###'] = $depositLicense->getText();
        }

        $args['###LICENSE_USERNAME###'] = $this->security->getUsername();

        if ($document->isSuggestion()) {
            $detailUrl = '<a href="' . $host . '/index.php?id=' . $backofficePageId;
            $detailUrl .= '&tx_dpf_backoffice[document]=' . $document->getUid();
            $detailUrl .= '&tx_dpf_backoffice[action]=showSuggestionDetails';
            $detailUrl .= '&tx_dpf_backoffice[controller]=Document">Link zum Änderungsvorschlag</a>';
        } else {
            $documentIdentifier = $document->getProcessNumber();
            if (empty($documentIdentifier)) {
                $documentIdentifier = $document->getDocumentIdentifier();
            }
            $detailUrl = '<a href="' . $host . '/index.php?id=' . $backofficePageId;
            $detailUrl .= '&tx_dpf_backoffice[document]=' . $documentIdentifier;
            $detailUrl .= '&tx_dpf_backoffice[action]=showDetails';
            $detailUrl .= '&tx_dpf_backoffice[controller]=Document">Link zum Dokument</a>';
        }

        $args['###DETAIL_URL###'] = $detailUrl;

        $args['###HAS_FILES###'] = 'Metadata only';

        if ($document->hasFiles()) {
            $args['###HAS_FILES###'] = 'Attachment';
            $fileList = [];
            foreach ($document->getFile() as $file) {
                if (!$file->isFileGroupDeleted()) {
                    $fileList[] = $file->getTitle();
                }
            }
            $args['###FILE_LIST###'] .= implode(", ", $fileList);
        }

        return $args;
    }

    public function sendSuggestionAcceptNotification(\EWW\Dpf\Domain\Model\Document $document)
    {
        /** @var Client $client */
        $client = $this->clientRepository->findAll()->current();

        $internalFormat = new InternalFormat($document->getXmlData());
        if ($internalFormat->getFisId() || true) {
            // Active messaging: Suggestion accept
            $this->sendActiveMessage(
                $document,
                $client->getActiveMessagingSuggestionAcceptUrl(),
                $client->getActiveMessagingSuggestionAcceptUrlBody(),
                __FUNCTION__
            );
        }
    }

    /**
     * @param Document $document
     * @param string $reason
     */
    public function sendSuggestionDeclineNotification(\EWW\Dpf\Domain\Model\Document $document, $reason = "")
    {
        /** @var Client $client */
        $client = $this->clientRepository->findAll()->current();

        $internalFormat = new InternalFormat($document->getXmlData());
        if ($internalFormat->getFisId()) {
            // Active messaging: Suggestion decline
            $this->sendActiveMessage(
                $document,
                $client->getActiveMessagingSuggestionDeclineUrl(),
                $client->getActiveMessagingSuggestionDeclineUrlBody(),
                __FUNCTION__,
                $reason
            );
        }
    }

    /**
     * @param Document $document
     * @param string $reason
     */
    public function sendDocumentDeletedNotification(\EWW\Dpf\Domain\Model\Document $document, $reason = "")
    {
        /** @var Client $client */
        $client = $this->clientRepository->findAll()->current();

        $internalFormat = new InternalFormat($document->getXmlData());
        if ($internalFormat->getFisId() && $this->isFisRelevant($document)) {
            // Active messaging: Suggestion decline
            $this->sendActiveMessage(
                $document,
                $client->getActiveMessagingDocumentDeletedUrl(),
                $client->getActiveMessagingDocumentDeletedUrlBody(),
                __FUNCTION__,
                $reason
            );
        }
    }

    public function sendChangedDocumentNotification(\EWW\Dpf\Domain\Model\Document $document, $addedFisIdOnly = false)
    {
        /** @var Client $client */
        $client = $this->clientRepository->findAll()->current();

        $internalFormat = new InternalFormat($document->getXmlData());

        // Active messaging: Document changed
        if (!$addedFisIdOnly && $internalFormat->getFisId() && $this->isFisRelevant($document)) {
            $this->sendActiveMessage(
                $document,
                $client->getActiveMessagingChangedDocumentUrl(),
                $client->getActiveMessagingChangedDocumentUrlBody(),
                __FUNCTION__
            );
        }
    }

    public function sendReleasePublishNotification(\EWW\Dpf\Domain\Model\Document $document)
    {
        /** @var Client $client */
        $client = $this->clientRepository->findAll()->current();

        if ($this->isFisRelevant($document)) {
            // Active messaging: New document (Release publish)
            $this->sendActiveMessage(
                $document,
                $client->getActiveMessagingNewDocumentUrl(),
                $client->getActiveMessagingNewDocumentUrlBody(),
                __FUNCTION__
            );
        }
    }

    /*
     * Email notification to an email address submitted with the document form when a document is published.
     */
    public function sendRegisterNotificationToSubmitter(\EWW\Dpf\Domain\Model\Document $document)
    {
        try {
            /** @var Client $client */
            $client = $this->clientRepository->findAll()->current();
            $internalFormat = new InternalFormat($document->getXmlData());
            $submitterEmail = $internalFormat->getSubmitterEmail();
            $documentType = $this->documentTypeRepository->findOneByUid($document->getDocumentType());

            $args = $this->getMailMarkerArray($document, $client, $documentType);

            // Notify submitter
            if ($submitterEmail) {
                $subject = $client->getSubmitterRegisterDocumentNotificationSubject();
                $body = $client->getSubmitterRegisterDocumentNotificationBody();
                $mailType = 'text/html';

                if (empty($subject)) {
                    $subject = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.newDocument.submitter.subject', 'dpf');
                }

                if (empty($body)) {
                    $body = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.newDocument.submitter.body', 'dpf');
                    $mailType = 'text/plain';
                }

                $this->sendMail($submitterEmail, $subject, $body, $args, $mailType);
            }

        } catch (\Exception $e) {
            /** @var $logger \TYPO3\CMS\Core\Log\Logger */
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

            $logger->log(
                LogLevel::ERROR, "sendRegisterNotificationToSubmitter failed",
                array(
                    'document' => $document
                )
            );
        }

    }

    /*
     * Email notification to an email address submitted with the document form when a document is published.
     *
     */
    public function sendReleasePublishNotificationToSubmitter(\EWW\Dpf\Domain\Model\Document $document)
    {

        try {
            $client = $this->clientRepository->findAll()->current();
            $internalFormat = new InternalFormat($document->getXmlData());
            $submitterEmail = $internalFormat->getSubmitterEmail();
            $documentType = $this->documentTypeRepository->findOneByUid($document->getDocumentType());

            $args = $this->getMailMarkerArray($document, $client, $documentType);

            // Notify submitter
            if ($submitterEmail) {
                $subject = $client->getSubmitterReleaseNotificationSubject();
                $body = $client->getSubmitterReleaseNotificationBody();
                $mailType = 'text/html';

                if (empty($subject)) {
                    $subject = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.ingestDocument.submitter.subject', 'dpf');
                }

                if (empty($body)) {
                    $body = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.ingestDocument.submitter.body', 'dpf');
                    $mailType = 'text/plain';
                }

                $this->sendMail($submitterEmail, $subject, $body, $args, $mailType);
            }
        } catch (\Exception $e) {
            /** @var $logger \TYPO3\CMS\Core\Log\Logger */
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

            $logger->log(
                LogLevel::ERROR, "sendReleasePublishNotificationToSubmitter failed",
                array(
                    'document' => $document
                )
            );
        }

    }

    public function sendEmbargoNotification(\EWW\Dpf\Domain\Model\Document $document) {
        try {
            $client = $this->clientRepository->findAllByPid($document->getPid())->current();
            $clientAdminEmail = $client->getAdminEmail();

            $documentType = $this->documentTypeRepository->findOneByUid($document->getDocumentType());

            $args = $this->getMailMarkerArray($document, $client, $documentType);

            // Notify client admin
            if ($clientAdminEmail) {
                $subject = $client->getAdminEmbargoSubject();
                $body = $client->getAdminEmbargoBody();
                $mailType = 'text/html';

                if (empty($subject)) {
                    $subject = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.document.embargo.admin.subject', 'dpf');
                }

                if (empty($body)) {
                    $body = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.document.embargo.admin.body', 'dpf');
                    $mailType = 'text/plain';
                }

                $this->sendMail($clientAdminEmail, $subject, $body, $args, $mailType);

            }

        } catch (\Exception $e) {
            /** @var $logger \TYPO3\CMS\Core\Log\Logger */
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

            $logger->log(
                LogLevel::ERROR, "sendEmbargoNotification failed",
                array(
                    'document' => $document
                )
            );
        }
    }

    public function sendRegisterNotification(\EWW\Dpf\Domain\Model\Document $document)
    {

        try {
            $client = $this->clientRepository->findAll()->current();
            $clientAdminEmail = $client->getAdminEmail();
            $documentType = $this->documentTypeRepository->findOneByUid($document->getDocumentType());

            $args = $this->getMailMarkerArray($document, $client, $documentType);

            // Notify client admin
            if ($clientAdminEmail) {
                $subject = $client->getAdminRegisterDocumentNotificationSubject();
                $body = $client->getAdminRegisterDocumentNotificationBody();
                $mailType = 'text/html';

                if (empty($subject)) {
                    $subject = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.registerDocument.admin.subject', 'dpf');
                }

                if (empty($body)) {
                    $body = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.registerDocument.admin.body', 'dpf');
                    $mailType = 'text/plain';
                }

                $this->sendMail($clientAdminEmail, $subject, $body, $args, $mailType);

            }

        } catch (\Exception $e) {
            /** @var $logger \TYPO3\CMS\Core\Log\Logger */
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

            $logger->log(
                LogLevel::ERROR, "sendRegisterNotification failed",
                array(
                    'document' => $document
                )
            );
        }

    }

    /**
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @param array $recipients
     */
    public function sendMyPublicationUpdateNotification(\EWW\Dpf\Domain\Model\Document $document, $recipients)
    {

        try {
            /** @var Client $client */
            $client = $this->clientRepository->findAll()->current();
            $documentType = $this->documentTypeRepository->findOneByUid($document->getDocumentType());

            $args = $this->getMailMarkerArray($document, $client, $documentType);

            // Notify client admin
            /** @var FrontendUser $recipient */
            foreach ($recipients as $recipient) {

                if ($recipient->getEmail()) {

                    $subject = $client->getMypublicationsUpdateNotificationSubject();
                    $body = $client->getMypublicationsUpdateNotificationBody();
                    $mailType = 'text/html';

                    if (empty($subject)) {
                        $subject = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.updatePublication.mypublications.subject',
                            'dpf');
                    }

                    if (empty($body)) {
                        $body = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.updatePublication.mypublications.body',
                            'dpf');
                        $mailType = 'text/plain';
                    }

                    $this->sendMail($recipient->getEmail(), $subject, $body, $args, $mailType);
                }
            }

        } catch (\Exception $e) {
            /** @var $logger \TYPO3\CMS\Core\Log\Logger */
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

            $logger->log(
                LogLevel::ERROR, "sendMyPublicationUpdateNotification failed",
                array(
                    'document' => $document
                )
            );
        }

    }


    /**
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @param array $recipients
     */
    public function sendMyPublicationNewNotification(\EWW\Dpf\Domain\Model\Document $document, $recipients)
    {

        try {
            /** @var Client $client */
            $client = $this->clientRepository->findAll()->current();
            $documentType = $this->documentTypeRepository->findOneByUid($document->getDocumentType());

            $args = $this->getMailMarkerArray($document, $client, $documentType);

            // Notify client admin
            /** @var FrontendUser $recipient */
            foreach ($recipients as $recipient) {

                if ($recipient->getEmail()) {

                    $subject = $client->getMypublicationsNewNotificationSubject();
                    $body = $client->getMypublicationsNewNotificationBody();
                    $mailType = 'text/html';

                    if (empty($subject)) {
                        $subject = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.newPublication.mypublications.subject',
                            'dpf');
                    }

                    if (empty($body)) {
                        $body = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.newPublication.mypublications.body',
                            'dpf');
                        $mailType = 'text/plain';
                    }

                    $this->sendMail($recipient->getEmail(), $subject, $body, $args, $mailType);
                }
            }

        } catch (\Exception $e) {
            /** @var $logger \TYPO3\CMS\Core\Log\Logger */
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

            $logger->log(
                LogLevel::ERROR, "sendMyPublicationNewNotification failed",
                array(
                    'document' => $document
                )
            );
        }

    }

    public function sendDepositLicenseNotification(\EWW\Dpf\Domain\Model\Document $document)
    {

        try {
            /** @var Client $client */
            $client = $this->clientRepository->findAll()->current();
            $clientAdminEmail = $client->getAdminEmail();
            $documentType = $this->documentTypeRepository->findOneByUid($document->getDocumentType());

            $args = $this->getMailMarkerArray($document, $client, $documentType);

            // Notify client admin
            if ($clientAdminEmail && $client->isSendAdminDepositLicenseNotification()) {

                $subject = $client->getAdminDepositLicenseNotificationSubject();
                $body = $client->getAdminDepositLicenseNotificationBody();
                $mailType = 'text/html';

                if (empty($subject)) {
                    $subject = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.depositLicense.admin.subject', 'dpf');
                }

                if (empty($body)) {
                    $body = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.depositLicense.admin.body', 'dpf');
                    $mailType = 'text/plain';
                }

                $this->sendMail($clientAdminEmail, $subject, $body, $args, $mailType);
            }

        } catch (\Exception $e) {
            /** @var $logger \TYPO3\CMS\Core\Log\Logger */
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

            $logger->log(
                LogLevel::ERROR, "sendDepositLicenseNotification failed",
                array(
                    'document' => $document
                )
            );
        }

    }

    protected function replaceMarkers($message, $args)
    {
        if (is_array($args)) {
            foreach ($args as $key => $value) {
                $message = str_replace($key, $value, $message);
            }
        }
        return $message;
    }


    protected function sendMail($reveiver, $subject, $body, $args, $mailType)
    {
        $noReplyAddress = $this->clientConfigurationManager->getNoReplyAddress();
        $emailReceiver = array();
        $emailReceiver[$reveiver] = $reveiver;
        $message = (new \TYPO3\CMS\Core\Mail\MailMessage())
            ->setFrom([$noReplyAddress => $noReplyAddress])
            ->setTo($emailReceiver)
            ->setSubject($this->replaceMarkers($subject,$args))
            ->setBody($this->replaceMarkers($body,$args),$mailType);
        $message->send();
    }

    /**
     * Checks if the document is relevant for the FIS.
     *
     * @param Document $document
     * @return bool
     */
    protected function isFisRelevant(Document $document)
    {
        $collectionXpath = $this->clientConfigurationManager->getCollectionXpath();
        $internalFormat = new InternalFormat($document->getXmlData());
        $collections = $internalFormat->getCollections();

        $fisCollections = $this->clientConfigurationManager->getFisCollections();

        if (empty($fisCollections) || empty($collectionXpath)) {
            return true;
        }

        foreach ($fisCollections as $fisCollection) {
            if (in_array(strtolower($fisCollection), array_map('strtolower', $collections))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Document $document
     * @param string $url
     * @param string $body
     * @param string $functionName
     * @param string $reason
     * @param bool   $retry
     * @return bool|[] true if the message was sent successfully, false if nothing was sent, or array with error with infos.
     * @throws ActiveMessageException
     * @throws ConnectionErrorException
     */
    protected function sendActiveMessage(
        Document $document, string $url, string $body, string $functionName, string $reason = "", $retry = false
    )
    {
        /** @var Client $client */
        $client = $this->clientRepository->findAll()->current();

        try {
            $documentType = $this->documentTypeRepository->findOneByUid($document->getDocumentType());
            $args = $this->getMailMarkerArray($document, $client, $documentType, $reason);
            
            if ($url) {

                $request = Request::post($url);
                if ($body) {
                    $request->body($this->replaceMarkers($body, $args));
                }

                $request->timeout(10);
                $res = $request->send();

                if ($res->hasErrors() || $res->code != 200) {
                    throw new ActiveMessageException(
                        sprintf(
                            'Connection to "%s" failed. Status code: %d.',
                            $url,
                            $res->code
                        ),
                        $res->code
                    );
                }

                return true;
            }

            return false;

        } catch (\Throwable $e) {

            $httpCode = -1;
            $curlCode = -1;

            if ($e instanceof ActiveMessageException) {
                $httpCode = $e->getCode();
            }

            if ($e instanceof ConnectionErrorException) {
                $curlCode = $e->getCurlErrorNumber();
            }

            /** @var $logger \TYPO3\CMS\Core\Log\Logger */
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__)->info(
                $functionName." sent",
                [
                    'document' => $document->getProcessNumber()
                ]
            );

            /** @var $logger \TYPO3\CMS\Core\Log\Logger */
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__)->error(
                $functionName." failed: " . $e->getMessage(),
                [
                    'document' => $document->getProcessNumber(),
                    'exception' => $e->getMessage()
                ]
            );

            if (!$retry) {
                $message = new Message();
                $message->setDocument($document);
                $message->setUrl($url);
                $message->setBody($body);
                $message->setFunctionname($functionName);
                $message->setReason($reason);
                $message->setHttpCode($httpCode);
                $message->setCurlCode($curlCode);

                $this->messageRepository->add($message);
                $this->persistenceManager->persistAll();
            }

            $type = self::ACTIVE_MESSAGE_ERROR_TYPE_UNKNOWN;

            if ($httpCode > 0) {
                $type = self::ACTIVE_MESSAGE_ERROR_TYPE_HTTP;
            }

            if ($curlCode > 0) {
                $type = self::ACTIVE_MESSAGE_ERROR_TYPE_CURL;
            }

            return [
              'type' => $type,
              'httpCode' => $httpCode,
              'curlCode' => $curlCode
            ];
        }
    }

    /**
     * Resend a failed message
     *
     * @param Message $message
     * @return bool|int true if the message was sent successfully, false if failed, or error type (curl or http).
     * @throws ActiveMessageException
     * @throws ConnectionErrorException
     */
    public function retryActiveMessage(Message $message)
    {
        try {
            /** @var Client $client */
            $client = $this->clientRepository->findAll()->current();

            $result = $this->sendActiveMessage(
                    $message->getDocument(),
                    $client->getActiveMessagingSuggestionAcceptUrl(),
                    $message->getBody(),
                    $message->getFunctionname(),
                    $message->getReason(),
                    true
            );

            if ($result === true) {
                // Delete from DB if sending was successful.
                $this->messageRepository->remove($message);
                $this->persistenceManager->persistAll();
            } else {
                $message->setChangedTime(time());
                $message->setUrl($client->getActiveMessagingSuggestionAcceptUrl());
                $message->setBody($client->getActiveMessagingSuggestionAcceptUrlBody());
                if (is_array($result)) {
                    $message->setHttpCode($result['httpCode']);
                    $message->setCurlCode($result['curlCode']);
                } else {
                    // Nothing sent due to empty URL in configuration.
                    $message->setHttpCode(-1);
                    $message->setCurlCode(-1);
                }

                $this->messageRepository->update($message);
                $this->persistenceManager->persistAll();
            }

            return $result;

        } catch (\Exception $e) {
            /** @var $logger \TYPO3\CMS\Core\Log\Logger */
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__)->error(
                "Retry: " . $message->getFunctionname()." failed: " . $e->getMessage(),
                [
                    'document' => $message->getDocument()->getProcessNumber(),
                    'exception' => $e->getMessage()
                ]
            );

            return [
                'type' => 'unknown',
                'code' => 0
            ];
        }
    }

}
