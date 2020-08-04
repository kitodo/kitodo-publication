<?php
namespace EWW\Dpf\Services\FeUser;

use \Httpful\Request;

class GndDataService
{

    protected $apiUrl = 'http://lobid.org/gnd/';

    public function __construct() {

    }

    public function searchPersonRequest($searchTerm) {
        $response = Request::get($this->apiUrl . 'search?filter=type:Person&format=json&q=' . $searchTerm)
            ->send();

        return ['entries' => $response->body->member];
    }

    public function getPersonData($gndId) {
        $response = Request::get($this->apiUrl . $gndId . '.json')
            ->send();

        return $response->body;
    }

    public function searchOrganisationRequest($searchTerm) {
        $response = Request::get($this->apiUrl . 'search?filter=type:CorporateBody&format=json&q=' . $searchTerm)
            ->send();

        return ['entries' => $response->body->member];
    }

    public function getOrganisationData($gndId) {
        $response = Request::get($this->apiUrl . $gndId . '.json')
            ->send();

        return $response->body;
    }

}