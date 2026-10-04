FROM wordpress:php8.3-apache

# curl is used by the compose healthcheck and is handy for debugging.
RUN apt-get update \
    && apt-get install -y --no-install-recommends curl \
    && rm -rf /var/lib/apt/lists/*

# Raise PHP limits so large migration packages (All-in-One WP Migration .wpress)
# import cleanly. WordPress/Apache here uses mod_php, so this ini is honored.
RUN { \
      echo 'upload_max_filesize = 1024M'; \
      echo 'post_max_size = 1024M'; \
      echo 'memory_limit = 512M'; \
      echo 'max_execution_time = 900'; \
      echo 'max_input_time = 900'; \
      echo 'max_input_vars = 5000'; \
    } > /usr/local/etc/php/conf.d/zz-uploads.ini

# Browser caching for static files (fonts a year; images, CSS and JS a month; JS/CSS URLs carry ?ver=).
COPY docker/site-cache.conf /etc/apache2/conf-available/site-cache.conf
RUN a2enmod expires headers deflate && a2enconf site-cache

# Site mu-plugins (YouTube click-to-play facade), copied into the wp-content volume on every start.
COPY mu-plugins/ /opt/site/mu-plugins/
COPY docker/ge-entrypoint.sh /usr/local/bin/ge-entrypoint.sh
RUN chmod +x /usr/local/bin/ge-entrypoint.sh
ENTRYPOINT ["ge-entrypoint.sh"]
CMD ["apache2-foreground"]
