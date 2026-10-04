#!/bin/bash
# Ships the site's mu-plugins into the persistent wp-content volume on every start (so a redeploy always carries
# the repo's version), then hands over to the stock WordPress entrypoint.
set -e
DEST=/var/www/html/wp-content/mu-plugins
mkdir -p "$DEST"
cp -f /opt/site/mu-plugins/*.php "$DEST"/
chown -R www-data:www-data "$DEST"
exec docker-entrypoint.sh "$@"
