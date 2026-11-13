FROM php:8.3-apache

ARG PHP_MEMORY_LIMIT=256M
ARG PHP_MAX_EXECUTION_TIME=60
ARG PHP_UPLOAD_MAX_FILESIZE=10M
ARG PHP_POST_MAX_SIZE=12M

# Extensiones para conectar con MySQL (PDO y mysqli)
RUN docker-php-ext-install pdo_mysql mysqli \
    && a2enmod rewrite

# Límites de PHP (las subidas de imágenes dependen de upload_max_filesize y post_max_size)
RUN { \
      echo "memory_limit=${PHP_MEMORY_LIMIT}"; \
      echo "max_execution_time=${PHP_MAX_EXECUTION_TIME}"; \
      echo "upload_max_filesize=${PHP_UPLOAD_MAX_FILESIZE}"; \
      echo "post_max_size=${PHP_POST_MAX_SIZE}"; \
      echo "date.timezone=Europe/Madrid"; \
    } > /usr/local/etc/php/conf.d/clinica.ini
