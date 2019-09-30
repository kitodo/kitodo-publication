<?php
namespace EWW\Dpf\Security;

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
use TYPO3\CMS\Extbase\Object\ObjectManager;

abstract class Voter
{
    /**
     * security
     *
     * @var \EWW\Dpf\Security\Security
     * @inject
     */
    protected $security = null;

    /**
     * supported attributes
     *
     * @var array
     */
    protected $attributes = array();


    /**
     * Determines if the voter supports the given attribute.
     *
     * @param string $attribute
     * @param mixed $subject
     * @return mixed
     */
    abstract public static function supports($attribute, $subject);


    /**
     * Determines if access for the given attribute and subject is allowed.
     *
     * @param string $attribute
     * @param mixed $subject
     * @return mixed
     */
    abstract public function voteOnAttribute($attribute, $subject = NULL);


    /**
     * Returns all available voters.
     *
     * @return array
     */
    public static function getVoters()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $voters[] = $objectManager->get(\EWW\Dpf\Security\DocumentVoter::class);
        //$voters[] = $objectManager->get(\EWW\Dpf\Security\DocumentFormBackofficeVoter::class);
        //$voters[] = $objectManager->get(\EWW\Dpf\Security\SearchVoter::class);

        return $voters;
    }

}