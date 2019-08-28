<?php
/**
 * Created by PhpStorm.
 * User: hauke
 * Date: 28.08.19
 * Time: 12:49
 */

namespace EWW\Dpf\Security;

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
    abstract public function supports($attribute, $subject);


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
        $voters[] = $objectManager->get(\EWW\Dpf\Security\DocumentFormBackofficeVoter::class);
        $voters[] = $objectManager->get(\EWW\Dpf\Security\SearchVoter::class);

        return $voters;
    }

}