FROM php:7.3-apache-stretch as typo3builder
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
    unzip less vim wget git \
# Configure PHP
    libxml2-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libpng-dev \
    libzip-dev \
    zlib1g-dev \
# Install required 3rd party tools
    graphicsmagick && \
# Configure extensions
    docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ && \
    docker-php-ext-install -j$(nproc) mysqli soap gd zip opcache intl && \
    echo 'always_populate_raw_post_data = -1' &&  > /usr/local/etc/php/conf.d/typo3.ini \
    echo 'max_execution_time = 240' >> /usr/local/etc/php/conf.d/typo3.ini && \
    echo 'max_input_vars = 1500' >> /usr/local/etc/php/conf.d/typo3.ini && \
    echo 'upload_max_filesize = 32M' >> /usr/local/etc/php/conf.d/typo3.ini && \
    echo 'post_max_size = 32M' >> /usr/local/etc/php/conf.d/typo3.ini && \
# Configure Apache as needed
    a2enmod rewrite && \
# Remove build libraries
    apt-get clean && \
    apt-get -y purge \
        libxml2-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng-dev \
        libzip-dev \
        zlib1g-dev && \
    rm -rf /var/lib/apt/lists/* /usr/src/*
WORKDIR /var/www/html
COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN echo '{"require":{"typo3/cms":"~7.6.0"}}' > /var/www/html/composer.json && \
    composer update && \
    touch FIRST_INSTALL && \
    chown -R www-data .

FROM typo3builder
ADD . /app
WORKDIR /var/www/html
RUN composer config repositories.t3ter composer https://composer.typo3.org && \
    composer config repositories.kitodo-publication path /app && \
    composer config minimum-stability dev && \
    composer config prefer-stable true && \
    composer require sjbr/static-info-tables:6.5.1 && \
    composer require typo3-ter/dlf:~2.2.0 && \
    composer require kitodo/publication && \
    chown -R www-data .

