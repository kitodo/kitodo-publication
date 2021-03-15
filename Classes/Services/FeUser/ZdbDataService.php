<?php
namespace EWW\Dpf\Services\FeUser;

use \Httpful\Request;

class ZdbDataService
{

    protected $apiUrl = 'https://www.zeitschriftendatenbank.de/api/hydra/';

    public function __construct() {

    }

    public function searchTermReplacement($searchTerm) {
        $searchTerm = str_replace('ä', 'ae', $searchTerm);
        $searchTerm = str_replace('Ä', 'Ae', $searchTerm);
        $searchTerm = str_replace('ö', 'oe', $searchTerm);
        $searchTerm = str_replace('Ö', 'Oe', $searchTerm);
        $searchTerm = str_replace('ü', 'ue', $searchTerm);
        $searchTerm = str_replace('Ü', 'Ue', $searchTerm);
        return str_replace(' ', '+', $searchTerm);
    }

    public function searchRequest($searchTerm) {
        $response = Request::get($this->apiUrl . '?q=' . $this->searchTermReplacement($searchTerm))
            ->send();

        return ['entries' => $response->body->member];
    }

    public function getDataRequest($zdbId) {
        $response = Request::get($this->apiUrl . 'resource/' . $zdbId .'/')
            ->send();
        
        return $response->body;
    }

}