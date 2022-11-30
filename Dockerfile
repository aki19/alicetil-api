#FROM node:16-slim as node-builder

#COPY . ./app
#RUN cd /app
#RUN npm ci && npm run prod

FROM php:7.2-apache

ENV APACHE_DOCUMENT_ROOT /var/www/html/public/

RUN apt-get update && apt-get install -y \
  zip \
  unzip \
  git

RUN docker-php-ext-install -j "$(nproc)" opcache && docker-php-ext-enable opcache

#RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --from=composer:2.0 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . ./
#COPY --from=node-builder /app/public ./public
#COPY /app/public ./public
RUN composer install
RUN chown -Rf www-data:www-data ./

EXPOSE 80
