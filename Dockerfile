FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    nginx supervisor unzip git curl libzip-dev libpng-dev libonig-dev libxml2-dev zip libjpeg-dev libfreetype6-dev libwebp-dev libxpm-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-xpm \
    && docker-php-ext-install pdo_mysql zip bcmath gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN mkdir -p public/uploads && chown www-data:www-data public/uploads && chmod -R 775 public/uploads

RUN composer install --no-dev --optimize-autoloader

COPY ./nginx.conf /etc/nginx/nginx.conf

COPY ./supervisord.conf /etc/supervisord.conf

RUN chown -R www-data:www-data /var/www

EXPOSE 80
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]

