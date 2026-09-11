# Leipzig UBL landing-page batch — verification checklist

Draft — verify before treating as ground truth. Covers issues #2040, #2041,
#2047, #2048, #2049 (kitodo-publication/issues), branch `landingpage-ubl-fixes`.

## Critical finding: test-web01 was 33 commits stale

When Leipzig (wuenscheUBL) last tested (2026-09-07/09), `sdvqucosa-test-web01`
was deployed at dpf commit `b0361fb` — 33 commits behind this branch's local
HEAD at session start. Confirmed live: `qucosa-33781` already renders the
merged `<dt>Sammelband</dt>` block that #2040 lists as red (`git log` shows
that fix landed in commit `0a8fc305`, long before the stale deploy).

**Consequence:** most red/yellow rows below are not necessarily open work —
they may already be fixed in commits sitting undeployed. Do not re-implement
anything here before checking bucket status first.

## Status buckets

- **A — verified live**: curled against deployed code, output matches Soll.
- **B — unverified, pending deploy**: fixed in a commit (this session's 3, or
  one of the 33-commit backlog) but never deployed/tested live.
- **C — open**: read current committed source, Soll is genuinely not
  implemented yet.

## Required deploy steps (in order) before any row below can move to bucket A

1. `git push origin landingpage-ubl-fixes` in both `dpf` and
   `slub_web_qucosa` (blocked from this session — user pushes).
2. On `sdvqucosa-test-web01`:
   ```
   cd /var/www/webroot/public/typo3conf/ext/dpf && sudo -u www-data git checkout -- . && sudo -u www-data git clean -fd
   cd /var/www/webroot/public/typo3conf/ext/slub_web_qucosa && sudo -u www-data git checkout -- . && sudo -u www-data git clean -fd
   cd /var/www/webroot && sudo -u www-data composer update kitodo/publication slub/slub-web-qucosa --no-interaction
   ```
3. **Run both new upgrade wizards — composer update does NOT execute them:**
   ```
   cd /var/www/webroot && sudo -u www-data vendor/bin/typo3cms upgrade:wizard dpfReorderReportAfterMonographInDropdown
   sudo -u www-data vendor/bin/typo3cms upgrade:wizard dpfReorderPlaceOfPublicationAfterPublisher
   ```
4. `sudo -u www-data vendor/bin/typo3cms cache:flush && sudo service php7.4-fpm reload`

## Row checklist

