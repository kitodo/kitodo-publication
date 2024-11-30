<?php
namespace EWW\Dpf\Services\FeUser;

use EWW\Dpf\Configuration\ClientConfigurationManager;
use Httpful\Request;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class UnpaywallDataService extends AbstractDataService
{
    /**
     * clientConfigurationManager
     *
     * @var \EWW\Dpf\Configuration\ClientConfigurationManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $clientConfigurationManager;

    public function __construct() {
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
        $this->clientConfigurationManager = $objectManager->get(ClientConfigurationManager::class);
    }

    public function searchRequest($searchTerm) {
        $mail = $this->clientConfigurationManager->getSetting("adminEmail");
        $response = Request::get($this->getApiUrl() . '/' . $searchTerm .'?email=' . $mail)
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
        $response = Request::get($this->getApiUrl() . '/' . $id .'?email=' . $mail)
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
        
        $response->body->kitodo = new \stdClass();

        if ($response->body->oa_status === 'bronze' || $response->body->oa_status === 'closed') {
            $response->body->kitodo->oa_variant = '-';
        } else {
            $response->body->kitodo->oa_variant = $response->body->oa_status;
        }

        $unpaywallOAValues = $this->clientConfigurationManager->getUnpaywallOAValues();

        if ($is_oa) {
            $response->body->kitodo->accessStatus = $unpaywallOAValues['openAccessTrue'];
        } else {
            $response->body->kitodo->accessStatus = $unpaywallOAValues['restrictedAccessTrue'];
        }

        return $response;
    }
}
