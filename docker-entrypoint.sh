#!/bin/sh
set -e

# Dynamically adjust Apache port for Render ($PORT is set by Render, default 10000 or 80)
PORT_TO_USE="${PORT:-80}"
sed -i "s/Listen 80/Listen ${PORT_TO_USE}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${PORT_TO_USE}>/g" /etc/apache2/sites-available/*.conf

# Ensure storage directories exist with correct permissions
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# If SQLite is used, ensure database file exists
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    mkdir -p database
    touch database/database.sqlite
    chown -R www-data:www-data database
    chmod 775 database
    chmod 664 database/database.sqlite
fi

# Link storage
php artisan storage:link --no-interaction || true

# Cache configuration, routes, and views for production performance
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Run database migrations automatically unless disabled
if [ "$AUTO_MIGRATE" != "false" ]; then
    echo "Running database migrations..."
    php artisan migrate --force --no-interaction || true
fi

# Seed database on first run if SEED_DATABASE is true
if [ "$SEED_DATABASE" = "true" ]; then
    echo "Seeding database..."
    php artisan db:seed --force --no-interaction || true
fi

echo "Starting Apache web server on port ${PORT_TO_USE}..."
exec "$@"
