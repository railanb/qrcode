FROM php:7.4-cli

WORKDIR /app

COPY . /app

RUN mkdir -p /app/storage /app/public/uploads \
    && chown -R www-data:www-data /app/storage /app/public/uploads \
    && { \
      echo "upload_max_filesize=8M"; \
      echo "post_max_size=8M"; \
      echo "max_file_uploads=20"; \
    } > /usr/local/etc/php/conf.d/uploads.ini

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
