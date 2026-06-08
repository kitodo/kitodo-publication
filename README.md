# kitodo-publication

Kitodo.Publication is free software, an extension for [TYPO3](https://typo3.org/) and part of the [Kitodo Digital Library Suite](https://en.wikipedia.org/wiki/Kitodo).
It implements the user and administrator interfaces for a [document and publication server](https://en.wikipedia.org/wiki/Institutional_repository).

## Development

### Running Tests

The DDEV environment provides the canonical PHP 7.4 runtime. Run tests inside the container:

```bash
ddev test                        # PHPUnit unit tests
ddev exec composer analyse       # PHPStan static analysis
ddev exec composer mess          # PHPMD mess detection
```

Running tests outside DDEV requires PHP 7.4 on PATH — no further guidance is provided for that setup.

### Local Environment (DDEV)

A [DDEV](https://www.ddev.com)-based environment is available for local development.
**A database fixture is required** — a blank TYPO3 instance provides no meaningful environment.
See [`.ddev/README.md`](.ddev/README.md) for setup and fixture requirements.

### Debugging

Enable XDebug with `ddev xdebug on` (connects to host port 9003). Disable with `ddev xdebug off`.
VS Code path mappings: `.vscode/launch.json`.

## More information

* https://ddev.readthedocs.io/en/stable/
* https://www.kitodo.org/
* http://www.b-i-t-online.de/sponsored/Kitodo

## Funding

Funded by European Regional Development Fund (EFRE)

![EFRE LOGO](./EFRE_EU.jpg)
