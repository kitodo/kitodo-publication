FROM slub/typo3:7

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

