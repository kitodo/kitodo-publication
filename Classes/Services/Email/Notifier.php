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


    public function sendAdminNewSuggestionNotification(\EWW\Dpf\Domain\Model\Document $document) {
        try {
            /** @var $client \EWW\Dpf\Domain\Model\Client */
            $client = $this->clientRepository->findAll()->current();
            $clientAdminEmail = $client->getAdminEmail();
            $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData());
            $slub = new \EWW\Dpf\Helper\Slub($document->getSlubInfoData());
            $documentType = $this->documentTypeRepository->findOneByUid($document->getDocumentType());

            $args = $this->getMailMarkerArray($document, $client, $documentType, $slub, $mods);

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

    public function sendAdminFilesAddedNotification(\EWW\Dpf\Domain\Model\Document $document) {
        try {
            /** @var $client \EWW\Dpf\Domain\Model\Client */
            $client = $this->clientRepository->findAll()->current();
            $clientAdminEmail = $client->getAdminEmail();
            $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData());
            $slub = new \EWW\Dpf\Helper\Slub($document->getSlubInfoData());
            $documentType = $this->documentTypeRepository->findOneByUid($document->getDocumentType());

            $args = $this->getMailMarkerArray($document, $client, $documentType, $slub, $mods);

            // Notify client admin
            if ($clientAdminEmail) {
                $subject = $client->getAdminFilesAddedSubject();
                $body = $client->getAdminFilesAddedBody();
                $mailType = 'text/html';

                if (empty($subject)) {
                    $subject = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.filesAdded.admin.subject', 'dpf');
                }

                if (empty($body)) {
                    $body = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:notification.filesAdded.admin.body', 'dpf');
                    $mailType = 'text/plain';
                }

                $this->sendMail($clientAdminEmail, $subject, $body, $args, $mailType);

            }

        } catch (\Exception $e) {
            /** @var $logger \TYPO3\CMS\Core\Log\Logger */
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

            $logger->log(
                LogLevel::ERROR, "sendAdminFilesAddedNotification failed",
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
            $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData());
            $slub = new \EWW\Dpf\Helper\Slub($document->getSlubInfoData());
            $documentType = $this->documentTypeRepository->findOneByUid($document->getDocumentType());

            $args = $this->getMailMarkerArray($document, $client, $documentType, $slub, $mods);

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


    public function getMailMarkerArray(Document $document, $client, $documentType, $slub, $mods) {

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

        if ($documentType) {
            $args['###DOCUMENT_TYPE###'] = $documentType->getDisplayName();
        } else {
            $args['###DOCUMENT_TYPE###'] = '';
        }

        $args['###TITLE###'] = $document->getTitle();
        $args['###AUTHOR###'] = array_shift($document->getAuthors());

        $args['###SUBMITTER_NAME###'] = $slub->getSubmitterName();
        $args['###SUBMITTER_EMAIL###'] = $slub->getSubmitterEmail();
        $args['###SUBMITTER_NOTICE###'] = $slub->getSubmitterNotice();

        $args['###DATE###'] = (new \DateTime)->format("d-m-Y H:i:s");
        $args['###URN###'] = $mods->getQucosaUrn();
        $args['###URL###'] = 'http://nbn-resolving.de/' . $mods->getQucosaUrn();

        $host = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
        $backofficePageId = $settings['plugin.']['tx_dpf.']['settings.']['backofficePluginPage'];

        $detailUrl = $host . '/index.php?id=' . $backofficePageId;
        $detailUrl .= '&tx_dpf_backoffice[document]=' . $document->getUid();
        $detailUrl .= '&tx_dpf_backoffice[action]=showDetails';
        $detailUrl .= '&tx_dpf_backoffice[controller]=Document';

        $args['###DETAIL_URL###'] = $detailUrl;

        $args['###HAS_FILES###'] = 'Metadata only';

        if ($document->getFileData()) {
            $args['###HAS_FILES###'] = 'Attachement';
            foreach ($document->getFileData() as $fileSection) {
                foreach ($fileSection as $file) {
                    $args['###FILE_LIST###'] .= $file['title'];
                }
            }
        }

        return $args;
    }

    public function sendNewDocumentNotification(\EWW\Dpf\Domain\Model\Document $document)
    {

        try {
            $client = $this->clientRepository->findAll()->current();
            $clientAdminEmail = $client->getAdminEmail();
            $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData());
            $slub = new \EWW\Dpf\Helper\Slub($document->getSlubInfoData());
            $submitterEmail = $slub->getSubmitterEmail();
            $documentType = $this->documentTypeRepository->findOneByUid($document->getDocumentType());
            $authors = $document->getAuthors();

            $args = $this->getMailMarkerArray($document, $client, $documentType, $slub, $mods);

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
            $clientAdminEmail = $client->getAdminEmail();
            $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData());
            $slub = new \EWW\Dpf\Helper\Slub($document->getSlubInfoData());
            $submitterEmail = $slub->getSubmitterEmail();
            $documentType = $this->documentTypeRepository->findOneByUid($document->getDocumentType());

            $args = $this->getMailMarkerArray($document, $client, $documentType, $slub, $mods);

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
            $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData());
            $slub = new \EWW\Dpf\Helper\Slub($document->getSlubInfoData());
            $submitterEmail = $slub->getSubmitterEmail();
<<<<<<< HEAD
//            $documentType = $this->documentTypeRepository->findOneByUid($document->getDocumentType());
            $authors = $document->getAuthors();

            $args['###CLIENT###'] = $client->getClient();
            $args['###PROCESS_NUMBER###'] = $document->getProcessNumber();
//            $args['###DOCUMENT_TYPE###'] = $documentType->getDisplayName();
            $args['###TITLE###'] = $document->getTitle();
            $args['###AUTHOR###'] = array_shift($authors);

            $args['###SUBMITTER_NAME###'] = $slub->getSubmitterName();
            $args['###SUBMITTER_EMAIL###'] = $submitterEmail; //
            $args['###SUBMITTER_NOTICE###'] = $slub->getSubmitterNotice();

            $args['###DATE###'] = (new \DateTime)->format("d-m-Y H:i:s");
            $args['###URN###'] = $mods->getQucosaUrn();
            $args['###URL###'] = 'http://nbn-resolving.de/' . $mods->getQucosaUrn();


            // Notify client admin
            if ($clientAdminEmail) {

=======
            $documentType = $this->documentTypeRepository->findOneByUid($document->getDocumentType());

            $args = $this->getMailMarkerArray($document, $client, $documentType, $slub, $mods);

            // Notify client admin
            if ($clientAdminEmail) {
                $subject = $client->getAdminEmbargoSubject();
                $body = $client->getAdminEmbargoBody();
>>>>>>> add suggestions flashmessage and mail notification
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
            $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData());
            $slub = new \EWW\Dpf\Helper\Slub($document->getSlubInfoData());
            $submitterEmail = $slub->getSubmitterEmail();
            $documentType = $this->documentTypeRepository->findOneByUid($document->getDocumentType());

            $args = $this->getMailMarkerArray($document, $client, $documentType, $slub, $mods);

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
