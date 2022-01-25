<?php
namespace EWW\Dpf\Services\Transfer;

/*
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

/**
 * Interface Repository
 * @package EWW\Dpf\Services\Transfer
 * @deprecated
 */
interface Repository
{

    public function ingest($document, $metsXml);

    public function update($document, $metsXml);

    public function retrieve($document);

    public function delete($document, $state);

    public function getNextDocumentId();

}
