<?php
namespace EWW\Dpf\Services\FeUser;

use \Httpful\Request;

class GndDataService
{

    protected $apiUrl = 'http://lobid.org/gnd/';

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

    public function searchPersonRequest($searchTerm) {
        $response = Request::get($this->apiUrl . 'search?filter=type:Person&format=json&q=' . $this->searchTermReplacement($searchTerm))
            ->send();

        return ['entries' => $response->body->member];
    }

    public function getPersonData($gndId) {
        $response = Request::get($this->apiUrl . $gndId . '.json')
            ->send();

        return $response->body;
    }

    public function searchOrganisationRequest($searchTerm) {
        $response = Request::get($this->apiUrl . 'search?filter=type:CorporateBody&format=json&q=' . $this->searchTermReplacement($searchTerm))
            ->send();

        return ['entries' => $response->body->member];
    }

    public function getOrganisationData($gndId) {
        $response = Request::get($this->apiUrl . $gndId . '.json')
            ->send();

        return $response->body;
    }

    public function searchKeywordRequest($searchTerm) {
        $response = Request::get($this->apiUrl . 'search?filter=type:SubjectHeading&format=json:suggest&size=100&q=' . $this->searchTermReplacement($searchTerm))
            ->send();

        return $response->body;
    }

}
