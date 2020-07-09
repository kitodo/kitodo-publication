<?php
namespace EWW\Dpf\Services\FeUser;

use \Httpful\Request;

class RorDataService
{

    protected $apiUrl = 'https://api.ror.org/organizations';

    public function __construct() {

    }

    public function searchRequest($searchTerm) {
        $response = Request::get($this->apiUrl . '?query=' . $searchTerm)
            ->send();

        return ['entries' => $response->body->items];
    }

    public function getDataRequest($rorId) {
        $response = Request::get($this->apiUrl . '/' . $rorId)
            ->send();

        return $response->body;
    }

}