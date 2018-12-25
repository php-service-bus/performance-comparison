FROM php:7.2-cli-alpine

RUN set -xe \
    && apk add --no-cache --virtual .persistent-deps autoconf postgresql-dev zlib-dev rabbitmq-c-dev make gcc  g++ \
    && docker-php-ext-install \
        bcmath \
        pcntl \
        pgsql \
        pdo \
        pdo_pgsql \
        mbstring \
        sysvsem \
        zip \
    && pecl download amqp-1.9.3 \
        && tar zxvf amqp-1.9.3.tgz \
        && cd amqp-1.9.3 \
        && phpize \
        && ./configure \
        && make \
        && make install \
        && cd .. \
        && rm -rf amqp-1.9.3.tgz amqp-1.9.3 \
        && docker-php-ext-enable amqp \
	&& rm -rf /tmp/* /var/cache/apk/*

# Iconv fix
RUN apk add gnu-libiconv --update-cache --repository http://dl-cdn.alpinelinux.org/alpine/edge/testing/ --allow-untrusted
ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so php

# Composer install
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
   && chmod +x /usr/local/bin/composer \
   && composer global require hirak/prestissimo \
   && composer clear-cache

COPY ./tools/* /tools/
