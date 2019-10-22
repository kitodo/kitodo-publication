# kitodo-publication

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/bce3bc0c744f46acae307b3f81e704b4)](https://www.codacy.com/app/claussni/kitodo-publication?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=kitodo/kitodo-publication&amp;utm_campaign=Badge_Grade)

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/kitodo/kitodo-publication/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/kitodo/kitodo-publication/?branch=master)

Kitodo.Publication is free software, an extension for [TYPO3](https://typo3.org/) and part of the [Kitodo Digital Library Suite](https://en.wikipedia.org/wiki/Kitodo).
It implements the user and administrator interfaces for a [document and publication server](https://en.wikipedia.org/wiki/Institutional_repository).

## Docker Container

To start Docker environment you need to have `docker` and `docker-compose` installed. To start up the whole system just run ``docker-compose up``. This will create and download the Docker images needed to run a database and a fresh TYPO3 system with all necessary extensions pre-installed.

The Docker image for the web container also contains XDebug. To allow XDebug to connect to your local debugging environment you have to pass your docker host IP as environment variable:

```$ ENV_HOST_IP=<<your_ip_here>> docker-compose up```

If you don't provide a value, the default is `172.21.0.1` which is usually the default IP for the docker host network.

## More information

* https://www.kitodo.org/
* http://www.b-i-t-online.de/sponsored/Kitodo
