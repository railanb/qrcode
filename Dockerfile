FROM php:7.4-cli

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libpng-dev \
    && docker-php-ext-install gd pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json /app/composer.json

RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

COPY . /app

RUN mkdir -p /app/storage /app/public/uploads /app/public/generated \
    && chown -R www-data:www-data /app/storage /app/public/uploads /app/public/generated \
    && { \
      echo "upload_max_filesize=8M"; \
      echo "post_max_size=8M"; \
      echo "max_file_uploads=20"; \
    } > /usr/local/etc/php/conf.d/uploads.ini

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
