FROM php:8.2-apache

RUN apt-get update && apt-get install -y libzip-dev unzip libsqlite3-dev \
    && docker-php-ext-install zip pdo pdo_sqlite \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite

# Habilitar AllowOverride para .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]