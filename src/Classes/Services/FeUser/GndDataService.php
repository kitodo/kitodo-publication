<?php
namespace EWW\Dpf\Services\FeUser;

use \Httpful\Request;

class GndUserData
{

    protected $apiUrl = 'http://lobid.org/gnd/';

    public function __construct() {

    }

    public function searchRequest($searchTerm) {
        $response = Request::get($this->apiUrl . 'search?filter=type:Person&format=json&q=preferredName:' . $searchTerm)
            ->send();

        return ['entries' => $response->body->member];
    }

    public function getData($gndId) {
        $response = Request::get($this->apiUrl . $gndId . '.json')
            ->send();

        return $response->body;
    }

}