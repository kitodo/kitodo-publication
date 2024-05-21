<?php
namespace EWW\Dpf\Services\FeUser;

use Httpful\Request;

class ZdbDataService extends AbstractDataService
{
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
        $response = Request::get($this->getApiUrl() . '?q=' . $this->searchTermReplacement($searchTerm))
            ->send();

        return ['entries' => $response->body->member];
    }

    public function getDataRequest($zdbId) {
        $response = Request::get($this->getApiUrl() . '/resource/' . $zdbId .'/')
            ->send();

        return $response->body;
    }

}
