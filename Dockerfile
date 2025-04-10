FROM php:8.3-fpm

ENV DEBIAN_FRONTEND=noninteractive
ENV INSTALL_NO_STRIP=1

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y --no-install-recommends \
    autoconf \
    build-essential \
    ca-certificates \
    curl \
    fonts-dejavu \
    fonts-freefont-ttf \
    fonts-liberation \
    fontconfig \
    g++ \
    ghostscript \
    git \
    gnupg \
    imagemagick \
    jpegoptim \
    libasound2 \
    libatk-bridge2.0-0 \
    libcurl4-openssl-dev \
    libexif-dev \
    libfreetype6-dev \
    libgbm1 \
    libgtk-3-0 \
    libicu-dev \
    libidn2-dev \
    libjpeg-dev \
    libmagickcore-dev \
    libmagickwand-dev \
    libnss3 \
    libonig-dev \
    libpng-dev \
    libpq-dev \
    libssl-dev \
    libxml2-dev \
    libxss1 \
    libzip-dev \
    locales \
    make \
    nano \
    optipng \
    pngquant \
    redis \
    redis-tools \
    supervisor \
    unzip \
    vim \
    wget \
    zip \
    && rm -rf /var/lib/apt/lists/*

RUN sed -i 's/<policy domain="coder" rights="none" pattern="PDF" \/>/<policy domain="coder" rights="read|write" pattern="PDF" \/>/' /etc/ImageMagick-6/policy.xml || true

RUN git clone https://github.com/Imagick/imagick.git \
    && cd imagick \
    && phpize \
    && ./configure \
    && make \
    && make install \
    && docker-php-ext-enable imagick \
    && cd .. && rm -rf imagick

COPY ./docker/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

RUN mkdir -p /var/log/supervisor \
    && chmod -R 777 /var/run /var/log/supervisor

RUN docker-php-ext-configure exif --enable-exif \
    && docker-php-ext-install exif gd pcntl pdo pdo_pgsql pgsql zip intl

RUN pecl install redis xdebug \
    && docker-php-ext-enable redis xdebug

COPY ./docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY ./docker/supervisor/queue-worker.conf /etc/supervisor/conf.d/queue-worker.conf
COPY ./docker/supervisor/laravel-scheduler.conf /etc/supervisor/conf.d/laravel-scheduler.conf

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
