# DLF (Kitodo.Presentation) Decoupling

As of this version, dpf has no runtime or development dependency on the
`kitodo/presentation` (EXT:dlf) extension. The five landing-page plugins
(Metadata, MetaTags, Coins, DownloadTool, RelatedListTool) run on dpf-native
code.

## What replaced what

| DLF (v3.3.4) | dpf-native replacement |
|---|---|
| `\Kitodo\Dlf\Common\AbstractPlugin` | `\EWW\Dpf\Common\AbstractPlugin` (extends TYPO3 core pi_base) |
| `\Kitodo\Dlf\Common\Document::getInstance()` | `\EWW\Dpf\Common\MetsDocument::getInstance()` |
| `\Kitodo\Dlf\Common\MetsDocument::getMetadata()` | `\EWW\Dpf\Services\Metadata\MetadataExtractor` |
| `\Kitodo\Dlf\Format\Mods` | `\EWW\Dpf\Services\Metadata\ModsCoreExtractor` (verbatim port) |
| `\Kitodo\Dlf\Plugin\Metadata` rendering | `\EWW\Dpf\Plugin\Metadata::printMetadata()` |
| `tx_dlf_metadata` + `tx_dlf_metadataformat` + `tx_dlf_formats` | `tx_dpf_metadata` (denormalized, single table) |
| `EXT:dlf/Configuration/Flexforms/Metadata.xml` | `EXT:dpf/Configuration/FlexForms/Metadata.xml` |

## Migration

The upgrade wizard `dpfMigrateDlfMetadata`
(`\EWW\Dpf\Updates\MigrateDlfMetadataUpdate`) copies the metadata
configuration from `tx_dlf_metadata` into `tx_dpf_metadata`, preserving the
original uids so `l18n_parent` translation chains stay valid. Run it once
after updating:

```
vendor/bin/typo3cms database:updateschema "*.add"
vendor/bin/typo3cms upgrade:run dpfMigrateDlfMetadata
```

The wizard is repeatable (clears `tx_dpf_metadata` first) and degrades
gracefully when the `tx_dlf_*` tables are gone.

## Behavioral notes / deviations

- The plugins now uniformly use the `tx_dpf` parameter namespace (prefixId).
  Previously the four tool plugins inherited `tx_dlf` from DLF and therefore
  received no `qid` — they rendered nothing.
- The plugin wrapper div carries both `tx-dpf-<plugin>` and the legacy
  `tx-dlf-<plugin>` CSS class.
- `owner`/`type`/`collection`/`language` values are translated via the
  TypoScript label map `plugin.tx_dpf_metadata.labels.<index_name>.<value>`
  instead of the `tx_dlf_libraries`/`structures`/`collections` tables.
- The Metadata plugin reads its TypoScript from `plugin.tx_dpf_metadata.`
  (previously, via DLF, from `plugin.tx_dlf_metadata.`) — site configuration
  must be moved to the new key.
- Metadata format support is hardcoded to MODS and SLUB (the only formats in
  Qucosa data); `tx_dlf_formats` has no equivalent.
