<?php
namespace EWW\Dpf\Services\FeUser;

use Httpful\Request;

class RorDataService
{
    public function searchTermReplacement($searchTerm) {
        return urlencode($searchTerm);
    }

    public function searchOrganisationRequest($searchTerm) {
        $response = Request::get($this->getApiUrl() . '?query=' . $this->searchTermReplacement($searchTerm))
            ->send();

        return ['entries' => $response->body->items];
    }

    public function getOrganisationData($rorId) {
        $response = Request::get($this->getApiUrl() . '/' . $rorId)
            ->send();

        return $response->body;
    }

}
