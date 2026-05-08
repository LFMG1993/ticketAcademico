#!/bin/bash
set -e

echo "=== MPM antes del fix ==="
ls /etc/apache2/mods-enabled/ | grep mpm || true

# Fix MPM en runtime (antes de que Apache inicie)
rm -f /etc/apache2/mods-enabled/mpm_event.conf \
      /etc/apache2/mods-enabled/mpm_event.load \
      /etc/apache2/mods-enabled/mpm_worker.conf \
      /etc/apache2/mods-enabled/mpm_worker.load

# Asegurar que prefork esté activo
ln -sf /etc/apache2/mods-available/mpm_prefork.load \
       /etc/apache2/mods-enabled/mpm_prefork.load 2>/dev/null || true
ln -sf /etc/apache2/mods-available/mpm_prefork.conf \
       /etc/apache2/mods-enabled/mpm_prefork.conf 2>/dev/null || true

echo "=== MPM después del fix ==="
ls /etc/apache2/mods-enabled/ | grep mpm || true

# Fix directorio data
DATA_DIR=/var/www/html/data
mkdir -p "$DATA_DIR"
chown -R www-data:www-data "$DATA_DIR"
chmod 775 "$DATA_DIR"

if [ ! -f "$DATA_DIR/database.sqlite" ]; then
    touch "$DATA_DIR/database.sqlite"
    chown www-data:www-data "$DATA_DIR/database.sqlite"
    echo "SQLite database created."
fi

exec "$@"