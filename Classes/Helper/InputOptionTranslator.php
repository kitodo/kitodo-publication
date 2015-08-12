<?php
namespace EWW\Dpf\Helper;

class InputOptionTranslator {
    
       
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
    protected $sysLanguageRepository = NULL;
        
    
    /**
     *  optionName
     * 
     * @var string
     */
    protected $optionName;
    
    
    /**
     * defaultLanguage
     * 
     * @var string
     */
    protected $defaultLanguage;
    
    
    /**
     * languagePath
     * 
     * @param string
     */
    protected $languagePath;
    
        
    /**
     * 
     * @param array
     */
    protected locallang = array();
    
    
    public function init($optionName) {
        $this->optionName = $optionName;        
        $this->languagePath = $this->getLanguagePath();
        $this->defaultLanguage = $this->getDefaultLanguage();    
        
        $languages = $this->sysLanguageRepository->findInstalledLanguages();    
                
        $dom = new \DomDocument();  
        $dom->load("$this->languagePath/locallang_".$this->optionName.".xlf");   
        $locallang[$this->defaultLanguage] = $dom;
        
        foreach ($languages as $language) {           
            $langFile = "$this->languagePath/".$language->getFlag().".locallang_".$this->optionName.".xlf";            
            if (file_exists($langFile)) {
                $dom = new \DomDocument();  
                $dom->load($langFile);
                $locallang[$language->getFlag()] = $dom;              
            }
        }
        
    }
    
    
    /**
     * Returns the default language
     * 
     * @return string
     */
    protected function getDefaultLanguage() {
        $configurationManager = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');                             
        $extbaseConfiguration = $configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);                              
        
        $defaultLanguage = $extbaseConfiguration['config.']['language'];
        
        if (empty($defaultLanguage)) {
            return 'en'; 
        }
        
        return $defaultLanguage;
    } 
    
    
    /**
     * Returns the path to the language files
     * 
     * @return string
     */
    protected function getLanguagePath() {
        $languagePath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('dpf');     
        return $languagePath . "Resources/Private/Language";        
    }
    
    
    /**
     * returns a translation for the key into the desired language
     * 
     * @param string $optionName
     * @param string $key
     * @param string $language
     */
    public function translate($key, $language = NULL) {
                
        $lang = (empty($language))? $this->defaultLanguage : $language;            
        
        $dom = new \DomDocument();  
               
        if ($lang == 'en') {
            $dom->load("$this->languagePath/locallang_".$this->optionName.".xlf");    
        } else {
            $dom->load("$this->languagePath/".$lang.".locallang_".$this->optionName.".xlf");    
        }                                        
        
        $xpath = new \DOMXpath($dom);
        $elements = $xpath->query("//trans-unit[@id='".$key."']");
        if (!is_null($elements) &&  $elements->length > 0 ) {    
            $displayName = $elements->item(0)->nodeValue;
        } else {
            $displayName = $key;
        }   
        
        return trim($displayName);               
    }
            
}

?>
