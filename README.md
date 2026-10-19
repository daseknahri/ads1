# ads1 — getemoji.site on Coolify

Self-contained Docker Compose stack to run the **getemoji.site** WordPress site on a
Coolify VPS. Migrated from the previous host via All-in-One WP Migration.

## Stack
- **wordpress** — OpenLiteSpeed + lsphp83 (`litespeedtech/openlitespeed:1.9.3-lsphp83`, extended
  via `Dockerfile`; since 2026-10-19, was `wordpress:php8.3-apache`). The LiteSpeed Cache plugin
  then serves its page cache at server level — see
  `docs/reports/2026-10-19-getemoji-perf-adsense-audit.md`. Web root stays `/var/www/html`
  (persisted in the `wp-data` volume); the entrypoint symlinks the OLS vhost docroot to it and
  seeds WordPress core from `/usr/src/wordpress` only if the volume is empty.
  PHP runs as the docroot owner (`setUIDMode 2`) = `www-data`, matching the volume ownership.
- **mariadb** — `mariadb:11`, data in the `db-data` volume.

### Post-deploy notes (OLS)
- LiteSpeed Cache detects the server automatically; enable **Cache → Browser Cache** in the
  plugin to restore the long static-asset TTL the old Apache conf provided.
- WebAdmin console: port 7080 (not exposed publicly; reach via SSH tunnel if ever needed).

## Deploy on Coolify
1. New Resource → **Docker Compose** → connect this repo (`daseknahri/ads1`, branch `main`).
2. Coolify reads `docker-compose.yml`. It auto-generates the DB credentials
   (`SERVICE_USER_MYSQL`, `SERVICE_PASSWORD_MYSQL`, `SERVICE_PASSWORD_MYSQLROOT`) and the
   public domain (`SERVICE_FQDN_WORDPRESS_80`).
3. Set the domain to `https://getemoji.site` and deploy.

## Env vars (Coolify magic variables — do not hardcode secrets)
| Variable | Provided by | Notes |
|---|---|---|
| `SERVICE_USER_MYSQL` | Coolify (auto) | DB user, shared by both services |
| `SERVICE_PASSWORD_MYSQL` | Coolify (auto) | DB password |
| `SERVICE_PASSWORD_MYSQLROOT` | Coolify (auto) | DB root password |
| `MYSQL_DATABASE` | optional | defaults to `wordpress` |
| `WORDPRESS_TABLE_PREFIX` | optional | defaults to `wp_` |

## Migration notes
- The `.wpress` export of the old site is imported through
  `wp-admin` → All-in-One WP Migration → Import, at the final domain, so no URL
  search-replace is needed.
- DNS for `getemoji.site` is flipped to the Coolify VPS **after** the import is verified.
