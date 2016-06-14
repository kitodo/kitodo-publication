<?php
namespace EWW\Dpf\Controller;

/**
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

/**
 * ClientController
 */
class ClientController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * sysLanguageRepository
     *
     * @var \EWW\Dpf\Domain\Repository\SysLanguageRepository
     * @inject
     */
    protected $sysLanguageRepository = null;

    /**
     * clientRepository
     *
     * @var \EWW\Dpf\Domain\Repository\ClientRepository
     * @inject
     */
    protected $clientRepository = null;

    /**
     * InputOptionListRepository
     *
     * @var \EWW\Dpf\Domain\Repository\InputOptionListRepository
     * @inject
     */
    protected $inputOptionListRepository = null;

    /**
     * persistence manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     * @inject
     */
    protected $persistenceManager;

    // TypoScript settings
    protected $settings = array();

    // Id of the selected page in the page tree
    protected $selectedPageUid;

    // Page information of selected page
    protected $pageInfo;

    protected function initializeAction()
    {

        $this->selectedPageUid = (int) \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id');

        $this->pageInfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->selectedPageUid, $GLOBALS['BE_USER']->getPagePermsClause(1));

        $configManager = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\BackendConfigurationManager');

        $this->settings = $configManager->getConfiguration(
            $this->request->getControllerExtensionName(),
            $this->request->getPluginName()
        );
    }

    protected function getPageInfo($pageUid)
    {
        return \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($pageUid, $GLOBALS['BE_USER']->getPagePermsClause(1));
    }

    protected function initializeView($view)
    {
        parent::initializeView($view);

    }

    /**
     * start action
     *
     * @param \EWW\Dpf\Domain\Model\Client $newClient
     */
    public function newAction(\EWW\Dpf\Domain\Model\Client $newClient = null)
    {
        if ($this->isValidClientFolder()) {

            $this->addFlashMessage(
                "",
                $messageTitle = 'Der ausgewählte Ordner enthält noch keine Mandanten-Konfiguration!',
                $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING,
                $storeInSession = true
            );

            $this->view->assign('isValidClientFolder', $this->isValidClientFolder());
            $this->view->assign('newClient', $newClient);
        }

    }

    /**
     * initializeClient action
     *
     * @param \EWW\Dpf\Domain\Model\Client $newClient
     */
    public function createAction(\EWW\Dpf\Domain\Model\Client $newClient)
    {

        if ($this->isValidClientFolder()) {
            $newClient->setPid($this->selectedPageUid);
            $this->clientRepository->add($newClient);

            $this->addBaseInputOptionLists($this->selectedPageUid);

            $this->addFlashMessage(
                "Mittels des Listen-Moduls können Sie nun die weitere Konfiguration durchführen.",
                $messageTitle = 'Der QUCOSA-Client wurde erfolgreich angelegt!',
                $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK,
                $storeInSession = true
            );
            $this->redirect('default');
        }

        $this->redirect('new');
    }

    /**
     * default action
     *
     */
    public function defaultAction()
    {

    }

    protected function isValidClientFolder()
    {

        if (!$this->selectedPageUid) {
            $this->addFlashMessage(
                "Bitte wählen Sie im Seitenbaum einen Systemordner aus, der als QUCOSA-Client initialisiert werden soll.",
                $messageTitle = 'Bitte wählen Sie einen Zielordner aus!',
                $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::INFO,
                $storeInSession = true
            );
            return false;
        } else {

            // check if the selected page already contains a QUCOSA-Client or if it is a subpage of a Client.
            $client = $this->clientRepository->findAllByPid($this->selectedPageUid)->current();

            $pageInfo = $this->getPageInfo($this->selectedPageUid);
            while ($pageInfo['uid'] != 0 && !$client) {
                $client   = $this->clientRepository->findAllByPid($pageInfo['uid'])->current();
                $pageInfo = $this->getPageInfo($pageInfo['pid']);
            }

            if ($client) {
                $this->addFlashMessage(
                    "Dieser Ordner ist bereits Bestandteil eines initialisierten QUCOSA-Clients.",
                    $messageTitle = 'Eine Initialisierung ist hier leider nicht möglich!',
                    $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR,
                    $storeInSession = true
                );
                return false;
            }

            if ($this->pageInfo['doktype'] != 254) {
                $this->addFlashMessage(
                    "Bitte wählen Sie einen Systemordner aus, nur diese können als QUCOSA-Client verwendet werden.",
                    $messageTitle = 'Eine Initialisierung ist hier leider nicht möglich!',
                    $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR,
                    $storeInSession = true
                );
                return false;
            }
        }

        return true;
    }

    /**
     * adds all default input options
     *
     * @param integer $storagePid
     */
    protected function addBaseInputOptionLists($storagePid)
    {

        $iso6392b = $this->objectManager->get('EWW\\Dpf\\Configuration\\InputOption\\Iso6392b');

        $inputOptionTranslator = $this->objectManager->get('EWW\\Dpf\\Helper\\InputOption\\Translator');
        $inputOptionTranslator->init(get_class($iso6392b));

        // create input option list for the default language
        $languageOptionList = $this->objectManager->get('EWW\\Dpf\\Domain\\Model\\InputOptionList');
        $languageOptionList->setName('languageList');
        $languageOptionList->setPid($storagePid);
        $languageOptionList->setSysLanguageUid(0);
        $this->inputOptionListRepository->add($languageOptionList);

        $languageOptionList->setValueList($iso6392b->getValuesString());

        if ($inputOptionTranslator->hasTranslation($inputOptionTranslator->getDefaultLanguage())) {
            $valueLabelList = $inputOptionTranslator->translate($iso6392b->getValues());
            $displayName    = $inputOptionTranslator->translate(array('languageList'));
        } else {
            $valueLabelList = $inputOptionTranslator->translate($iso6392b->getValues(), 'en');
            $displayName    = $inputOptionTranslator->translate(array('languageList'), 'en');
        }
        $languageOptionList->setDisplayName(implode('', $displayName));
        $languageOptionList->setValueLabelList(implode('|', $valueLabelList));

        $this->persistenceManager->persistAll();

        // create input option for all other languages
        $installedlanguages = $this->sysLanguageRepository->findInstalledLanguages();
        foreach ($installedlanguages as $installedLanguage) {
            $langIsoCode = $installedLanguage->getLangIsocode();
            if (!empty($langIsoCode)) {
                // only when an iso code has been configured, a translation dataset is created
                if ($inputOptionTranslator->hasTranslation($langIsoCode)) {
                    // only when a translation exists, a translation dataset is created
                    $valueLabelList = $inputOptionTranslator->translate($iso6392b->getValues(), $langIsoCode);
                    $displayName    = $inputOptionTranslator->translate(array('languageList'), $langIsoCode);

                    $translatedOptionList = $this->objectManager->get('EWW\\Dpf\\Domain\\Model\\InputOptionList');
                    $translatedOptionList->setDisplayName(implode('', $displayName));
                    $translatedOptionList->setPid($storagePid);
                    $translatedOptionList->setSysLanguageUid($installedLanguage->getUid());
                    $translatedOptionList->setL10nParent($languageOptionList->getUid());
                    $translatedOptionList->setValueLabelList(implode('|', $valueLabelList));
                    $this->inputOptionListRepository->add($translatedOptionList);

                }

            }
        }

    }

}
