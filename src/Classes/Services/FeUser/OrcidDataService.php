<?php
namespace EWW\Dpf\Services\FeUser;

use \Httpful\Request;

class OrcidDataService
{
    protected $apiUrl = 'https://pub.orcid.org/v3.0';

    protected $params = '&start=0&rows=20';

    public function __construct() {

    }

    public function searchPersonRequest($searchTerm) {
        $response = Request::get($this->apiUrl . '/expanded-search/?q=' . $searchTerm)
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

        return $response->body->{'expanded-result'}[0];
    }

    public function searchOrganisationRequest($searchTerm) {
        $response = Request::get($this->apiUrl . '/expanded-search/?q=affiliation-org-name:' . $searchTerm)
            ->expectsJson()
            ->addHeader('Accept','*/*')
            ->addHeader('Content-Type', 'application/vnd.orcid+json')
            ->send();

        return ['entries' => $response->body->{'expanded-result'}];
    }

    public function getOrganisationData($orcidId) {
        $response = Request::get($this->apiUrl . '/expanded-search/?q=orcid:' . $orcidId)
            ->expectsJson()
            ->addHeader('Accept','*/*')
            ->addHeader('Content-Type', 'application/vnd.orcid+json')
            ->send();

        return $response->body->{'expanded-result'}[0];
    }

}