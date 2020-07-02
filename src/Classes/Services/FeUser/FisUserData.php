<?php
namespace EWW\Dpf\Services\FeUser;

use \Httpful\Request;

class FisUserData
{

    protected $apiUrl = 'https://fob.uni-leipzig.de/anchorwheel/api';

    public function __construct() {

    }

    public function getFisUserData($id) {
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
                        fullName
                        fisPersid
                    }     
                }   
            } 
        }';

        return $graphQl;
    }

}