<?php
namespace EWW\Dpf\ViewHelpers;

class FileUrlViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

  
        /**       
         *
         * @param string $uri 
         * 
         */
        public function render($uri) {                               
               return $this->buildFileUri($uri);
        }
           
        protected function buildFileUri($uri) {
            
            $baseURL = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';    
            $port = '';    
            if ($_SERVER['SERVER_PORT'] && intval($_SERVER['SERVER_PORT']) != 80) {
                $port = ':'.$_SERVER['SERVER_PORT'];
            }         
            $baseURL .= trim($_SERVER['SERVER_NAME'],"/").$port;
            
            
            $regex = '/\/(\w*:\d*)\/datastreams\/(\w*-\d*)/';
            preg_match($regex, $uri, $treffer);

            if (!empty($treffer)) {
                $qucosa = explode(":", $treffer[1]);
                $namespace = $qucosa[0];
                $qid = $qucosa[1];
                $fid = $treffer[2];                            
                return $baseURL.'/get/file/'.$namespace.'/'.$qid.'/'.$fid;
            }
            
            return $uri;
        }
    
}

?>
