FROM composer:2.2 AS composer

FROM php:7.4.33-cli
# Debian 11 is end of life. Its packages now live on archive.debian.org only.
RUN printf '%s\n' \
      'deb http://archive.debian.org/debian bullseye main' \
      'deb http://archive.debian.org/debian-security bullseye-security main' \
      > /etc/apt/sources.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends git unzip libxml2-dev libxslt1-dev \
    && docker-php-ext-install -j"$(nproc)" xsl \
    && rm -rf /var/lib/apt/lists/*
# Report all errors, as a developer php.ini does. Tools like PHPStan need more than 128M.
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" \
    && echo 'memory_limit = -1' > "$PHP_INI_DIR/conf.d/zz-memory.ini"
COPY --from=composer /usr/bin/composer /usr/bin/composer
# Same PHPUnit phar and checksum as .github/workflows/php-code-scanning.yml
RUN curl -fsSL https://phar.phpunit.de/phpunit-9.6.22.phar -o /usr/local/bin/phpunit \
    && echo "9618d52015c9b06b4979a8e481ca9567be6be20e711e98926c61378a400e1f2e  /usr/local/bin/phpunit" | sha256sum -c - \
    && chmod +x /usr/local/bin/phpunit
WORKDIR /app
