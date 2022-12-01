#!/usr/bin/env bash
echo "Running composer"
composer global require hirak/prestissimo
#composer install --no-dev --working-dir=/var/www/html

echo "Caching config..."
#php artisan config:cache

echo "Caching routes..."
#php artisan route:cache

echo "composer dump..."
composer install --no-scripts
composer update --no-dev
composer dump-autoload -o

echo "Running migrations..."
php artisan migrate --force
