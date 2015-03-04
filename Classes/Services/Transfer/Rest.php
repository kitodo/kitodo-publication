<?php
namespace EWW\Dpf\Services\Transfer;

class Rest {
   
  protected $baseUrl;
  
  protected $user;
  
  protected $password;
  
  protected $curlHandle;
  
  
  public function __construct($baseUrl = NULL, $user = NULL, $password = NULL) {
  
    $this->baseUrl = $baseUrl;
    
    $this->user = $user;
    
    $this->password = $password;
      
  } 
  
  
  public function __destruct() {
  
    if ($this->curlHandle) {
      curl_close($this->curlHandle);
    }  
     
  }
 
  
  public function init() {   
    
    if ($this->curlHandle) {
      curl_close($this->curlHandle);
    }  
    
    $this->curlHandle = curl_init($this->baseUrl); 

    curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, TRUE);
    
    if(!empty($this->user) && !empty($this->password)) {
      curl_setopt($this->curlHandle, CURLOPT_USERPWD, $this->user . ":" . $this->password);                 
    }
    
    //curl_setopt($this->curlHandle, CURLOPT_HEADER, TRUE);
  }    
  
  
  public function post($data = NULL, $header = NULL, $path = "") {
  
    $this->init();
    
    curl_setopt($this->curlHandle, CURLOPT_HTTPHEADER, $header);    
    curl_setopt($this->curlHandle, CURLOPT_URL, $this->baseUrl . $path);           
    curl_setopt($this->curlHandle, CURLOPT_POST, TRUE);
    curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, http_build_query($data));    
    
    
    
    //$header = array('Content-Type: multipart/form-data');
    //$fields = array('file' => '@' . $_FILES['file']['tmp_name'][0]);   
     
  }

  
  public function put($data = NULL, $header = NULL, $path = "") {

    $this->init();
    
    curl_setopt($this->curlHandle, CURLOPT_HTTPHEADER, $header);    
    curl_setopt($this->curlHandle, CURLOPT_URL, $this->baseUrl . $path);               
    curl_setopt($this->curlHandle, CURLOPT_PUT, TRUE);
    curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, http_build_query($data));        
    
  }
  
  
  public function get($data = NULL, $header = NULL, $path = "") {
        
    $this->init();
    
    curl_setopt($this->curlHandle, CURLOPT_HTTPHEADER, $header);    
    curl_setopt($this->curlHandle, CURLOPT_URL, $this->baseUrl . $path);           
    curl_setopt($this->curlHandle, CURLOPT_HTTPGET, TRUE);
       
  }
  
  
  public function delete($data = NULL, $header = NULL, $path = "") {
    
    $this->init();
    
    curl_setopt($this->curlHandle, CURLOPT_HTTPHEADER, $header);    
    curl_setopt($this->curlHandle, CURLOPT_URL, $this->baseUrl . $path);           
    curl_setopt($this->curlHandle, CURLOPT_DELETE, TRUE);
    curl_setopt($this->curlHandle, CURLOPT_POSTFIELDS, http_build_query($data));       
      
  }
  
  
  public function send() {
  
    $curlResult = curl_exec($this->curlHandle);
     
    $response = new Response();
    
    if ($curlResult === false) {
      $response->setCurlError();		
      $response->setErrorMessage(curl_error($this->curlHandle));						
    } else {
      $httpStatus = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);			
      $response->setHttpStatus($httpStatus); 					
      $response->setResponse($curlResult); 
    }
        
    return $response;
    
  }
  
}


?>
