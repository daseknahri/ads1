#!/bin/bash
# OpenLiteSpeed variant of the getemoji.site entrypoint.
# 1) ships the repo's mu-plugins into the persistent wp-content volume (as before)
# 2) seeds WordPress core into an EMPTY volume (first run / recreated volume), without
#    touching existing files (cp -rn = no-clobber)
# 3) points the image's default OLS vhost docroot (/var/www/vhosts/localhost/html) at the
#    WordPress volume (/var/www/html). The vhost template has allowSymbolLink 1 and PHP runs
#    as the docroot owner (setUIDMode 2), so the www-data-owned volume keeps working unchanged.
set -e
DEST=/var/www/html/wp-content/mu-plugins
mkdir -p "$DEST"
cp -f /opt/site/mu-plugins/*.php "$DEST"/

if [ ! -f /var/www/html/wp-settings.php ] && [ -d /usr/src/wordpress ]; then
    echo "[ge-entrypoint] empty volume detected — seeding WordPress core"
    mkdir -p /var/www/html
    cp -rn /usr/src/wordpress/. /var/www/html/
fi

mkdir -p /var/www/vhosts/localhost
# The image ships an empty placeholder directory here; remove it before linking
# (ln -sfn would otherwise create the symlink INSIDE it).
if [ -d /var/www/vhosts/localhost/html ] && [ ! -L /var/www/vhosts/localhost/html ]; then
    rm -rf /var/www/vhosts/localhost/html
fi
ln -sfn /var/www/html /var/www/vhosts/localhost/html

# Align ownership only when needed (volume came from the Apache image = already www-data).
if [ "$(stat -c %U /var/www/html/wp-content 2>/dev/null || echo unknown)" != "www-data" ]; then
    echo "[ge-entrypoint] fixing volume ownership to www-data"
    chown -R www-data:www-data /var/www/html
fi

exec /entrypoint.sh "$@"
