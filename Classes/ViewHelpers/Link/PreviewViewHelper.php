<?php
namespace EWW\Dpf\ViewHelpers\Link;

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

use EWW\Dpf\Security\PreviewToken;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class PreviewViewHelper extends AbstractViewHelper
{
    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $uriBuilder;

    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentRepository;

    /**
     * escapeOutput, activates / deactivates HTML escaping.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Returns the View Icon with link
     *
     * @param array $row Data row
     * @param integer $viewPage Detail View page id
     * @param  integer $apiPid
     * @param  string $insideText
     * @param  string $class
     * @return string html output
     */
    protected function getViewIcon(array $row, $pageUid, $apiPid, $insideText, $class)
    {
        $args = ['qid' => $row['qid']];
        if (!empty($row['action'])) {
            $args['action'] = $row['action'];
        }
        if (!empty($row['deliverInactive'])) {
            $args['deliverInactive'] = $row['deliverInactive'];
        }

        $landingPageUri = $this->uriBuilder
            ->reset()
            ->setTargetPageUid($pageUid)
            ->setArguments(['tx_dpf' => $args])
            ->setCreateAbsoluteUri(true)
            ->setUseCacheHash(true)
            ->buildFrontendUri();

        $title = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('manager.tooltip.preview', 'dpf', $arguments = null);
        $icon = '<a href="'. $landingPageUri . '" data-toggle="tooltip" class="' . $class . '" title="' . $title . '">' . $insideText . '</a>';

        return $icon;
    }

    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument('arguments', 'array', '', true);
        $this->registerArgument('pageUid', 'int', '', true);
        $this->registerArgument('apiPid', 'int', '', true);
        $this->registerArgument('class', 'string', '', true);
        $this->registerArgument('deliverInactive', 'string', 'Master secret — converted to HMAC token before embedding in URL', false, '');
    }

    /**
     * Renders a record list as known from the TYPO3 list module
     * Note: This feature is experimental!
     *
     * @return string the rendered record list
     */
    public function render()
    {
        $arguments = $this->arguments['arguments'];
        $pageUid = $this->arguments['pageUid'];
        $apiPid = $this->arguments['apiPid'];
        $class = $this->arguments['class'];
        $deliverInactive = $this->arguments['deliverInactive'];

        if ($arguments['document']) {

            // it's already a document object?
            if ($arguments['document'] instanceof \EWW\Dpf\Domain\Model\Document) {

                $document = $arguments['document'];

            } else if (MathUtility::canBeInterpretedAsInteger($arguments['document'])) {

                $document = $this->documentRepository->findByUid($arguments['document']);

            }

            // we found a valid document
            if ($document) {

                $row['qid'] = $document->getProcessNumber();

                $row['action'] = 'preview';

                if (!empty($deliverInactive)) {
                    $row['deliverInactive'] = PreviewToken::generate($row['qid'], $deliverInactive);
                }

            } else {

                // ok, nothing to render. So return empty content.
                return '';

            }

        } else if ($arguments['documentObjectIdentifier']) {

            $row['qid'] = $arguments['documentObjectIdentifier'];

        }

        $insideText = $this->renderChildren();

        $content = $this->getViewIcon($row, $pageUid, $apiPid, $insideText, $class);

        return $content;

    }
}
