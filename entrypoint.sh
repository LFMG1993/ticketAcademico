#!/bin/bash
# Fix permisos en data dir (volumen montado)
chown -R www-data:www-data /var/www/data
chmod 775 /var/www/data
touch /var/www/data/database.sqlite
chown www-data:www-data /var/www/data/database.sqlite

exec "$@"