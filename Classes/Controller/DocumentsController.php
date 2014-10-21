<?php
namespace EWW\Dpf\Controller;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2014
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * DocumentsController
 */
class DocumentsController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * documentsRepository
	 *
	 * @var \EWW\Dpf\Domain\Repository\DocumentsRepository
	 * @inject
	 */
	protected $documentsRepository = NULL;

	/**
	 * action list
	 *
	 * @return void
	 */
	public function listAction() {
		$documents = $this->documentsRepository->findAll();
		$this->view->assign('documents', $documents);
	}

	/**
	 * action show
	 *
	 * @param \EWW\Dpf\Domain\Model\Documents $documents
	 * @return void
	 */
	public function showAction(\EWW\Dpf\Domain\Model\Documents $documents) {
		$this->view->assign('documents', $documents);
	}

	/**
	 * action new
	 *
	 * @param \EWW\Dpf\Domain\Model\Documents $newDocuments
	 * @ignorevalidation $newDocuments
	 * @return void
	 */
	public function newAction(\EWW\Dpf\Domain\Model\Documents $newDocuments = NULL) {
		$this->view->assign('newDocuments', $newDocuments);
	}

	/**
	 * action create
	 *
	 * @param \EWW\Dpf\Domain\Model\Documents $newDocuments
	 * @return void
	 */
	public function createAction(\EWW\Dpf\Domain\Model\Documents $newDocuments) {
		$this->addFlashMessage('The object was created. Please be aware that this action is publicly accessible unless you implement an access check. See <a href="http://wiki.typo3.org/T3Doc/Extension_Builder/Using_the_Extension_Builder#1._Model_the_domain" target="_blank">Wiki</a>', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
		$this->documentsRepository->add($newDocuments);
		$this->redirect('list');
	}

	/**
	 * action edit
	 *
	 * @param \EWW\Dpf\Domain\Model\Documents $documents
	 * @ignorevalidation $documents
	 * @return void
	 */
	public function editAction(\EWW\Dpf\Domain\Model\Documents $documents) {
		$this->view->assign('documents', $documents);
	}

	/**
	 * action update
	 *
	 * @param \EWW\Dpf\Domain\Model\Documents $documents
	 * @return void
	 */
	public function updateAction(\EWW\Dpf\Domain\Model\Documents $documents) {
		$this->addFlashMessage('The object was updated. Please be aware that this action is publicly accessible unless you implement an access check. See <a href="http://wiki.typo3.org/T3Doc/Extension_Builder/Using_the_Extension_Builder#1._Model_the_domain" target="_blank">Wiki</a>', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
		$this->documentsRepository->update($documents);
		$this->redirect('list');
	}

	/**
	 * action delete
	 *
	 * @param \EWW\Dpf\Domain\Model\Documents $documents
	 * @return void
	 */
	public function deleteAction(\EWW\Dpf\Domain\Model\Documents $documents) {
		$this->addFlashMessage('The object was deleted. Please be aware that this action is publicly accessible unless you implement an access check. See <a href="http://wiki.typo3.org/T3Doc/Extension_Builder/Using_the_Extension_Builder#1._Model_the_domain" target="_blank">Wiki</a>', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
		$this->documentsRepository->remove($documents);
		$this->redirect('list');
	}

}