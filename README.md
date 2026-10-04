# kitodo-publication

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/kitodo/kitodo-publication/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/kitodo/kitodo-publication/?branch=master)

Kitodo.Publication is free software, an extension for [TYPO3](https://typo3.org/) and part of the [Kitodo Digital Library Suite](https://en.wikipedia.org/wiki/Kitodo).
It implements the user and administrator interfaces for a [document and publication server](https://en.wikipedia.org/wiki/Institutional_repository).

## Development environment

Tests and static analysis run in a Docker container with PHP 7.4.33. You need `docker` with the `compose` plugin. You do not need PHP on the host.

```
docker compose run --rm php composer install
docker compose run --rm php phpunit
docker compose run --rm php vendor/bin/phpstan analyse
```

The image is defined in `Build/Dockerfile`. It contains PHP, Composer and the PHPUnit phar that CI uses. It does not contain TYPO3, a web server or a database.

## More information

* https://www.kitodo.org/
* http://www.b-i-t-online.de/sponsored/Kitodo
