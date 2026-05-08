FROM php:8.2-apache

RUN apt-get update && apt-get install -y libzip-dev unzip libsqlite3-dev \
    && docker-php-ext-install zip pdo pdo_sqlite \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite

# Fix MPM nuclear: borrar TODO lo de mpm y recrear solo prefork
RUN rm -f /etc/apache2/mods-enabled/mpm_*.conf \
          /etc/apache2/mods-enabled/mpm_*.load && \
    ln -sf /etc/apache2/mods-available/mpm_prefork.load \
           /etc/apache2/mods-enabled/mpm_prefork.load && \
    ln -sf /etc/apache2/mods-available/mpm_prefork.conf \
           /etc/apache2/mods-enabled/mpm_prefork.conf

# Verificar que quedó correcto
RUN echo "MPM activo:" && ls /etc/apache2/mods-enabled/ | grep mpm

RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

COPY . /var/www/html/

RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html/data \
    && chmod 775 /var/www/html/data

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]