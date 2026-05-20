<?php
namespace EWW\Dpf\Command;

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

use EWW\Dpf\Domain\Repository\ClientRepository;
use EWW\Dpf\Domain\Repository\DocumentRepository;
use EWW\Dpf\Domain\Repository\DocumentTypeRepository;
use EWW\Dpf\Helper\Slub;
use EWW\Dpf\Services\Email\Notifier;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class ResendNotificationCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Resend new-document notification to submitter')
             ->addArgument('process_number', InputArgument::REQUIRED, 'Process number of the document')
             ->addOption('to', null, InputOption::VALUE_REQUIRED, 'Redirect all mail to this address for testing');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $processNumber = $input->getArgument('process_number');
        $overrideTo = $input->getOption('to');

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $documentRepository = $objectManager->get(DocumentRepository::class);
        $clientRepository = $objectManager->get(ClientRepository::class);
        $documentTypeRepository = $objectManager->get(DocumentTypeRepository::class);
        $notifier = $objectManager->get(Notifier::class);

        // Search across all storage pages to find the document
        $querySettings = $objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $documentRepository->setDefaultQuerySettings($querySettings);

        $documents = $documentRepository->findByProcessNumber($processNumber);

        if (!$documents || $documents->count() === 0) {
            $output->writeln('<error>No document found: ' . $processNumber . '</error>');
            return 1;
        }

        $document = $documents->getFirst();

        // Derive client + documentType PID from document — same storage page
        $pidQuerySettings = $objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings::class);
        $pidQuerySettings->setStoragePageIds([$document->getPid()]);
        $clientRepository->setDefaultQuerySettings($pidQuerySettings);
        $documentTypeRepository->setDefaultQuerySettings($pidQuerySettings);

        $client = $clientRepository->findAll()->current();

        if (!$client) {
            $output->writeln('<error>No client record found for storage PID ' . $document->getPid() . '</error>');
            return 1;
        }

        if ($overrideTo) {
            $slub = new Slub($document->getSlubInfoData());
            $output->writeln('Original submitter: ' . $slub->getSubmitterEmail());
            $output->writeln('Original admin:     ' . $client->getAdminEmail());

            // Patch submitter email in slubInfoData in memory
            $slubInfo = preg_replace(
                '/(<foaf:mbox[^>]*>)[^<]*(<\/foaf:mbox>)/',
                '${1}' . htmlspecialchars($overrideTo) . '${2}',
                $document->getSlubInfoData()
            );
            $document->setSlubInfoData($slubInfo);

            // In-memory only — no update()/persistAll() called, so no DB write.
            // Notifier calls clientRepository->findAll()->current() internally.
            // Extbase identity map returns this same already-loaded object from memory,
            // so getAdminEmail() inside Notifier returns the overridden value.
            $client->setAdminEmail($overrideTo);

            $output->writeln('Redirecting all mail to: ' . $overrideTo);
        }

        $notifier->sendNewDocumentNotification($document);
        $output->writeln('<info>Notification triggered for: ' . $processNumber . ' (check mailq if unsure)</info>');
        return 0;
    }
}
