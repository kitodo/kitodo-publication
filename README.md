# kitodo-publication

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/kitodo/kitodo-publication/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/kitodo/kitodo-publication/?branch=master)

Kitodo.Publication is free software, an extension for [TYPO3](https://typo3.org/) and part of the [Kitodo Digital Library Suite](https://en.wikipedia.org/wiki/Kitodo).
It implements the user and administrator interfaces for a [document and publication server](https://en.wikipedia.org/wiki/Institutional_repository).

## DDEV Development Environment

This extension provides a TYPO3 environment powered by [DDEV](https://www.ddev.com). For more information check out the [DDEV documentation](https://ddev.readthedocs.io/en/stable/).

### Start and Configuration
1. `ddev start` to start all containers
2. `ddev first-install` to install TYPO3 and all extensions
4. `ddev import-db -f db.sql.gz` to import a prepared database
5. `ddev launch typo3` to go to the backoffice login page

Steps 1–3 are mandatory after the initial checkout to set up the virtual environment.

### TYPO3 backend credentails
* Username: `admin`
* Password: `adminadmin`

### Running Unit Tests

Run all the extensions unit tests simply by executing `ddev test`.

## More information

* https://ddev.readthedocs.io/en/stable/
* https://www.kitodo.org/
* http://www.b-i-t-online.de/sponsored/Kitodo

## Funding

Funded by European Regional Development Fund (EFRE)

![EFRE LOGO](./EFRE_EU.jpg)