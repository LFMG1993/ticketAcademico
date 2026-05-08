#!/bin/bash
set -e

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