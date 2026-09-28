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
