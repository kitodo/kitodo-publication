<?php
namespace EWW\Dpf\Services\FeUser;

use Httpful\Request;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

class FisDataService
{
    /**
     * @var string
     */
    protected $apiUrl = '';

    public function __construct() {

        $configurationManager = GeneralUtility::makeInstance(
            'TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager'
        );

        $settings = $configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );

        if (isset($settings['plugin.']['tx_dpf.']['settings.']['fisDataServiceUrl'])) {
            $this->apiUrl = $settings['plugin.']['tx_dpf.']['settings.']['fisDataServiceUrl'];
        }
    }

    public function getPersonData($id) {
        try {
            $response = Request::post($this->apiUrl)
                ->body($this->getPersonRequestBody($id))
                ->send();

            foreach ($response->body->data->person->organisationalUnits as $key => $organisationalUnit) {
                $titleKitodo = $this->mergeOrganisationTitleAndId(
                    $organisationalUnit->titleDe,
                    $organisationalUnit->id
                );

                if ($titleKitodo) {
                    $response->body->data->person->organisationalUnits[$key]->kitodoOrgaTitle = $titleKitodo;
                }

            }

            return $response->body->data->person;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function searchPersonRequest($searchTerm) {
        try {
            $response = Request::post($this->apiUrl)
                ->body($this->getSearchPersonBody($searchTerm))
                ->send();

            return $response->body->data->staff;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getProjectData($id) {
        try {
            $response = Request::post($this->apiUrl)
                ->body($this->getProjectRequestBody($id))
                ->send();
            if (sizeof($response->body->data->projekte->entries) === 1) {
                return $response->body->data->projekte->entries[0];
            }
            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function searchProjectRequest($searchTerm) {
        try {
            $response = Request::post($this->apiUrl)
                ->body($this->getSearchProjectBody($searchTerm))
                ->send();
            return $response->body->data->projekte;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getOrganisationData($id) {
        try {
            $response = Request::post($this->apiUrl)
                ->body($this->getOrgaRequestBody($id))
                ->send();

            $titleKitodo = $this->mergeOrganisationTitleAndId(
               $response->body->data->organisationalUnit->titleDe,
               $response->body->data->organisationalUnit->id
            );

            if ($titleKitodo) {
                $response->body->data->organisationalUnit->kitodoOrgaTitle = $titleKitodo;
            }

            return $response->body->data->organisationalUnit;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function searchOrganisationRequest($searchTerm) {
        try {
            $response = Request::post($this->apiUrl)
                ->body($this->getSearchOrgaBody($searchTerm))
                ->send();

            return $response->body->data->organisationalUnits;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function getPersonRequestBody($id) {
        $graphQl = '{
            person (id:"'.$id.'")
            {
                fisPersid
                fullName
                givenName
                surname
                title
                organisationalUnits
                {
                    titleDe
                    id
                }
            }
        }';

        return $graphQl;
    }

    protected function getSearchPersonBody($searchTerm) {
        $graphQl = '{
            staff(filter: {fullName: "'.$searchTerm.'"}, pageSize: 100)
            {
                entries
                {
                ... on Person
                    {
                        organisationalUnits
                        {
                            titleDe
                        }
                        fisPersid
                        fullName
                        givenName
                        surname
                        title
                    }
                }
            }
        }';

        return $graphQl;
    }

    protected function getProjectRequestBody($id) {
        $graphQl = '{
            projekte(query:"'.$id.'", pageSize: 1)
            {
                entries
                {
                ... on Projektkern
                    {
                        id
                        titelDe
                        titelEn
                        foerderkennzeichen
                        foerderinstitutionen
                        beginn
                        ende
                    }
                }
            }
        }';

        return $graphQl;
    }

    protected function getSearchProjectBody($searchTerm) {
        $graphQl = '{
            projekte(query:"'.$searchTerm.'", pageSize: 100)
            {
                entries
                {
                ... on Projektkern
                    {
                        id
                        titelDe
                        titelEn
                        foerderkennzeichen
                        foerderinstitutionen
                        beginn
                        ende
                    }
                }
            }
        }';

        return $graphQl;
    }

    protected function getOrgaRequestBody($id) {
        $graphQl = '{
            organisationalUnit(id:'.$id.') {
                titleDe
                id
            }
        }';

        return $graphQl;
    }

    protected function getSearchOrgaBody($searchTerm) {
        $graphQl = '{
            organisationalUnits(filter: {text: "'.$searchTerm.'"})
            {
                entries
                {
                ... on OrganisationalUnit
                    {
                        titleDe
                        id
                        parentOrgaName
                    }
                }
            }
        }';

        return $graphQl;
    }

    /**
     * @param string|null $titleDe
     * @param string|null $id
     */
    protected function mergeOrganisationTitleAndId(string $titleDe = null, string $id = null)
    {
        $titleKitodo = [];

        if ($titleDe) {
            $titleKitodo[] = $titleDe;
        }

        if ($id) {
            $titleKitodo[] = '[fis:' . $id . ']';
        }

        return implode(' - ', $titleKitodo);
    }

}
