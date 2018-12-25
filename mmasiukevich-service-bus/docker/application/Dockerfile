FROM php:7.2-cli-alpine

RUN set -xe \
    && apk add --no-cache --virtual .persistent-deps  postgresql-dev zlib-dev libevent-dev libpq \
    && apk add --no-cache --virtual .build-deps autoconf g++ libtool make \
    && docker-php-ext-install \
        bcmath \
        pcntl \
        mbstring \
        sysvsem \
        zip \
    # Iconv fix
    && apk add gnu-libiconv --update-cache --repository http://dl-cdn.alpinelinux.org/alpine/edge/testing/ --allow-untrusted \
    && docker-php-ext-install sockets \
    # event
    && pecl install event \
    && docker-php-ext-enable event \
    && mv /usr/local/etc/php/conf.d/docker-php-ext-event.ini /usr/local/etc/php/conf.d/docker-php-ext-zz-event.ini \
    # raphf
    && pecl install raphf \
    && docker-php-ext-enable raphf \
    # pq
    && pecl install pq \
    && echo "extension=pq.so" > /usr/local/etc/php/conf.d/pq.ini \
	&& rm -rf /tmp/* /var/cache/apk/*

ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so php

# Composer install
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
   && chmod +x /usr/local/bin/composer \
   && composer global require hirak/prestissimo \
   && composer clear-cache

COPY ./tools/* /tools/
