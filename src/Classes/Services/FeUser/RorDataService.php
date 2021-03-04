<?php
namespace EWW\Dpf\Services\FeUser;

use \Httpful\Request;

class RorDataService
{

    protected $apiUrl = 'https://api.ror.org/organizations';

    public function __construct() {

    }

    public function searchTermReplacement($searchTerm) {
        return urlencode($searchTerm);
    }

    public function searchOrganisationRequest($searchTerm) {
        $response = Request::get($this->apiUrl . '?query=' . $this->searchTermReplacement($searchTerm))
            ->send();

        return ['entries' => $response->body->items];
    }

    public function getOrganisationData($rorId) {
        $response = Request::get($this->apiUrl . '/' . $rorId)
            ->send();

        return $response->body;
    }

}