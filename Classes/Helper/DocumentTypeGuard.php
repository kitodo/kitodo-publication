<?php

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

namespace EWW\Dpf\Helper;

/**
 * Rejects a document type uid that the current client may not use.
 *
 * The uid comes from the request. findByUid() ignores the storage pid, so a type of
 * another client would be accepted. findOneByUid() respects it and returns null instead.
 */
class DocumentTypeGuard
{
    /**
     * @param object $documentTypeRepository \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     * @param mixed  $uid
     * @throws \InvalidArgumentException
     */
    public static function assertAvailable($documentTypeRepository, $uid)
    {
        if (!ctype_digit((string) $uid) || (int) $uid < 1) {
            throw new \InvalidArgumentException('Invalid document type uid.');
        }

        if (!$documentTypeRepository->findOneByUid((int) $uid)) {
            throw new \InvalidArgumentException('Document type not available for this client.');
        }
    }
}
