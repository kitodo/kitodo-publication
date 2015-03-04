<?php
namespace EWW\Dpf\Services\Transfer;

interface Repository {
   
  public function ingest($data);
   
  public function update($data);
  
  public function retrieve($id);
   
  public function delete($id);
  
}

?>
