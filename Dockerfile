FROM php:8.3.13

ENV TZ=America/Sao_Paulo \
    DEBIAN_FRONTEND=noninteractive
ENV PHP_IDE_CONFIG="serverName=local"

# One of tests need to allocate 128M of memory
RUN echo "memory_limit=256M" > /usr/local/etc/php/conf.d/memory-limit.ini

RUN apt-get update
RUN apt-get install -y --no-install-recommends \
      tzdata  \
      libzip-dev \
      libxml2-dev \
      libcurl4-openssl-dev \
      libonig-dev \
      libssl-dev \
      pkg-config \
      unzip \
      curl \
      default-mysql-server \
      netcat-traditional

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN docker-php-ext-install mysqli
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install mbstring
RUN docker-php-ext-install xml
RUN docker-php-ext-install zip
RUN docker-php-ext-install bcmath
RUN docker-php-ext-install curl
RUN docker-php-ext-install sockets

RUN pecl install xdebug && \
    docker-php-ext-enable xdebug

RUN usermod -d /var/lib/mysql mysql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /laravel-replicador
