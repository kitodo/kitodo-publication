<?php
namespace EWW\Dpf\Services\Logger;

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
 

class TransferLogger
{

    /**
     * Logs the response of a document repository transfer
     *
     * @param
     * @return void
     */
    public static function log($action, $documentUid, $objectIdentifier, $response)
    {

        $objectManager                 = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\Object\\ObjectManager');
        $documentTransferLogRepository = $objectManager->get('\\EWW\\Dpf\\Domain\\Repository\\DocumentTransferLogRepository');

        $documentTransferLog = $objectManager->get('\\EWW\\Dpf\\Domain\\Model\\DocumentTransferLog');
        $documentTransferLog->setResponse(print_r($response, true));
        $documentTransferLog->setAction($action);
        $documentTransferLog->setDocumentUid($documentUid);
        $documentTransferLog->setObjectIdentifier($objectIdentifier);
        $documentTransferLog->setDate(new \DateTime());
        $documentTransferLogRepository->add($documentTransferLog);
    }

}
