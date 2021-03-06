<?php
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

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

use EWW\Dpf\Domain\Repository\DocumentRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use EWW\Dpf\Security\Security;


class ext_update {

    // Ideally the version corresponds with the extension version
    const NEW_VERSION = "v5.0.0";

    // The version
    const VERSION_4_0_0 = "v4.0.0";

    public function access() {
        $registry = GeneralUtility::makeInstance(Registry::class);
        $version = $registry->get('tx_dpf','updatescript-'.self::NEW_VERSION);
        // If the version has already been registered in the table sys_register the updatscript will be blocked.
        if ($version) {
            return FALSE;
        }

        return TRUE;
    }

    public function main() {
        // This script registers itself into the sys_registry table to prevent a re-run with the same version number.
        $registry = GeneralUtility::makeInstance(Registry::class);
        $newVersion = $registry->get('tx_dpf','updatescript-'.self::NEW_VERSION);

        if ($newVersion) {
            return FALSE;
        } else {
            try {
                // The necessary updates.
                // 3->4: (new UpdateState)->execute();
                // 3->4: (new UpdateAccessRestrictions)->execute();
                // 3->4: (new UpdateVirtualType)->execute();
                (new UpdateMetadataObjectValidator)->execute();
            } catch (\Throwable $throwable) {
                return "Error while updating the extension: ".($throwable->getMessage());
            }
            $registry->set('tx_dpf','updatescript-'.self::NEW_VERSION, TRUE);
            return "The extension has been successfully updated.";
        }
    }
}


class UpdateState
{
    const OBJECT_STATE_NEW             = "NEW";
    const OBJECT_STATE_ACTIVE          = "ACTIVE";
    const OBJECT_STATE_INACTIVE        = "INACTIVE";
    const OBJECT_STATE_DELETED         = "DELETED";
    const OBJECT_STATE_LOCALLY_DELETED = "LOCALLY_DELETED";

    public function execute()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $documentRepository = $objectManager->get(DocumentRepository::class);

        $documents = $documentRepository->crossClientFindAll();

        foreach ($documents as $oldDocument) {
            $oldState = $oldDocument['state'];
            $objectIdentifier = $oldDocument['objectIdentifier'];

            $newDocument = $documentRepository->findByUid($oldDocument['uid']);

            switch ($oldState) {
                case self::OBJECT_STATE_NEW:
                    $newDocument->setState(\EWW\Dpf\Domain\Workflow\DocumentWorkflow::STATE_NEW_NONE);
                    break;
                case self::OBJECT_STATE_ACTIVE:
                    $newDocument->setState(\EWW\Dpf\Domain\Workflow\DocumentWorkflow::STATE_IN_PROGRESS_ACTIVE);
                    break;
                case self::OBJECT_STATE_INACTIVE:
                    $newDocument->setState(\EWW\Dpf\Domain\Workflow\DocumentWorkflow::STATE_IN_PROGRESS_INACTIVE);
                    break;
                case self::OBJECT_STATE_DELETED:
                    $newDocument->setState(\EWW\Dpf\Domain\Workflow\DocumentWorkflow::STATE_IN_PROGRESS_DELETED);
                    break;
                case self::OBJECT_STATE_LOCALLY_DELETED:
                    $newDocument->setState(\EWW\Dpf\Domain\Workflow\DocumentWorkflow::STATE_NONE_NONE);
                    break;
            }

            $documentRepository->update($newDocument);
        }
    }
}

class UpdateAccessRestrictions
{
    public function execute() {

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $repositories[] = $objectManager->get(\EWW\Dpf\Domain\Repository\MetadataObjectRepository::class);
        $repositories[] = $objectManager->get(\EWW\Dpf\Domain\Repository\MetadataGroupRepository::class);
        $repositories[] = $objectManager->get(\EWW\Dpf\Domain\Repository\MetadataPageRepository::class);

        foreach ($repositories as $repository) {
            foreach ($repository->crossClientFindAll() as $record) {
                if ($record['backend_only']) {
                    $recordObject = $repository->findByUid($record['uid']);
                    $recordObject->setAccessRestrictionRoles(array(Security::ROLE_LIBRARIAN, Security::ROLE_RESEARCHER));
                    $repository->update($recordObject);
                }
            }
        }
    }
}

class UpdateVirtualType
{
    public function execute() {

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $repository = $objectManager->get(\EWW\Dpf\Domain\Repository\DocumentTypeRepository::class);

        foreach ($repository->crossClientFindAll() as $record) {
            if ($record['virtual']) {
                $recordObject = $repository->findByUid($record['uid']);
                $recordObject->setVirtualType($record['virtual'] === 1);
                $repository->update($recordObject);
            }
        }

       // $GLOBALS['TYPO3_DB']->sql_query("ALTER TABLE tx_dpf_domain_model_documenttype CHANGE virtual zzz_deleted_virtual SMALLINT UNSIGNED DEFAULT 0 NOT NULL");

    }
}

class UpdateMetadataObjectValidator
{
    public function execute() {

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $repository = $objectManager->get(\EWW\Dpf\Domain\Repository\MetadataObjectRepository::class);

        foreach ($repository->crossClientFindAll() as $record) {
            if ($record['data_type']) {
                $recordObject = $repository->findByUid($record['uid']);
                $recordObject->setValidator($record['data_type']);
                $repository->update($recordObject);
            }
        }
    }
}
