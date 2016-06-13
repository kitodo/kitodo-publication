<?php
namespace EWW\Dpf\Services\Transfer;

interface Repository
{

    public function ingest($document, $metsXml);

    public function update($document, $metsXml);

    public function retrieve($document);

    public function delete($document, $state);

    public function getNextDocumentId();

}
