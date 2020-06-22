FROM php:7.4-fpm

RUN apt-get update && apt-get install -y \
    git \
    wget

RUN apt-get install -y libpq-dev supervisor \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql

ADD ./php.ini /usr/local/etc/php/php.ini
ADD ./entrypoint.sh /var/entrypoint.sh

RUN chmod u+x /var/entrypoint.sh

RUN wget https://getcomposer.org/installer -O - -q \
    | php -- --install-dir=/bin --filename=composer --quiet


WORKDIR /var/www

CMD ["/var/entrypoint.sh"]
