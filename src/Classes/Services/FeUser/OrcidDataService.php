<?php
namespace EWW\Dpf\Services\FeUser;

use \Httpful\Request;

class OrcidDataService
{
    protected $apiUrl = 'https://pub.orcid.org/v3.0';

    protected $params = '&start=0&rows=20';

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
        $response = Request::get($this->apiUrl . '/expanded-search/?q=' . $this->searchTermReplacement($searchTerm))
            ->expectsJson()
            ->addHeader('Accept','*/*')
            ->addHeader('Content-Type', 'application/vnd.orcid+json')
            ->send();

        return ['entries' => $response->body->{'expanded-result'}];
    }

    public function getPersonData($orcidId) {
        $response = Request::get($this->apiUrl . '/expanded-search/?q=orcid:' . $orcidId)
            ->expectsJson()
            ->addHeader('Accept','*/*')
            ->addHeader('Content-Type', 'application/vnd.orcid+json')
            ->send();
var_dump($response->body->{'expanded-result'}[0]);
        return $response->body->{'expanded-result'}[0];
    }

}