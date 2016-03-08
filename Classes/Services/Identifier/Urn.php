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
                     
        public function getUrn($niss) {
            
            $client = $this->clientRepository->findAll()->current();
            
            // Workaround to ensure unique URNs until URNs will be genarated by fedora.
            $replaceNissPart = $client->getReplaceNissPart();        
            $nissPartSearch = $client->getNissPartSearch();            
            $nissPartReplace = $client->getNissPartReplace();                                                   
            if ($replaceNissPart && !empty($nissPartSearch) && !empty($nissPartReplace)) {                        
                $niss = str_replace($nissPartSearch,$nissPartReplace,$niss);                                
            }            
                                                                 
            $niss = str_replace(":",'-',$niss);
                                   
            $snid1 = $client->getNetworkInitial();
            $snid2 = $client->getLibraryIdentifier();          
                        
            $identifierUrn = new \EWW\Dpf\Services\Identifier\UrnBuilder($snid1, $snid2);              
            return $identifierUrn->getUrn($niss);                        
        }
           
}


?>
