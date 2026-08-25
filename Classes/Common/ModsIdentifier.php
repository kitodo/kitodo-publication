<?php

namespace EWW\Dpf\Common;

/**
 * Shared MODS relatedItem identifier-type preference logic, used by both
 * the legacy RelatedListTool plugin and the new LandingPageAssembler so the
 * two rendering paths can't drift apart on which @type is preferred.
 */
class ModsIdentifier
{
    /**
     * Picks the preferred mods:identifier/@type on a relatedItem node:
     * 'urn' or 'local' if present, otherwise the first type found.
     *
     * @param \SimpleXMLElement $node A mods:relatedItem (or similar) node
     * @return string The preferred identifier type, or '' if none present
     */
    public static function preferredType(\SimpleXMLElement $node): string
    {
        $types = $node->xpath('mods:identifier/@type');
        if (empty($types)) {
            return '';
        }
        $values = array_map('strval', $types);
        foreach (['urn', 'local'] as $preferred) {
            if (in_array($preferred, $values, true)) {
                return $preferred;
            }
        }
        return $values[0];
    }
}
