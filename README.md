# kitodo-publication

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/kitodo/kitodo-publication/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/kitodo/kitodo-publication/?branch=master)

Kitodo.Publication is free software, an extension for [TYPO3](https://typo3.org/) and part of the [Kitodo Digital Library Suite](https://en.wikipedia.org/wiki/Kitodo).
It implements the user and administrator interfaces for a [document and publication server](https://en.wikipedia.org/wiki/Institutional_repository).

## DDEV Development Environment

This extension provides a [DDEV](https://ddev.readthedocs.io/en/stable/) TYPO3 environment. On initial checkout TYPO3 needs to be installed and configured via composer. Prior to running TYPO3 import inital database dump.

### Start and Configuration
1. `ddev start` to start all containers
2. `ddev composer install` to install TYPO3 and all extensions
3. `ddev import-db -f db.sql.gz` to import the prepared database
4. `ddev launch typo3` to go to the backoffice login page

### TYPO3 backend credentails
* Username: `admin`
* Password: `adminadmin`

## More information

* https://ddev.readthedocs.io/en/stable/
* https://www.kitodo.org/
* http://www.b-i-t-online.de/sponsored/Kitodo
