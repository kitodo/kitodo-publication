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

class GetDepositLicenseTextViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * documentTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DepositLicenseRepository
     * @inject
     */
    protected $depositLicenseRepository = null;

    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument('uri', 'string', '', true);
    }

    /**
     * Gets the deposit license text for the given uri/urn
     *
     * @return string
     */
    public function render()
    {
        $uri = $this->arguments['uri'];

        /** @var \EWW\Dpf\Domain\Model\DepositLicense $depositLicense */
        $depositLicense = $this->depositLicenseRepository->findOneByUri($uri);

        if ($depositLicense) {
            return $depositLicense->getText();
        }

        return "";
    }
}
