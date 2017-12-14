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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper;

class DataCiteViewHelper extends AbstractBackendViewHelper
{

    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @inject
     */
    protected $documentRepository;

    /**
     * Returns the View Icon with link
     * @param  integer $viewPage Detail View page id
     * @param  integer $apiPid
     * @param  string $insideText
     * @param  string $class
     * @return string html output
     */
    protected function getViewIcon($row, $pageUid, $apiPid, $insideText, $class)
    {

        // Build typolink configuration array.
        $conf = array(
            'useCacheHash'     => 0,
            'parameter'        => $apiPid,
            'additionalParams' => '&tx_dpf[qid]=' . $row['uid'] . '&tx_dpf[action]=' . $row['action'],
            'forceAbsoluteUrl' => true,
        );

        /** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObj */
        $cObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');

        // replace uid with URI to dpf API
        $dataCite = $cObj->typoLink_URL($conf);

        $title = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('manager.tooltip.datacite', 'dpf', $arguments = null);
        $icon  = '<a href="' . $dataCite . '" data-toggle="tooltip" class="' . $class . '" title="' . $title . '">' . $insideText . '</a>';

        return $icon;
    }

    /**
     * Renders a record list as known from the TYPO3 list module
     * Note: This feature is experimental!
     *
     * @param array() $arguments
     * @param  integer $pageUid
     * @param  integer $apiPid
     * @param  string $class
     * @return string the rendered record list
     */
    public function render(array $arguments, $pageUid, $apiPid, $class)
    {

        if ($arguments['document']) {

            // it's already a document object?
            if ($arguments['document'] instanceof \EWW\Dpf\Domain\Model\Document) {

                $document = $arguments['document'];

            } else if (MathUtility::canBeInterpretedAsInteger($arguments['document'])) {

                $document = $this->documentRepository->findByUid($arguments['document']);

            }

            // we found a valid document
            if ($document) {

                $row['uid'] = $document->getUid();

                $row['title'] = $document->getTitle();

                $row['action'] = 'dataCite';

            } else {

                // ok, nothing to render. So return empty content.
                return '';

            }

        } else if ($arguments['documentObjectIdentifier']) {

            $row['action'] = 'dataCite';

            $row['uid'] = $arguments['documentObjectIdentifier'];

        }

        $insideText = $this->renderChildren();
        $content = $this->getViewIcon($row, $pageUid, $apiPid, $insideText, $class);

        return $content;

    }
}
