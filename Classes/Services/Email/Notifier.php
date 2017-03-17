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

            $args['###CLIENT###'] = $client->getClient();
            $args['###PROCESS_NUMBER###'] = $document->getProcessNumber();
            $args['###DOCUMENT_TYPE###'] = $documentType->getDisplayName();
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

        } catch (\Exception $e) {}

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
            $authors = $document->getAuthors();

            $args['###CLIENT###'] = $client->getClient();
            $args['###PROCESS_NUMBER###'] = $document->getProcessNumber();
            $args['###DOCUMENT_TYPE###'] = $documentType->getDisplayName();
            $args['###TITLE###'] = $document->getTitle();
            $args['###AUTHOR###'] = array_shift($authors);

            $args['###SUBMITTER_NAME###'] = $slub->getSubmitterName();
            $args['###SUBMITTER_EMAIL###'] = $submitterEmail; //
            $args['###SUBMITTER_NOTICE###'] = $slub->getSubmitterNotice();

            $args['###DATE###'] = (new \DateTime)->format("d-m-Y H:i:s");
            $args['###URN###'] = $mods->getQucosaUrn();
            $args['###URL###'] = 'http://nbn-resolving.de/' . $mods->getQucosaUrn();

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
        } catch (\Exception $e) {}

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
