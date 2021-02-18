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

use EWW\Dpf\Domain\Model\Document;
use \TYPO3\CMS\Core\Log\LogLevel;
use \TYPO3\CMS\Core\Log\LogManager;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use EWW\Dpf\Domain\Model\FrontendUser;
use EWW\Dpf\Domain\Model\Client;
use \Httpful\Request;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;

class Notifier
{
    /**
     * clientRepository
     *
     * @var \EWW\Dpf\Domain\Repository\ClientRepository
     * @inject
     */
    protected $clientRepository = null;

    /**
     * documentTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     * @inject
     */
    protected $documentTypeRepository = null;

    /**
     * depositLicenseRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DepositLicenseRepository
     * @inject
     */
    protected $depositLicenseRepository = null;

    /**
     * security
     *
     * @var \EWW\Dpf\Security\Security
     * @inject
     */
    protected $security = null;

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

        $author = array_shift($document->getAuthors());
        $args['###AUTHOR###'] = $author['name'];

        $internalFormat = new \EWW\Dpf\Helper\InternalFormat($document->getXmlData());
        $args['###SUBMITTER_NAME###'] = $internalFormat->getSubmitterName();
        $args['###SUBMITTER_EMAIL###'] = $internalFormat->getSubmitterEmail();
        $args['###SUBMITTER_NOTICE###'] = $internalFormat->getSubmitterNotice();

        $args['###DATE###'] = (new \DateTime)->format("d-m-Y H:i:s");
        $args['###URN###'] = $internalFormat->getQucosaUrn();
        $args['###URL###'] = 'http://nbn-resolving.de/' . $internalFormat->getQucosaUrn();

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

