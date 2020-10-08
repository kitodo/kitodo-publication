#!/bin/bash

container=typo3
executable="vendor/phpunit/phpunit/phpunit"
options="-c vendor/nimut/testing-framework/res/Configuration/UnitTests.xml $@"

export ENV_HOST_IP=172.17.0.1
docker network create qucosa_backend
docker-compose -f Build/docker-compose.yml run --rm --no-deps ${container} ${executable} ${options} /app/Tests
