FROM php:8.3-apache

LABEL maintainer="getlaminas.org" \
    org.label-schema.docker.dockerfile="/Dockerfile" \
    org.label-schema.name="Laminas MVC Skeleton" \
    org.label-schema.url="https://docs.getlaminas.org/mvc/" \
    org.label-schema.vcs-url="https://github.com/laminas/laminas-mvc-skeleton"

RUN apt-get update

RUN a2enmod rewrite \
    && sed -i 's!/var/www/html!/var/www/public!g' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' >> /etc/apache2/apache2.conf

RUN curl -sS https://getcomposer.org/installer \
  | php -- --install-dir=/usr/local/bin --filename=composer

RUN apt-get install --yes \
    git \
    unzip \
    zlib1g-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    libzstd-dev \
    && docker-php-ext-install zip intl pdo_mysql xml

RUN pecl install igbinary \
    && docker-php-ext-enable igbinary \
    && pecl install redis \
    && docker-php-ext-enable redis

WORKDIR /var/www