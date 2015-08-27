<?php
namespace EWW\Dpf\Services\Identifier;


class Urn {
       
        /**
	 * clientRepository
	 *
	 * @var \EWW\Dpf\Domain\Repository\ClientRepository
	 * @inject
	 */
	protected $clientRepository = NULL;
                     
        public function getUrn($qucosaId) {          
            list($niss,$id) = explode(":",$qucosaId);
            
            $client = $this->clientRepository->findAll()->current();
            
            $snid1 = $client->getNetworkInitial();
            $snid2 = $client->getLibraryIdentifier();          
                        
            $identifierUrn = new \EWW\Dpf\Services\Identifier\UrnBuilder($snid1, $snid2, $niss);              
            return $identifierUrn->getUrn($id);                        
        }
           
}


?>
