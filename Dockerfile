FROM php:8.3-apache

LABEL maintainer="getlaminas.org" \
    org.label-schema.docker.dockerfile="/Dockerfile" \
    org.label-schema.name="Laminas MVC Skeleton" \
    org.label-schema.url="https://docs.getlaminas.org/mvc/" \
    org.label-schema.vcs-url="https://github.com/laminas/laminas-mvc-skeleton"

RUN apt-get update

RUN a2enmod rewrite \
    && sed -i 's!/var/www/html!/var/www/public!g' /etc/apache2/sites-available/000-default.conf \
    && mv /var/www/html /var/www/public

RUN curl -sS https://getcomposer.org/installer \
  | php -- --install-dir=/usr/local/bin --filename=composer

# Dependências e extensões principais
RUN apt-get install --yes \
    git \
    unzip \
    zlib1g-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    && docker-php-ext-install zip intl pdo_mysql xml

# Redis
RUN pecl install igbinary \
    && docker-php-ext-enable igbinary \
    && apt-get install --yes libzstd-dev \
    && pecl install redis \
    && docker-php-ext-enable redis

WORKDIR /var/www