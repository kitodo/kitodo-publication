<?php

namespace EWW\Dpf\Services\FeUser;

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

use EWW\Dpf\Configuration\Settings;

abstract class AbstractDataService
{
    /**
     * @var string|null
     */
    protected $apiUrl;

    protected function getApiUrl()
    {
        if (!$this->apiUrl) {
            $settings = new Settings();
            $apiUrl = $settings->getSettingByName(lcfirst((new \ReflectionClass($this))->getShortName()).'Url');
            if (is_string($apiUrl)) {
                $this->apiUrl = trim($apiUrl, '/');
            }
        }

        return $this->apiUrl;
    }
}
