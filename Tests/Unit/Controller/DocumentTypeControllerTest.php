<?php
namespace EWW\Dpf\Tests\Unit\Controller;

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
 * Test case for class EWW\Dpf\Controller\DocumentTypeController.
 *
 */
class DocumentTypeControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \EWW\Dpf\Controller\DocumentTypeController
	 */
	protected $subject = NULL;

	protected function setUp() {
		$this->subject = $this->getMock('EWW\\Dpf\\Controller\\DocumentTypeController', array('redirect', 'forward', 'addFlashMessage'), array(), '', FALSE);
	}

	protected function tearDown() {
		unset($this->subject);
	}

	/**
	 * @test
	 */
	public function listActionFetchesAllDocumentTypesFromRepositoryAndAssignsThemToView() {

		$allDocumentTypes = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage', array(), array(), '', FALSE);

		$documentTypeRepository = $this->getMock('EWW\\Dpf\\Domain\\Repository\\DocumentTypeRepository', array('findAll'), array(), '', FALSE);
		$documentTypeRepository->expects($this->once())->method('findAll')->will($this->returnValue($allDocumentTypes));
		$this->inject($this->subject, 'documentTypeRepository', $documentTypeRepository);

		$view = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\View\\ViewInterface');
		$view->expects($this->once())->method('assign')->with('documentTypes', $allDocumentTypes);
		$this->inject($this->subject, 'view', $view);

		$this->subject->listAction();
	}

	/**
	 * @test
	 */
	public function showActionAssignsTheGivenDocumentTypeToView() {
		$documentType = new \EWW\Dpf\Domain\Model\DocumentType();

		$view = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\View\\ViewInterface');
		$this->inject($this->subject, 'view', $view);
		$view->expects($this->once())->method('assign')->with('documentType', $documentType);

		$this->subject->showAction($documentType);
	}

	/**
	 * @test
	 */
	public function newActionAssignsTheGivenDocumentTypeToView() {
		$documentType = new \EWW\Dpf\Domain\Model\DocumentType();

		$view = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\View\\ViewInterface');
		$view->expects($this->once())->method('assign')->with('newDocumentType', $documentType);
		$this->inject($this->subject, 'view', $view);

		$this->subject->newAction($documentType);
	}

	/**
	 * @test
	 */
	public function createActionAddsTheGivenDocumentTypeToDocumentTypeRepository() {
		$documentType = new \EWW\Dpf\Domain\Model\DocumentType();

		$documentTypeRepository = $this->getMock('EWW\\Dpf\\Domain\\Repository\\DocumentTypeRepository', array('add'), array(), '', FALSE);
		$documentTypeRepository->expects($this->once())->method('add')->with($documentType);
		$this->inject($this->subject, 'documentTypeRepository', $documentTypeRepository);

		$this->subject->createAction($documentType);
	}

	/**
	 * @test
	 */
	public function editActionAssignsTheGivenDocumentTypeToView() {
		$documentType = new \EWW\Dpf\Domain\Model\DocumentType();

		$view = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\View\\ViewInterface');
		$this->inject($this->subject, 'view', $view);
		$view->expects($this->once())->method('assign')->with('documentType', $documentType);

		$this->subject->editAction($documentType);
	}

	/**
	 * @test
	 */
	public function updateActionUpdatesTheGivenDocumentTypeInDocumentTypeRepository() {
		$documentType = new \EWW\Dpf\Domain\Model\DocumentType();

		$documentTypeRepository = $this->getMock('EWW\\Dpf\\Domain\\Repository\\DocumentTypeRepository', array('update'), array(), '', FALSE);
		$documentTypeRepository->expects($this->once())->method('update')->with($documentType);
		$this->inject($this->subject, 'documentTypeRepository', $documentTypeRepository);

		$this->subject->updateAction($documentType);
	}

	/**
	 * @test
	 */
	public function deleteActionRemovesTheGivenDocumentTypeFromDocumentTypeRepository() {
		$documentType = new \EWW\Dpf\Domain\Model\DocumentType();

		$documentTypeRepository = $this->getMock('EWW\\Dpf\\Domain\\Repository\\DocumentTypeRepository', array('remove'), array(), '', FALSE);
		$documentTypeRepository->expects($this->once())->method('remove')->with($documentType);
		$this->inject($this->subject, 'documentTypeRepository', $documentTypeRepository);

		$this->subject->deleteAction($documentType);
	}
}
