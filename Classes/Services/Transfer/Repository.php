<?php
namespace EWW\Dpf\Services\Transfer;

interface Repository {
   
  public function ingest($document, $metsXml);
   
  public function update($document, $metsXml);
  
  public function retrieve($id);
   
  public function delete($id);
  
}

?>
