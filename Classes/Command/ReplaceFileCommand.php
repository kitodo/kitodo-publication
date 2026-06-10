<?php
namespace EWW\Dpf\Command;

use EWW\Dpf\Domain\Model\File;
use EWW\Dpf\Domain\Repository\DocumentRepository;
use EWW\Dpf\Domain\Repository\FileRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class ReplaceFileCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Replace or add a file attachment on a document (use for oversized uploads)')
             ->addOption('process-number', null, InputOption::VALUE_REQUIRED, 'Process number of the document')
             ->addOption('url', null, InputOption::VALUE_REQUIRED, 'Download URL for the file')
             ->addOption('datastream', null, InputOption::VALUE_REQUIRED, 'Datastream ID to replace (replace mode, default: ATT-0)', 'ATT-0')
             ->addOption('username', null, InputOption::VALUE_REQUIRED, 'HTTP username for download URL')
             ->addOption('password', null, InputOption::VALUE_REQUIRED, 'HTTP password for download URL')
             ->addOption('label', null, InputOption::VALUE_REQUIRED, 'Display name shown to users')
             ->addOption('title', null, InputOption::VALUE_REQUIRED, 'Physical filename shown in TYPO3 backend')
             ->addOption('add', null, InputOption::VALUE_NONE, 'Add new secondary file instead of replacing existing')
             ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Skip confirmation prompts (for non-interactive use)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $isAdd  = (bool) $input->getOption('add');
        $skipConfirm = (bool) $input->getOption('yes');

        // --- Find document ---
        $processNumber = $input->getOption('process-number');
        if (!$processNumber) {
            $processNumber = $helper->ask($input, $output, new Question('Process number: '));
        }
        if (!$processNumber) {
            $output->writeln('<error>Process number required.</error>');
            return 1;
        }

        $objectManager      = GeneralUtility::makeInstance(ObjectManager::class);
        $documentRepository = $objectManager->get(DocumentRepository::class);
        $fileRepository     = $objectManager->get(FileRepository::class);
        $persistenceManager = $objectManager->get(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface::class);

        $noStoragePage = $objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings::class);
        $noStoragePage->setRespectStoragePage(false);
        $documentRepository->setDefaultQuerySettings($noStoragePage);
        $fileRepository->setDefaultQuerySettings($noStoragePage);

        $documents = $documentRepository->findByProcessNumber($processNumber);
        if (!$documents || $documents->count() === 0) {
            $output->writeln('<error>No document found: ' . $processNumber . '</error>');
            return 1;
        }

        $document = $documents->getFirst();
        $output->writeln('Found: "' . $document->getTitle() . '" (PID ' . $document->getPid() . ')');

        if (!$skipConfirm && !$helper->ask($input, $output, new ConfirmationQuestion('Correct document? [Y/n]: ', true))) {
            $output->writeln('Aborted.');
            return 0;
        }

        $files = $document->getFile();
        $output->writeln('Files on this document:');
        foreach ($files as $f) {
            $primary = $f->isPrimaryFile() ? ' (primary)' : '';
            $output->writeln('  ' . ($f->getDatastreamIdentifier() ?: 'NEW') . '  ' . $f->getTitle() . $primary);
        }

        // --- Find target file (replace mode only) ---
        $targetFile   = null;
        $diskPath     = null;
        $isFedoraLink = false;
        $newLink      = null;
        if (!$isAdd) {
            $datastreamId = $input->getOption('datastream') ?: 'ATT-0';
            if (!$input->getOption('datastream') && !$input->getOption('url')) {
                $datastreamId = $helper->ask($input, $output, new Question('Datastream to replace [ATT-0]: ', 'ATT-0'));
            }

            foreach ($files as $f) {
                if ($f->getDatastreamIdentifier() === $datastreamId) {
                    $targetFile = $f;
                    break;
                }
                // NEW documents have no DSID yet; ATT-0 maps to the primary file
                if ($datastreamId === 'ATT-0' && $f->isPrimaryFile() && !$f->getDatastreamIdentifier()) {
                    $targetFile = $f;
                    break;
                }
            }
            if ($targetFile === null) {
                $output->writeln('<error>Datastream not found: ' . $datastreamId . '</error>');
                return 1;
            }

            $output->writeln('Current title: "' . $targetFile->getTitle() . '"');
            $output->writeln('Current label: "' . $targetFile->getLabel() . '"');
            $output->writeln('Current link:  ' . $targetFile->getLink());

            $urlPath      = parse_url($targetFile->getLink(), PHP_URL_PATH);
            $diskPath     = rtrim(PATH_site, '/') . $urlPath;
            $isFedoraLink = !is_file($diskPath) && (parse_url($targetFile->getLink(), PHP_URL_HOST) !== null);

            if (!is_file($diskPath) && !$isFedoraLink) {
                $output->writeln('<error>File not found on disk: ' . $diskPath . '</error>');
                return 1;
            }

            if ($isFedoraLink) {
                // Recalled document: link points to Fedora — place new file in upload directory
                $conn      = GeneralUtility::makeInstance(ConnectionPool::class)
                               ->getConnectionForTable('tx_dpf_domain_model_client');
                $clientRow = $conn->select(['upload_domain', 'upload_directory'], 'tx_dpf_domain_model_client',
                               ['pid' => $document->getPid()])->fetch();
                $uploadDir    = !empty($clientRow['upload_directory']) ? trim($clientRow['upload_directory'], '/') : 'uploads/tx_dpf';
                $domain       = !empty($clientRow['upload_domain']) ? rtrim($clientRow['upload_domain'], '/') : '';
                $diskUploadDir = rtrim(PATH_site, '/') . '/' . $uploadDir . '/';
                if (!is_dir($diskUploadDir)) {
                    mkdir($diskUploadDir, 0775, true);
                }
                $uniqueName = time() . '_' . substr(md5(uniqid(mt_rand(), true)), 0, 8) . '.pdf';
                $diskPath   = $diskUploadDir . $uniqueName;
                $newLink    = $domain . '/' . $uploadDir . '/' . $uniqueName;
                $output->writeln('Upload domain: ' . ($domain ?: '(none — relative URL will be used)'));
            }
        }

        // --- Download URL ---
        $url = $input->getOption('url');
        if (!$url) {
            $url = $helper->ask($input, $output, new Question('Download URL: '));
        }
        if (!$url) {
            $output->writeln('<error>URL required.</error>');
            return 1;
        }

        // --- Auth ---
        $username = $input->getOption('username');
        $password = $input->getOption('password');
        if (!$username && !$input->getOption('url')) {
            $username = $helper->ask($input, $output, new Question('HTTP username (empty for no auth): ', ''));
            if ($username) {
                $pwQuestion = new Question('HTTP password: ', '');
                $pwQuestion->setHidden(true);
                $password = $helper->ask($input, $output, $pwQuestion);
            }
        }

        // --- Download ---
        $output->write('Downloading... ');
        $tmpPath    = tempnam(sys_get_temp_dir(), 'dpf_replace_');
        $fp         = fopen($tmpPath, 'wb');
        $ch         = curl_init($url);
        $curlDoExec = 'curl_exec';
        curl_setopt_array($ch, [
            CURLOPT_FILE           => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_FAILONERROR    => true,
            CURLOPT_TIMEOUT        => 300,
        ]);
        if ($username) {
            curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
        }
        $ok        = $curlDoExec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if (!$ok) {
            unlink($tmpPath);
            $output->writeln('<error>Download failed: ' . $curlError . '</error>');
            return 1;
        }

        $size = filesize($tmpPath);
        $output->writeln('done (' . round($size / 1048576, 1) . ' MB)');

        // --- Validate PDF ---
        $header = file_get_contents($tmpPath, false, null, 0, 4);
        if ($header !== '%PDF') {
            unlink($tmpPath);
            $output->writeln('<error>Not a PDF (magic bytes: ' . bin2hex($header) . ')</error>');
            return 1;
        }
        $output->writeln('Validated: PDF');

        // --- Label ---
        $newLabel = $input->getOption('label');
        if ($newLabel === null && !$input->getOption('url')) {
            $currentLabel = $isAdd ? '' : $targetFile->getLabel();
            $newLabel = $helper->ask($input, $output, new Question(
                'Display name' . ($currentLabel ? ' [' . $currentLabel . ']: ' : ': '), $currentLabel ?: ''
            ));
        }
        if (!$newLabel && !$isAdd) {
            $newLabel = $targetFile->getLabel();
        }

        // --- Title ---
        $newTitle = $input->getOption('title');
        if ($newTitle === null && !$input->getOption('url')) {
            $currentTitle = $isAdd ? basename($url) : $targetFile->getTitle();
            $newTitle = $helper->ask($input, $output, new Question(
                'Physical filename [' . $currentTitle . ']: ', $currentTitle
            ));
        }
        if (!$newTitle) {
            $newTitle = $isAdd ? basename($url) : $targetFile->getTitle();
        }

        // --- Confirm ---
        $action    = $isAdd ? 'Add file and update DB' : 'Replace file and update DB';
        $confirmed = $skipConfirm || $helper->ask($input, $output, new ConfirmationQuestion($action . '? [y/N]: ', false));
        if (!$confirmed) {
            unlink($tmpPath);
            $output->writeln('Aborted.');
            return 0;
        }

        if ($isAdd) {
            // Derive upload base URL from primary file
            $primaryFile = $fileRepository->getPrimaryFileByDocument($document);
            if (!$primaryFile) {
                unlink($tmpPath);
                $output->writeln('<error>No primary file found — cannot derive upload URL base.</error>');
                return 1;
            }
            $existingLink = $primaryFile->getLink();
            $baseUrl      = substr($existingLink, 0, strrpos($existingLink, '/') + 1);
            $uniqueName   = time() . substr(md5(uniqid(mt_rand(), true)), 0, 14) . '.' . substr(md5(uniqid()), 0, 8);
            $newDiskPath  = rtrim(PATH_site, '/') . parse_url($baseUrl, PHP_URL_PATH) . $uniqueName;
            $publicUrl    = $baseUrl . $uniqueName;

            copy($tmpPath, $newDiskPath);
            unlink($tmpPath);
            chmod($newDiskPath, 0664);
            $output->writeln('Placed: ' . $newDiskPath);

            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('tx_dpf_domain_model_file')
                ->insert('tx_dpf_domain_model_file', [
                    'pid'                   => $document->getPid(),
                    'document'              => $document->getUid(),
                    'primary_file'          => 0,
                    'download'              => 1,
                    'archive'               => 0,
                    'label'                 => $newLabel ?: '',
                    'title'                 => $newTitle,
                    'status'                => File::STATUS_ADDED,
                    'content_type'          => 'application/pdf',
                    'datastream_identifier' => '',
                    'link'                  => $publicUrl,
                    'crdate'                => time(),
                    'tstamp'                => time(),
                ]);
            $output->writeln('DB record created, link: ' . $publicUrl);
        } else {
            copy($tmpPath, $diskPath);
            unlink($tmpPath);
            chmod($diskPath, 0664);
            $output->writeln('Placed: ' . $diskPath);

            $targetFile->setLabel($newLabel);
            $targetFile->setTitle($newTitle);
            if ($isFedoraLink) {
                $targetFile->setLink($newLink);
                $targetFile->setStatus(\EWW\Dpf\Domain\Model\File::STATUS_CHANGED);
                $output->writeln('Link updated: ' . $newLink);
                $output->writeln('Status set to: changed (FLocat will be included on next publish)');
            }
            $fileRepository->update($targetFile);
            $persistenceManager->persistAll();
            $output->writeln('Title: "' . $newTitle . '", Label: "' . $newLabel . '"');
        }

        $output->writeln('Done.');
        return 0;
    }
}
