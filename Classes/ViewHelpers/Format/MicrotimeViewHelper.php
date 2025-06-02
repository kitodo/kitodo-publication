<?php
namespace  EWW\Dpf\ViewHelpers\Format;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It originated from the Neos.Form package (www.neos.io)
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

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class MicrotimeViewHelper extends AbstractViewHelper
{
    public function initializeArguments()
    {
        $this->registerArgument('microtime', 'string', 'The microtime to format', true);
        $this->registerArgument('format', 'string', 'The format to use', false, 'd.m.Y H:i:s.u');
    }

    public function render()
    {
        $microtime = (float)$this->arguments['microtime'];
        $format = $this->arguments['format'];

        $seconds = floor($microtime);
        $microseconds = sprintf("%06d", ($microtime - $seconds) * 1000000);

        $dateTime = new \DateTime();
        $dateTime->setTimestamp($seconds);
        return $dateTime->format(str_replace('u', $microseconds, $format));
    }
}
