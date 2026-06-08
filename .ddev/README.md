# DDEV Local Environment

Bootstraps a local dpf 5 development environment with TYPO3 9.5, Fedora 6, and Elasticsearch.

## Requirements

- [DDEV](https://ddev.readthedocs.io/)
- Docker

## Setup

```bash
ddev start
ddev import-db --src=<your-fixture-dump>.sql.gz
ddev exec vendor/bin/typo3cms database:updateschema
```

A SQL dump is required — a blank TYPO3 instance provides no meaningful environment.
A minimal fixture needs: TYPO3 site configuration, a backend user, and the `tx_dpf_*` tables populated.

## Services

| Service | Image | Port |
|---------|-------|------|
| TYPO3 / PHP | PHP 7.4, Apache | 80/443 |
| MariaDB | 10.6 | 3306 |
| Elasticsearch | 7.17.26 | 9200 |
| Fedora 6 | fcrepo/fcrepo:6.4.0 | 8080 |

## Notes

- **No fixture committed** — obtaining or generating a fixture dump is a prerequisite.
- CI (PHPStan + PHPUnit) runs via GitHub Actions and does not use this environment.
