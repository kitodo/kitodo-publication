<?php
namespace EWW\Dpf\Services\FeUser;

use \Httpful\Request;

class ZdbDataService
{

    protected $apiUrl = 'https://www.zeitschriftendatenbank.de/api/hydra/';

    public function __construct() {

    }

    public function searchRequest($searchTerm) {
        $response = Request::get($this->apiUrl . '?q=' . $searchTerm)
            ->send();

        return ['entries' => $response->body->member];
    }

    public function getDataRequest($zdbId) {
        $response = Request::get($this->apiUrl . 'resource/' . $zdbId .'/')
            ->send();
        
        return $response->body;
    }

}