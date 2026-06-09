<?php
namespace EWW\Dpf\Services\Metadata;

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

use EWW\Dpf\Common\MetsDocument;

/**
 * Metadata extraction for the toplevel logical structure node.
 *
 * Replicates \Kitodo\Dlf\Common\MetsDocument::getMetadata() (v3.3.4)
 * against the dpf-owned tx_dpf_metadata mapping table: core MODS seeding,
 * then the configured XPath rules evaluated over the dmdSec content node.
 */
class MetadataExtractor
{
    /**
     * @var MetadataMappingRepository
     */
    protected $repository;

    public function __construct(MetadataMappingRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Extract metadata for the document's toplevel structure node.
     *
     * @param MetsDocument $document
     * @param int $cPid PID of the tx_dpf_metadata definitions
     * @return array
     */
    public function extract(MetsDocument $document, int $cPid): array
    {
        if ($cPid <= 0) {
            return [];
        }

        // Same initial shape as DLF MetsDocument::getMetadata().
        $metadata = [
            'title' => [],
            'title_sorting' => [],
            'author' => [],
            'place' => [],
            'year' => [],
            'prod_id' => [],
            'record_id' => [],
            'opac_id' => [],
            'union_id' => [],
            'urn' => [],
            'purl' => [],
            'type' => [],
            'volume' => [],
            'volume_sorting' => [],
            'license' => [],
            'terms' => [],
            'restrictions' => [],
            'out_of_print' => [],
            'rights_info' => [],
            'collection' => [],
            'owner' => [],
            'mets_label' => [],
            'mets_orderlabel' => [],
            'document_format' => ['METS'],
        ];

        $toplevelId = $document->getToplevelId();
        if (empty($toplevelId)) {
            return [];
        }

        $dmdIdAttribute = $document->mets->xpath(
            './mets:structMap[@TYPE="LOGICAL"]//mets:div[@ID="' . $toplevelId . '"]/@DMDID'
        );
        if (empty($dmdIdAttribute)) {
            return [];
        }
        $dmdIds = explode(' ', (string) $dmdIdAttribute[0]);

        $dmdSecs = $document->getDmdSec();
        $hasSupportedMetadata = false;

        foreach ($dmdIds as $dmdId) {
            if (empty($dmdSecs[$dmdId])) {
                // Unsupported metadata format — try the next @DMDID.
                continue;
            }
            $type = $dmdSecs[$dmdId]['type'];
            $dmdXml = $dmdSecs[$dmdId]['xml'];

            if ($type === 'MODS') {
                (new ModsCoreExtractor())->extractMetadata($dmdXml, $metadata);
            }

            $structType = $document->mets->xpath(
                './mets:structMap[@TYPE="LOGICAL"]//mets:div[@ID="' . $toplevelId . '"]/@TYPE'
            );
            if (!empty($structType)) {
                $metadata['type'] = [(string) $structType[0]];
            }

            $rules = $this->repository->findExtractionRules($cPid, $type);

            // A \DOMXPath is needed because SimpleXML doesn't support XPath
            // functions properly — same as DLF.
            $domNode = dom_import_simplexml($dmdXml);
            $domXPath = new \DOMXPath($domNode->ownerDocument);
            MetsDocument::registerNamespaces($domXPath);

            foreach ($rules as $rule) {
                $indexName = $rule['index_name'];
                if (
                    $rule['format'] > 0
                    && !empty($rule['xpath'])
                    && ($values = $domXPath->evaluate($rule['xpath'], $domNode))
                ) {
                    if (
                        $values instanceof \DOMNodeList
                        && $values->length > 0
                    ) {
                        $metadata[$indexName] = [];
                        foreach ($values as $value) {
                            $metadata[$indexName][] = trim((string) $value->nodeValue);
                        }
                    } elseif (!($values instanceof \DOMNodeList)) {
                        $metadata[$indexName] = [trim((string) $values)];
                    }
                }
                if (
                    empty($metadata[$indexName][0])
                    && strlen($rule['default_value']) > 0
                ) {
                    $metadata[$indexName] = [$rule['default_value']];
                }
                if (
                    !empty($metadata[$indexName])
                    && $rule['is_sortable']
                ) {
                    if (
                        $rule['format'] > 0
                        && !empty($rule['xpath_sorting'])
                        && ($values = $domXPath->evaluate($rule['xpath_sorting'], $domNode))
                    ) {
                        if (
                            $values instanceof \DOMNodeList
                            && $values->length > 0
                        ) {
                            $metadata[$indexName . '_sorting'][0] = trim((string) $values->item(0)->nodeValue);
                        } elseif (!($values instanceof \DOMNodeList)) {
                            $metadata[$indexName . '_sorting'][0] = trim((string) $values);
                        }
                    }
                    if (empty($metadata[$indexName . '_sorting'][0])) {
                        $metadata[$indexName . '_sorting'][0] = $metadata[$indexName][0];
                    }
                }
            }

            if (empty($metadata['title'][0])) {
                $metadata['title'][0] = '';
                $metadata['title_sorting'][0] = '';
            }

            // Extract metadata only from the first supported dmdSec.
            $hasSupportedMetadata = true;
            break;
        }

        if ($hasSupportedMetadata) {
            return $metadata;
        }
        return [];
    }
}