| Issue | Row | Example ID | Verify | Bucket |
|---|---|---|---|---|
| #2040 | Erschienen in / Sammelband merge into one `<dt>Sammelband</dt>` | qucosa-33781 | `curl -s .../landing-page/qucosa-33781 \| grep -F '<dt>Sammelband</dt>'` | **A** (confirmed live) |
| #2040 | Erscheinungsort right after Verlag (standalone works) | qucosa-14538, qucosa-11825, qucosa-14961 | `curl -s .../landing-page/qucosa-14538 \| grep -oE '<dt>[^<]*</dt>'` — Erscheinungsort should follow Verlag immediately | B (this session's wizard, needs step 3) |
| #2040 | Underline gap between license icon and text not underlined | qucosa-80960 | visual, no curl check — CSS, needs screenshot review | C (not investigated this session) |
| #2040 | Institution-as-AutorIn styled same as Institution-as-HerausgeberIn | UBL-26-5007 | visual — dt small/grey, dd bold/black for `dt.author`/`dd.author` on an institution row | C (not investigated this session — may overlap #2047's Institution:Rolle row) |
| #2040 | Beziehungen clickable-area vertical spacing too large | qucosa-75289 | visual — this record currently renders **no relation block at all** on stale deploy; re-check after deploy which record actually exercises it | C |
| #2041 | `ubl-26-5023` Andere Ausgabe → ISSN/DOI/URN identifier-group restructure per 2026-09-09 Soll spec (drop "Erstveröffentlichung"/"Link zum Original" text, DOI/URN split into own dt/dd pairs) | qucosa-94827, qucosa-15411 | `curl -s .../landing-page/qucosa-94827 \| grep -F 'Erstveröffentlichung'` should return nothing once fixed | C (spec given in comment, not yet implemented) |
| #2047 | "Verweis" relation missing URN/Handle/ISBN/ISSN/ZDB-ID/Bandzählung/Heftzählung | `UBL-26-5088` | check `relationDetailLines()` output includes all RELATION_DETAIL_FIELDS for Verweis type | C |
| #2047 | Kollektion "Zweitveröffentlichung" — only that one collection shown, word "Kollektion" dropped | UBL-26-5007 | `curl ... \| grep -F 'Zweitveröffentlichung'` and absence of `>Kollektion<` | C |
| #2047 | Person second-look info (Institutionszugehörigkeit, ORCID, etc.) hidden behind click/hover, not shown inline | UBL-26-5007 | visual/interaction — tooltip mechanism exists (`buildNameIdentifierTooltips`), confirm it's not showing all fields inline by default | C |
| #2047 | Institution "Beitragende/r (Institution)" row position — should sit directly after "Beitragende/r" | ubl-26-5018 | `curl -s ... \| grep -oE '<dt>[^<]*</dt>'` — check adjacency | C |
| #2047 | UW Identifier: ISI, PMID missing | ubl-26-5018 | `curl ... \| grep -E 'ISI\|PMID'` | C |
| #2047 | Zugangsstatus "Embargoed Access" must show even when embargo date can't be parsed (e.g. "2031") | ubl-26-5064, UBL-26-5152, UBL-26-5149 | check embargo status renders regardless of date-parse success | C |
| #2047 | Title/abstract/keywords missing for Georgian/Macedonian/Swedish/Catalan; language label inconsistent (ITA vs Katalanisch) | ubl-26-5006 | visual + `curl ... \| grep -F 'Katalanisch'` vs `'ITA'` — standardize to one form | C |
| #2047 | English project title row misplaced ("1. Projekt (Englisch)" should not be its own row — must merge into "Titel des Projekts / Titel des Projekts (Englisch)") | ubl-26-5006 | `curl ... \| grep -F '1. Projekt (Englisch)'` should return nothing once fixed | C |
| #2048 | Nachschlagewerk pill (encyclopedia typo) | ubl-26-5156 | `curl -s .../landing-page/ubl-26-5156 \| grep -F 'Nachschlagewerk'` | **B** (fixed this session, commit 8db12808/2648dd7) |
| #2048 | Dokumententyp → Publikationstyp dropdown label | n/a (search form) | `curl -s https://test.leupub.qucosa.de/suche \| grep -F 'Publikationstyp'` | **B** (fixed this session) |
| #2048 | researchData/software excluded from dropdown | n/a | `curl -s https://test.leupub.qucosa.de/suche \| grep -F 'Forschungsdaten'` should return nothing | **B** (fixed this session) |
| #2048 | report sorts after monograph in dropdown | n/a | check dropdown option order | **B** (fixed this session, wizard needs step 3) |
| #2048 | contained_work/magister/master_thesis/text landing-page pill labels | qucosa-12055 (contained_work), qucosa-74994 (magister), qucosa-12098 (master) | `curl ... \| grep -F 'Beitrag in Sammelband'` etc. | **B** (fixed this session — found by systematic DB-vs-TS diff, not itemized by Leipzig; sanity-check intent) |
| #2049 | Peer Review search merge (j/n/u + absent) | UBL-PUBMAN-122006 | already fixed on `main` before this session (commit f5f09c71), test covers exact `u+0` case | **B** — code correct, deploy-verify only |

## Notes

- `software = Software` label added to both label trees alongside the
  dropdown exclusion — speculative: not confirmed any live document actually
  carries `type=software`. Harmless if unused, flagged rather than silently
  listed as a real-world fix.
- `SearchFEController::getDocTypes()` has no unit test — matches this repo's
  documented "no controller-level tests exist, integration testing needs the
  full TYPO3 test framework" constraint (see `dpf/CLAUDE.local.md`).
- #2041's ISSN/DOI/URN restructure spec (2026-09-09 comment) is detailed
  enough to implement directly next session — not attempted here for time.
