# kitodo-publication

Kitodo.Publication is free software, an extension for [TYPO3](https://typo3.org/) and part of the [Kitodo Digital Library Suite](https://en.wikipedia.org/wiki/Kitodo).
It implements the user and administrator interfaces for a [document and publication server](https://en.wikipedia.org/wiki/Institutional_repository).

## Development

### Running Tests

Tests and static analysis run in a Docker container with PHP 7.4.33. You need `docker` with the `compose` plugin. You do not need PHP on the host.

```bash
docker compose run --rm php composer install
docker compose run --rm php composer test      # PHPUnit unit tests
docker compose run --rm php composer analyse   # PHPStan static analysis
docker compose run --rm php composer mess      # PHPMD mess detection
```

The image is defined in `Build/Dockerfile`. It contains PHP and Composer. It does not contain TYPO3, a web server or a database.

## More information

* https://www.kitodo.org/
* http://www.b-i-t-online.de/sponsored/Kitodo

## Funding

Funded by European Regional Development Fund (EFRE)

![EFRE LOGO](./EFRE_EU.jpg)
