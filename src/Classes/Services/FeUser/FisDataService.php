<?php
namespace EWW\Dpf\Services\FeUser;

use \Httpful\Request;

class FisDataService
{

    protected $apiUrl = 'https://fob.uni-leipzig.de/anchorwheel/api';

    public function __construct() {

    }

    public function getPersonData($id) {
        $response = Request::post($this->apiUrl)
            ->body($this->getPersonRequestBody($id))
            ->send();

        return $response->body->data->person;
    }

    public function searchPersonRequest($searchTerm) {
        $response = Request::post($this->apiUrl)
            ->body($this->getSearchPersonBody($searchTerm))
            ->send();

        return $response->body->data->staff;
    }

    public function getOrganisationData($id) {
        $response = Request::post($this->apiUrl)
            ->body($this->getOrgaRequestBody($id))
            ->send();

        return $response->body->data->organisationalUnit;
    }

    public function searchOrganisationRequest($searchTerm) {
        $response = Request::post($this->apiUrl)
            ->body($this->getSearchOrgaBody($searchTerm))
            ->send();

        return $response->body->data->organisationalUnits;
    }

    protected function getPersonRequestBody($id) {
        $graphQl = '{
            person (id:"'.$id.'") 
            {
                _createDate
                _createUser
                _entityName
                _stringRep
                _updateDate
                _updateUser
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
                    }     
                }   
            } 
        }';

        return $graphQl;
    }

}