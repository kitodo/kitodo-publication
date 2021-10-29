<?php
namespace EWW\Dpf\ViewHelpers;

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

class GetDepositLicenseViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * documentTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DepositLicenseRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $depositLicenseRepository = null;

    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument('uri', 'string', '', true);
    }

    /**
     * @return bool
     */
    public function render()
    {
        $uri = $this->arguments['uri'];

        /** @var \EWW\Dpf\Domain\Model\DepositLicense $depositLicense */
        $depositLicense = $this->depositLicenseRepository->findOneByUri($uri);
        return $depositLicense;
    }

}
