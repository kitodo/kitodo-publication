<?php
namespace EWW\Dpf\Services\FeUser;

use EWW\Dpf\Configuration\ClientConfigurationManager;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use \Httpful\Request;

class UnpaywallDataService
{
    /**
     * clientConfigurationManager
     *
     * @var \EWW\Dpf\Configuration\ClientConfigurationManager
     * @inject
     */
    protected $clientConfigurationManager;

    protected $apiUrl = 'https://api.unpaywall.org/v2/';

    public function __construct() {
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
        $this->clientConfigurationManager = $objectManager->get(ClientConfigurationManager::class);
    }

    public function searchRequest($searchTerm) {
        $mail = $this->clientConfigurationManager->getSetting("adminEmail");
        $response = Request::get($this->apiUrl . '' . $searchTerm .'?email=' . $mail)
            ->send();
        if ($response->body->HTTP_status_code == 404) {
            return ['entries' => ''];
        } else {
            $response = $this->enrichInformation($response);
            return ['entries' => [$response->body]];
        }

    }

    public function getDataRequest($id) {
        $mail = $this->clientConfigurationManager->getSetting("adminEmail");
        $response = Request::get($this->apiUrl . '' . $id .'?email=' . $mail)
            ->send();

        if ($response->body->HTTP_status_code == 404) {
            return '';
        } else {
            $response = $this->enrichInformation($response);
            return $response->body;
        }
    }

    protected function enrichInformation($response) {
        $responseBody = $response->body;

        $is_oa = $responseBody->is_oa;
        $journal_is_in_doaj = $responseBody->journal_is_in_doaj;
        $host_type = $responseBody->best_oa_location->host_type;

        $color = '';
        if ($is_oa == 'true' && $journal_is_in_doaj == 'false') {
            $color = 'hybrid';
        }
        if ($is_oa == 'true' && $journal_is_in_doaj == 'true') {
            $color = 'gold';
        }
        if ($host_type == 'repository') {
            $color = 'green';
        }

        $response->body->color = $color;

        return $response;
    }

}