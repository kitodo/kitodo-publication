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
 * Test case for class EWW\Dpf\Controller\DocumentsController.
 *
 */
class DocumentsControllerTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \EWW\Dpf\Controller\DocumentsController
	 */
	protected $subject = NULL;

	protected function setUp() {
		$this->subject = $this->getMock('EWW\\Dpf\\Controller\\DocumentsController', array('redirect', 'forward', 'addFlashMessage'), array(), '', FALSE);
	}

	protected function tearDown() {
		unset($this->subject);
	}

	/**
	 * @test
	 */
	public function listActionFetchesAllDocumentssFromRepositoryAndAssignsThemToView() {

		$allDocumentss = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage', array(), array(), '', FALSE);

		$documentsRepository = $this->getMock('EWW\\Dpf\\Domain\\Repository\\DocumentsRepository', array('findAll'), array(), '', FALSE);
		$documentsRepository->expects($this->once())->method('findAll')->will($this->returnValue($allDocumentss));
		$this->inject($this->subject, 'documentsRepository', $documentsRepository);

		$view = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\View\\ViewInterface');
		$view->expects($this->once())->method('assign')->with('documentss', $allDocumentss);
		$this->inject($this->subject, 'view', $view);

		$this->subject->listAction();
	}

	/**
	 * @test
	 */
	public function showActionAssignsTheGivenDocumentsToView() {
		$documents = new \EWW\Dpf\Domain\Model\Documents();

		$view = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\View\\ViewInterface');
		$this->inject($this->subject, 'view', $view);
		$view->expects($this->once())->method('assign')->with('documents', $documents);

		$this->subject->showAction($documents);
	}

	/**
	 * @test
	 */
	public function newActionAssignsTheGivenDocumentsToView() {
		$documents = new \EWW\Dpf\Domain\Model\Documents();

		$view = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\View\\ViewInterface');
		$view->expects($this->once())->method('assign')->with('newDocuments', $documents);
		$this->inject($this->subject, 'view', $view);

		$this->subject->newAction($documents);
	}

	/**
	 * @test
	 */
	public function createActionAddsTheGivenDocumentsToDocumentsRepository() {
		$documents = new \EWW\Dpf\Domain\Model\Documents();

		$documentsRepository = $this->getMock('EWW\\Dpf\\Domain\\Repository\\DocumentsRepository', array('add'), array(), '', FALSE);
		$documentsRepository->expects($this->once())->method('add')->with($documents);
		$this->inject($this->subject, 'documentsRepository', $documentsRepository);

		$this->subject->createAction($documents);
	}

	/**
	 * @test
	 */
	public function editActionAssignsTheGivenDocumentsToView() {
		$documents = new \EWW\Dpf\Domain\Model\Documents();

		$view = $this->getMock('TYPO3\\CMS\\Extbase\\Mvc\\View\\ViewInterface');
		$this->inject($this->subject, 'view', $view);
		$view->expects($this->once())->method('assign')->with('documents', $documents);

		$this->subject->editAction($documents);
	}

	/**
	 * @test
	 */
	public function updateActionUpdatesTheGivenDocumentsInDocumentsRepository() {
		$documents = new \EWW\Dpf\Domain\Model\Documents();

		$documentsRepository = $this->getMock('EWW\\Dpf\\Domain\\Repository\\DocumentsRepository', array('update'), array(), '', FALSE);
		$documentsRepository->expects($this->once())->method('update')->with($documents);
		$this->inject($this->subject, 'documentsRepository', $documentsRepository);

		$this->subject->updateAction($documents);
	}

	/**
	 * @test
	 */
	public function deleteActionRemovesTheGivenDocumentsFromDocumentsRepository() {
		$documents = new \EWW\Dpf\Domain\Model\Documents();

		$documentsRepository = $this->getMock('EWW\\Dpf\\Domain\\Repository\\DocumentsRepository', array('remove'), array(), '', FALSE);
		$documentsRepository->expects($this->once())->method('remove')->with($documents);
		$this->inject($this->subject, 'documentsRepository', $documentsRepository);

		$this->subject->deleteAction($documents);
	}
}
