<?php
namespace EWW\Dpf\Tasks;

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

use EWW\Dpf\Domain\Model\Client;
use EWW\Dpf\Domain\Model\File;
use EWW\Dpf\Services\File\FileValidator;
use EWW\Dpf\Services\File\FileValidatorInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use EWW\Dpf\Domain\Repository\ClientRepository;
use EWW\Dpf\Domain\Repository\FileRepository;
use TYPO3\CMS\Scheduler\Task\AbstractTask;


class FileValidationTask extends AbstractTask
{
    /**
     * @var FileRepository
     */
    protected $fileRepository;

    /**
     * @var ClientRepository
     */
    protected $clientRepository;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    public function execute()
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->clientRepository = $this->objectManager->get(ClientRepository::class);
        $this->fileRepository = $this->objectManager->get(FileRepository::class);

        $persistenceManager = $this->objectManager->get(
            \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface::class
        );

        /** @var FileValidatorInterface $fileValidator */
        $fileValidator = $this->objectManager->get(FileValidator::class);

        $this->clientRepository->crossClient(true);
        $clients= $this->clientRepository->findAll();

        /** @var Client $client */
        foreach ($clients as $client) {

            $this->fileRepository->setStoragePid($client->getPid());
            $files = $this->fileRepository->findAll();

            /** @var File $file */
            foreach($files as $file) {
                if ($file->getArchive()) {
                    $fileValidationResults = $fileValidator->validate(
                        $file->getUrl(),
                        $file->getContentType(),
                        $file->getDownload(),
                        $file->getArchive()
                    );

                    $file->setValidationResults($fileValidationResults);
                    $this->fileRepository->update($file);
                }
            }

            $persistenceManager->persistAll();
        }

        return true;
    }
}
