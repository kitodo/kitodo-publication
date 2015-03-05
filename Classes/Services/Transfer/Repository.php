<?php
namespace EWW\Dpf\Services\Transfer;

interface Repository {
   
  public function ingest($document);
   
  public function update($document);
  
  public function retrieve($id);
   
  public function delete($id);
  
}

?>