        if ($document->getFileData()) {
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

    public function sendSuggestionAcceptNotification(\EWW\Dpf\Domain\Model\Document $document) {

        try {
            /** @var Client $client */
            $client = $this->clientRepository->findAll()->current();
            $documentType = $this->documentTypeRepository->findOneByUid($document->getDocumentType());

            $internalFormat = new \EWW\Dpf\Helper\InternalFormat($document->getXmlData());

            $args = $this->getMailMarkerArray($document, $client, $documentType);

            // Active messaging: Suggestion accept
            if ($client->getActiveMessagingSuggestionAcceptUrl()) {
                if ($internalFormat->getFisId()) {
                    $request = Request::post($client->getActiveMessagingSuggestionAcceptUrl());
                    if ($body = $client->getActiveMessagingSuggestionAcceptUrlBody()) {
                        $request->body($this->replaceMarkers($body, $args));
                    }
                    $request->send();
                }
            }

        } catch (\Exception $e) {
            /** @var $logger \TYPO3\CMS\Core\Log\Logger */
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

            $logger->log(
                LogLevel::ERROR, "sendSuggestionAcceptNotification failed",
                array(
                    'document' => $document
                )
            );
        }
    }

    /**
     * @param Document $document
     * @param string $reason
     */
    public function sendSuggestionDeclineNotification(\EWW\Dpf\Domain\Model\Document $document, $reason = "") {

        try {
            /** @var Client $client */
            $client = $this->clientRepository->findAll()->current();
            $documentType = $this->documentTypeRepository->findOneByUid($document->getDocumentType());

            $internalFormat = new \EWW\Dpf\Helper\InternalFormat($document->getXmlData());

            $args = $this->getMailMarkerArray($document, $client, $documentType, $reason);

            // Active messaging: Suggestion accept
            if ($client->getActiveMessagingSuggestionDeclineUrl()) {
                if ($internalFormat->getFisId()) {
                    $request = Request::post($client->getActiveMessagingSuggestionDeclineUrl());
                    if ($body = $client->getActiveMessagingSuggestionDeclineUrlBody()) {
                        $request->body($this->replaceMarkers($body, $args));
                    }
                    $request->send();
                }
            }

        } catch (\Exception $e) {
            /** @var $logger \TYPO3\CMS\Core\Log\Logger */
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

            $logger->log(
                LogLevel::ERROR, "sendSuggestionDeclineNotification failed",
                array(
                    'document' => $document
                )
            );
        }
    }

    public function sendChangedDocumentNotification(\EWW\Dpf\Domain\Model\Document $document, $addedFisIdOnly = false) {

        try {
            /** @var Client $client */
            $client = $this->clientRepository->findAll()->current();

            $internalFormat = new \EWW\Dpf\Helper\InternalFormat($document->getXmlData());

            $documentType = $this->documentTypeRepository->findOneByUid($document->getDocumentType());

            $args = $this->getMailMarkerArray($document, $client, $documentType);

            // Active messaging: Suggestion accept
            if (!$addedFisIdOnly && $client->getActiveMessagingChangedDocumentUrl()) {
                if ($internalFormat->getFisId()) {
                    $request = Request::post($client->getActiveMessagingChangedDocumentUrl());
                    if ($body = $client->getActiveMessagingChangedDocumentUrlBody()) {
                        $request->body($this->replaceMarkers($body,$args));
                    }
                    $request->send();
                }
            }

        } catch (\Exception $e) {
            /** @var $logger \TYPO3\CMS\Core\Log\Logger */
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

            $logger->log(
                LogLevel::ERROR, "sendChangedDocumentNotification failed",
                array(
                    'document' => $document
                )
            );
        }
    }


    public function sendReleasePublishNotification(\EWW\Dpf\Domain\Model\Document $document)
    {
        try {
            /** @var Client $client */
            $client = $this->clientRepository->findAll()->current();

            $internalFormat = new \EWW\Dpf\Helper\InternalFormat($document->getXmlData());

            $documentType = $this->documentTypeRepository->findOneByUid($document->getDocumentType());

            $args = $this->getMailMarkerArray($document, $client, $documentType);

            // Active messaging: New document (Release publish)
            if ($client->getActiveMessagingNewDocumentUrl()) {
                $fisId = $internalFormat->getFisId();
                if (empty($fisId)) {
                    $request = Request::post($client->getActiveMessagingNewDocumentUrl());
                    if ($body = $client->getActiveMessagingNewDocumentUrlBody()) {
                        $request->body($this->replaceMarkers($body, $args));
                    }
                    $request->send();
                }
            }

        } catch (\Exception $e) {
            /** @var $logger \TYPO3\CMS\Core\Log\Logger */
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

            $logger->log(
                LogLevel::ERROR, "sendReleasePublishNotification failed",
                array(
                    'document' => $document
                )
            );
        }
    }


    public function sendNewDocumentNotification(\EWW\Dpf\Domain\Model\Document $document)
    {

        try {
            /** @var Client $client */
            $client = $this->clientRepository->findAll()->current();
            $clientAdminEmail = $client->getAdminEmail();
            $internalFormat = new \EWW\Dpf\Helper\InternalFormat($document->getXmlData());
            $submitterEmail = $internalFormat->getSubmitterEmail();
            $documentType = $this->documentTypeRepository->findOneByUid($document->getDocumentType());
            $authors = $document->getAuthors();

            $args = $this->getMailMarkerArray($document, $client, $documentType);

            // Notify client admin
            if ($clientAdminEmail) {
                $subject = $client->getAdminNewDocumentNotificationSubject();
                $body = $client->getAdminNewDocumentNotificationBody();
                $mailType = 'text/html';

                if (empty($subject)) {
                    $subject = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.newDocument.admin.subject', 'dpf');
                }

                if (empty($body)) {
                    $body = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.newDocument.admin.body', 'dpf');
                    $mailType = 'text/plain';
                }

                $this->sendMail($clientAdminEmail, $subject, $body, $args, $mailType);

            }


            // Notify submitter
            if ($submitterEmail) {
                $subject = $client->getSubmitterNewDocumentNotificationSubject();
                $body = $client->getSubmitterNewDocumentNotificationBody();
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
                LogLevel::ERROR, "sendNewDocumentNotification failed",
                array(
                    'document' => $document
                )
            );
        }

    }

    public function sendIngestNotification(\EWW\Dpf\Domain\Model\Document $document)
    {

        try {
            $client = $this->clientRepository->findAll()->current();
            $internalFormat = new \EWW\Dpf\Helper\InternalFormat($document->getXmlData());
            $submitterEmail = $internalFormat->getSubmitterEmail();
            $documentType = $this->documentTypeRepository->findOneByUid($document->getDocumentType());

            $args = $this->getMailMarkerArray($document, $client, $documentType);

            // Notify submitter
            if ($submitterEmail) {
                $subject = $client->getSubmitterIngestNotificationSubject();
                $body = $client->getSubmitterIngestNotificationBody();
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
                LogLevel::ERROR, "sendIngestNotification failed",
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
                LogLevel::ERROR, "sendRegisterNotification failed",
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
            $author = array_shift($document->getAuthors());

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
    public function sendMyPublicationNewNotification(\EWW\Dpf\Domain\Model\Document $document, $recipients)
    {

        try {
            /** @var Client $client */
            $client = $this->clientRepository->findAll()->current();
            $documentType = $this->documentTypeRepository->findOneByUid($document->getDocumentType());
            $author = array_shift($document->getAuthors());

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
                LogLevel::ERROR, "sendRegisterNotification failed",
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
        $emailReceiver = array();
        $emailReceiver[$reveiver] = $reveiver;
        $message = (new \TYPO3\CMS\Core\Mail\MailMessage())
            ->setFrom(array('noreply@qucosa.de' => 'noreply@qucosa.de'))
            ->setTo($emailReceiver)
            ->setSubject($this->replaceMarkers($subject,$args))
            ->setBody($this->replaceMarkers($body,$args),$mailType);
        $message->send();
    }

}
