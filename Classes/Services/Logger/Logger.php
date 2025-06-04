<?php
namespace EWW\Dpf\Services\Logger;

use EWW\Dpf\Domain\Repository\ClientRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\Writer\DatabaseWriter;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Logger extends DatabaseWriter
{
    public function writeLog(LogRecord $record)
    {
        $data = $record->getData();

        $fieldValues = [
            'request_id' => $record->getRequestId(),
            'time_micro' => $record->getCreated(),
            'component' => $record->getComponent(),
            'level' => $record->getLevel(),
            'message' => $record->getMessage(),
            'data' => json_encode($data)
        ];

        $clientRepository = GeneralUtility::makeInstance(ClientRepository::class);
        $client = $clientRepository->findAll()->current();

        if ($client) {
            $fieldValues['client_id'] = $client->getUid();
        } else {
            throw new \Exception('Error: No client found.');
        }

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->logTable);

        $connection->insert(
            $this->logTable,
            $fieldValues
        );

        return true;
    }
}
