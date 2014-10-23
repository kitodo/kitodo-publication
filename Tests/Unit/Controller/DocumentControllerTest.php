<?php
namespace EWW\Dpf\Tests\Unit\Controller;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
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
 * Test case for class EWW\Dpf\Controller\DocumentController.
 *
 */
class DocumentControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \EWW\Dpf\Controller\DocumentController
	 */
	protected $subject = NULL;

	protected function setUp() {
		$this->subject = $this->getMock('EWW\\Dpf\\Controller\\DocumentController', array('redirect', 'forward', 'addFlashMessage'), array(), '', FALSE);
	}

	protected function tearDown() {
		unset($this->subject);
	}

	/**
	 * @test
	 */
	public function listActionFetchesAllDocumentsFromRepositoryAndAssignsThemToView() {

		$allDocuments = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage', array(), array(), '', FALSE);

		$documentRepository = $this->getMock('EWW\\Dpf\\Domain\\Repository\\DocumentRepository', array('findAll'), array(), '', FALSE);
		$documentRepository->expects($this->once())->method('findAll')->will($this->returnValue($allDocuments));
		$this->inject($this->subject, 'documentRepository', $documentRepository);

		$view = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\View\\ViewInterface');
		$view->expects($this->once())->method('assign')->with('documents', $allDocuments);
		$this->inject($this->subject, 'view', $view);

		$this->subject->listAction();
	}

	/**
	 * @test
	 */
	public function showActionAssignsTheGivenDocumentToView() {
		$document = new \EWW\Dpf\Domain\Model\Document();

		$view = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\View\\ViewInterface');
		$this->inject($this->subject, 'view', $view);
		$view->expects($this->once())->method('assign')->with('document', $document);

		$this->subject->showAction($document);
	}

	/**
	 * @test
	 */
	public function newActionAssignsTheGivenDocumentToView() {
		$document = new \EWW\Dpf\Domain\Model\Document();

		$view = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\View\\ViewInterface');
		$view->expects($this->once())->method('assign')->with('newDocument', $document);
		$this->inject($this->subject, 'view', $view);

		$this->subject->newAction($document);
	}

	/**
	 * @test
	 */
	public function createActionAddsTheGivenDocumentToDocumentRepository() {
		$document = new \EWW\Dpf\Domain\Model\Document();

		$documentRepository = $this->getMock('EWW\\Dpf\\Domain\\Repository\\DocumentRepository', array('add'), array(), '', FALSE);
		$documentRepository->expects($this->once())->method('add')->with($document);
		$this->inject($this->subject, 'documentRepository', $documentRepository);

		$this->subject->createAction($document);
	}

	/**
	 * @test
	 */
	public function editActionAssignsTheGivenDocumentToView() {
		$document = new \EWW\Dpf\Domain\Model\Document();

		$view = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\View\\ViewInterface');
		$this->inject($this->subject, 'view', $view);
		$view->expects($this->once())->method('assign')->with('document', $document);

		$this->subject->editAction($document);
	}

	/**
	 * @test
	 */
	public function updateActionUpdatesTheGivenDocumentInDocumentRepository() {
		$document = new \EWW\Dpf\Domain\Model\Document();

		$documentRepository = $this->getMock('EWW\\Dpf\\Domain\\Repository\\DocumentRepository', array('update'), array(), '', FALSE);
		$documentRepository->expects($this->once())->method('update')->with($document);
		$this->inject($this->subject, 'documentRepository', $documentRepository);

		$this->subject->updateAction($document);
	}

	/**
	 * @test
	 */
	public function deleteActionRemovesTheGivenDocumentFromDocumentRepository() {
		$document = new \EWW\Dpf\Domain\Model\Document();

		$documentRepository = $this->getMock('EWW\\Dpf\\Domain\\Repository\\DocumentRepository', array('remove'), array(), '', FALSE);
		$documentRepository->expects($this->once())->method('remove')->with($document);
		$this->inject($this->subject, 'documentRepository', $documentRepository);

		$this->subject->deleteAction($document);
	}
}
