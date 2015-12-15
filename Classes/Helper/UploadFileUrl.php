<?php
namespace EWW\Dpf\Helper;

class UploadFileUrl {
  
    
    public function getBaseUrl() {
    
        $confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dpf']);     
        $baseUrl =  trim($confArr['uploadDomain'], "/ ");   
    
        if (empty($baseUrl)) {    
            $protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === FALSE ? 'http://' : 'https://';
            $baseUrl = $protocol.$_SERVER['HTTP_HOST'];    
        }    
                           
        return $baseUrl;
    }
    
    public function getDirectory() {
    
        $confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dpf']);
    
        $uploadDirectory = trim($confArr['uploadDirectory'], "/ ");        
    
        $uploadDir = empty($uploadDirectory)? "uploads/tx_dpf" : $uploadDirectory;
                              
        return $uploadDir;
    }
    
    public function getUploadUrl() {
        return $this->getBaseUrl()."/".$this->getDirectory();
    }
  
}

?>
