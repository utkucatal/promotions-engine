FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libpng-dev libonig-dev libxml2-dev libzip-dev libpq-dev \
    && docker-php-ext-install \
        pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

ENV SYMFONY_HOME=/tmp/.symfony
ENV HOME=/tmp

WORKDIR /var/www/symfony

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
