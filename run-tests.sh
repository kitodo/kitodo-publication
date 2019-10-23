#!/bin/bash

container=typo3_2x
executable="vendor/phpunit/phpunit/phpunit"
options="-c vendor/nimut/testing-framework/res/Configuration/UnitTests.xml $@"

docker-compose run --rm --no-deps ${container} ${executable} ${options} /app/Tests