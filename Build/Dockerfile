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
WORKDIR /app
