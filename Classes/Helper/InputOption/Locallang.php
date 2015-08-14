<?php
namespace EWW\Dpf\Helper\InputOption;

class Locallang {
    
    
    /**
     * inputOptionClass
     * 
     * @var string
     */
    protected $inputOptionClass = ''; 
    
    
    /**
     * language
     * 
     * @var string
     */        
    protected $language = '';
    
    
    /**
     * xpath
     * 
     * @var \DOMXpath
     */
    protected $xpath = NULL;
       
    
    /**
     * loallangFilePath
     * 
     * @var string
     */
    protected $locallangFilePath = '';

    
    /**
     * sets the xpath
     *     
     * @param \DOMXpath $xpath
     */
    protected function setXpath($xpath){
       $this->xpath = $xpath; 
       // $this->inputOptionClass = $inputOptionClass;
       // $this->language = $language;        
       // $this->loadConfiguration($inputOptionClass, $language);
    } 
           
    
    protected function setLanguage($language) {
        $this->language = $language;        
    }
    

    protected function setLocallangFilePath($locallangFilePath) {
        $this->locallangFilePath = $locallangFilePath;        
    }

    
    /**
     * Returns the locallang configuration for the specified input option
     * and language. If no language was specified, english is used by default.    
     * 
     * @param string $inputOptionClass
     * @param string $language
     * 
     * @return \EWW\Dpf\Helper\InputOption\Locallang
     */    
    static public function load($inputOptionClass, $language = 'en') {
        
        $language = (empty($language))? 'en' : $language;  
        
        $inputOptionClass = explode('\\',$inputOptionClass);
        $inputOptionClass = $inputOptionClass[sizeof($inputOptionClass)-1];
        
        $languagePath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('dpf');     
        $languagePath .= "Resources/Private/Language";        
        
        $langPart = ($language == 'en')? "" : $language.'.';           
        $locallangFilePath = "$languagePath/".$langPart."locallang_configuration_".$inputOptionClass.".xlf";     
        
        if (file_exists($locallangFilePath)) {                
            $dom = new \DomDocument();                                  
            $dom->load($locallangFilePath);                       
            $newLocallang = new \EWW\Dpf\Helper\InputOption\Locallang();                        
            $newLocallang->setXpath(new \DOMXpath($dom));
            $newLocallang->setLocallangFilePath($locallangFilePath);
            $newLocallang->setLanguage($language);
            
            return $newLocallang;
        }               
        
        return NULL;
                                                                   
    }

    /**
     * returns the translation entry of the specified key
     * 
     * @param string $key
     */
    public function findTranslationByKey($key) {        
        if ($this->xpath) {            
            $elements = $this->xpath->query("//trans-unit[@id='".$key."']");
            if (!is_null($elements) &&  $elements->length > 0 ) {    
                return trim($elements->item(0)->nodeValue);
            }
        }
        
        return trim($key);        
    }
                        
} 


?>