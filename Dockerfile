# OpenLiteSpeed + lsphp83 (replaces wordpress:php8.3-apache).
# Why: audit 2026-10-19 (docs/reports/2026-10-19-getemoji-perf-adsense-audit.md) — Apache cannot
# serve the LiteSpeed Cache plugin's page cache at server level, so every request booted PHP.
# With a real LiteSpeed server the existing plugin config serves cached pages natively.
# Rollback: revert to the previous Dockerfile (git history) and Redeploy.
FROM wordpress:php8.3-apache AS wp
# Only used as the source of pristine WordPress core for first-run seeding of a fresh volume.

FROM litespeedtech/openlitespeed:1.9.3-lsphp83

# Match the PHP limits the Apache image had (image already sets memory 1024M, upload/post 1024M,
# max_execution_time 300 — better than the old 900s ceiling, fine for imports via wp-cli).
RUN PHPINI=$(ls /usr/local/lsws/lsphp83/etc/php/*/litespeed/php.ini) \
    && sed -i 's/^;\?max_input_vars = .*/max_input_vars = 5000/' "$PHPINI" \
    && sed -i 's/^;\?max_input_time = .*/max_input_time = 900/' "$PHPINI"

# Pristine WordPress core for seeding an empty wp-data volume (mirrors the official image's flow).
COPY --from=wp /usr/src/wordpress /usr/src/wordpress

# Site mu-plugins (YouTube facade + fixed-size ads), copied into the wp-content volume on every start.
COPY mu-plugins/ /opt/site/mu-plugins/
COPY docker/ge-entrypoint.sh /usr/local/bin/ge-entrypoint.sh
RUN chmod +x /usr/local/bin/ge-entrypoint.sh

ENTRYPOINT ["ge-entrypoint.sh"]
