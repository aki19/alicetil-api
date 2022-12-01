#!/usr/bin/env bash
echo "Running composer"
composer global require hirak/prestissimo
composer update --no-dev --working-dir=/var/www/html

echo "Caching config..."
#php artisan config:cache

echo "Caching routes..."
#php artisan route:cache

echo "composer dump..."
composer dump-autoload -o

echo "Running migrations..."
php artisan migrate --force
