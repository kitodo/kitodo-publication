<?php
namespace EWW\Dpf\Controller;

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
use EWW\Dpf\Domain\Model\Log;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/**
* Backend module user/group action controller
*/
class BackendLogController extends ActionController
{
    /**
     * @var \EWW\Dpf\Domain\Repository\LogRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $logRepository;

    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentRepository = null;

    /**
     * fileRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FileRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $fileRepository = null;

    /**
     * documentTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentTypeRepository = null;

    /**
     * bookmarkRepository
     *
     * @var \EWW\Dpf\Domain\Repository\BookmarkRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $bookmarkRepository = null;

    /**
     * frontendUserRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FrontendUserRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $frontendUserRepository = null;

    /**
     * clientRepository
     *
     * @var \EWW\Dpf\Domain\Repository\ClientRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $clientRepository = null;

    /**
    * Backend Template Container
    *
    * @var string
    */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * Set up the doc header properly here
     *
     * @param ViewInterface $view
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
    }

    public function listAction(
        string $sortField = 'time_micro',
        string $sortDirection = 'desc',
        ?string $requestId = null,
        ?string $component = null,
        ?string $fromTime = null,
        ?string $toTime = null,
        ?int $clientId = null,
        ?int $level = null,
        ?string $limit = null
    ): void
    {
        $accessibleClients = $this->getAccessibleClients();

        $accessibleClientIds = [];
        foreach ($accessibleClients as $client) {
            $accessibleClientIds[] = $client->getUid();
        }

        $fromDateTime = $fromTime ? new \DateTime($fromTime) : null;
        $toDateTime = $toTime ? new \DateTime($toTime) : null;
        $fromTimestamp = $fromDateTime ? $fromDateTime->getTimestamp() : null;
        $toTimestamp = $toDateTime ? $toDateTime->getTimestamp() : null;

        // Reset level if all (value => -1) has been chosen in the form.
        if ($level !== null && $level < 0) {
            $level = null;
        }

        if ($limit === null || !is_numeric($limit) || $limit <= 0) {
            $limit = 150;
        }

        $logs = $this->logRepository->findByFilters(
            $sortField,
            $sortDirection,
            $requestId,
            $component,
            $fromTimestamp,
            $toTimestamp,
            $clientId,
            $accessibleClientIds,
            $level,
            $limit
        );

        $this->view->assign('accessibleClients', $accessibleClients);

        $this->view->assign('limit', $limit);
        $this->view->assign('logs', $logs);
        $this->view->assign('requestId', $requestId);
        $this->view->assign('component', $component);
        $this->view->assign('fromTime', $fromTime);
        $this->view->assign('toTime', $toTime);
        $this->view->assign('selectedClientId', $clientId);
        $this->view->assign('clients', $accessibleClients);
        $this->view->assign('logLevels', Log::getLogLevels());
        $this->view->assign('level', $level);
        $this->view->assign('sortField', $sortField);
        $this->view->assign('sortDirection', $sortDirection);
    }

    protected function getAccessibleClients() {
        /** @var BackendUserAuthentication $beUser */
        $beUser = $GLOBALS['BE_USER'];
        if ($beUser === null) {
            return [];
        }

        /** @var Client $t */
        $allClients = $this->clientRepository->crossClientFindAll(false)->toArray();
        if (empty($allClients)) {
            return [];
        }

        $accessibleClients = [];
        foreach ($allClients as $client) {
            $page = BackendUtility::getRecord('pages', $client->getPid());

            if (
                $beUser->isAdmin() || (
                    $page &&
                    $beUser->doesUserHaveAccess($page, 1) &&
                    $beUser->isInWebMount($client->getPid())
                )
            ) {
                $accessibleClients[$client->getUid()] = $client;
            }
        }

        return $accessibleClients;
    }
}



