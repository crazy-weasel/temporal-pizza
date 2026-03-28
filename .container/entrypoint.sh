#!/bin/sh
set -e

composer install --no-interaction --prefer-dist
php bin/console importmap:install
php bin/console tailwind:build
php bin/console doctrine:migrations:migrate --allow-no-migration --no-interaction

PIZZA_COUNT=$(psql "${DATABASE_URL%%\?*}" -tAc "SELECT COUNT(*) FROM pizza" 2>/dev/null || echo 0)
if [ "$PIZZA_COUNT" = "0" ]; then
    php bin/console doctrine:fixtures:load --no-interaction
fi

exec rr serve -c .rr.yaml
