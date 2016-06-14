<?php
namespace EWW\Dpf\Services\Identifier;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

class Urn
{

    /**
     * clientRepository
     *
     * @var \EWW\Dpf\Domain\Repository\ClientRepository
     * @inject
     */
    protected $clientRepository = null;

    public function getUrn($niss)
    {

        $client = $this->clientRepository->findAll()->current();

        // Workaround to ensure unique URNs until URNs will be genarated by fedora.
        $replaceNissPart = $client->getReplaceNissPart();
        $nissPartSearch  = $client->getNissPartSearch();
        $nissPartReplace = $client->getNissPartReplace();
        if ($replaceNissPart && !empty($nissPartSearch) && !empty($nissPartReplace)) {
            $niss = str_replace($nissPartSearch, $nissPartReplace, $niss);
        }

        $niss = str_replace(":", '-', $niss);

        $snid1 = $client->getNetworkInitial();
        $snid2 = $client->getLibraryIdentifier();

        $identifierUrn = new \EWW\Dpf\Services\Identifier\UrnBuilder($snid1, $snid2);
        return $identifierUrn->getUrn($niss);
    }

}
