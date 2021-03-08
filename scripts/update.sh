#!/usr/bin/env bash

cd "${BASH_SOURCE%/*}/" || exit

export COMPOSER_ALLOW_SUPERUSER=1;

cd ..

git pull
composer install

cd web

echo "$(date -u)" "Putting site into maintenance mode"
drush sset system.maintenance_mode 1

echo "$(date -u)" "Clearing caches"
drush cr

echo "$(date -u)" "Running database updates"
drush updb -y

echo "$(date -u)" "Importing configuration."
drush  config:import -y

#echo "$(date -u)" "Importing translations"
#drush  locale-check -y
#drush  locale-update -y

echo "$(date -u)" "Clearing caches"
drush cr

echo "$(date -u)" "Taking site out of maintenance mode"
drush sset system.maintenance_mode 0  -y

echo "$(date -u)" "Drush script executed"