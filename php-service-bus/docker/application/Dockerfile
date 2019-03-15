FROM php:7.3-cli-alpine

RUN set -xe \
    && apk update \
    && apk add  libpq postgresql-dev libevent-dev autoconf zlib-dev g++ libtool make libzip-dev \
    && apk add --repository http://dl-cdn.alpinelinux.org/alpine/edge/community/ gnu-libiconv \
    && docker-php-ext-install \
        bcmath \
        pcntl \
        mbstring \
        sysvsem \
        zip \
        # escape bytea string
        pgsql \
    # Iconv fix
    && apk add gnu-libiconv --update-cache --repository http://dl-cdn.alpinelinux.org/alpine/edge/testing/ --allow-untrusted \
    # sockets
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

# Iconv fix
ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so phpalpine

# Composer install
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
   && chmod +x /usr/local/bin/composer \
   && composer global require hirak/prestissimo \
   && composer clear-cache

COPY ./tools/* /tools/
