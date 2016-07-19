<?php
namespace EWW\Dpf\Helper\InputOption;

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

class Translator
{

    /**
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     * @inject
     */
    protected $objectManager;

    /**
     * sysLanguageRepository
     *
     * @var \EWW\Dpf\Domain\Repository\SysLanguageRepository
     * @inject
     */
    protected $sysLanguageRepository = null;

    /**
     * defaultLanguage
     *
     * @var string
     */
    protected $defaultLanguage;

    /**
     * locallang
     *
     * @param array | \DomDocument
     */
    protected $locallang = array();

    /**
     * initialization
     *
     * @param string $inputOptionClass
     */
    public function init($inputOptionClass)
    {
        $this->defaultLanguage = $this->getDefaultLanguage();

        $languages = $this->sysLanguageRepository->findInstalledLanguages();

        // load translation data for the default language
        $local = \EWW\Dpf\Helper\InputOption\Locallang::load($inputOptionClass, $this->defaultLanguage);
        if ($local) {
            $this->locallang[$this->defaultLanguage] = $local;
        }

        // load translation data for all other languages
        foreach ($languages as $language) {
            $langIsoCode = $language->getLangIsocode();

            if (!empty($langIsoCode)) {
                $local = \EWW\Dpf\Helper\InputOption\Locallang::load($inputOptionClass, $langIsoCode);
                if ($local) {
                    $this->locallang[$language->getLangIsocode()] = $local;
                }
            }
        }

    }

    /**
     * Returns the default language
     *
     * @return string
     */
    public function getDefaultLanguage()
    {
        $configurationManager = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
        $extbaseConfiguration = $configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);

        $defaultLanguage = $extbaseConfiguration['config.']['language'];

        if (empty($defaultLanguage)) {
            return 'en';
        }

        return $defaultLanguage;
    }

    /**
     * returns a translation for the keys into the desired language
     *
     * @param string $keys
     * @param string $language
     */
    public function translate($keys, $language = null)
    {

        $lang = (empty($language)) ? $this->getDefaultLanguage() : $language;

        if ($this->hasTranslation($lang)) {
            foreach ($keys as $key) {
                $result[$key] = $this->locallang[$lang]->findTranslationByKey($key);
            }

            return $result;
        }

        return $keys;

    }

    /**
     * checks if a translation for the specified language exists
     *
     * @param string $language
     */
    public function hasTranslation($language)
    {
        return array_key_exists($language, $this->locallang);
    }

}
