<?php
namespace EWW\Dpf\Services\Transfer;

class Response {  
  
  protected $httpStatus = 0;
  
  protected $response;
  
  protected $errorMessage;
  
  protected $curlError;
  
  function setHttpStatus($httpStatus) {
    $this->httpStatus = $httpStatus;     
  }
	
  function getHttpStatus() {
    return $this->http_status;
  }

  function setResponse($response) {
    $this->response = $response; 
  }

  function getResponse() {
    return $this->response;
  }

  function setErrorMessage($error_message) {
    $this->error_message = $error_message; 
  }

  function getErrorMessage() {
    return $this->error_message;
  }

  function setCurlError() {
    $this->curlError = TRUE; 
  }

  function getCurlError() {
    return $this->curlError;
  }

  function isSuccess() {
    return $this->getHttpStatus() == 201 && !$this->getCurlError();
  }
  
  
}


?>
