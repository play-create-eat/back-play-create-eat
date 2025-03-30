# Use the official PHP image as the base image
FROM php:8.3-fpm

# Set environment variables to avoid interactive prompts
ENV DEBIAN_FRONTEND=noninteractive
ENV INSTALL_NO_STRIP=1

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libonig-dev \
    libzip-dev \
    libpq-dev \
    supervisor \
    redis \
    redis-tools \
    libicu-dev \
    libexif-dev \
    g++ \
    make \
    autoconf \
    ghostscript \
    imagemagick \
    libmagickwand-dev --no-install-recommends

RUN sed -i 's/<policy domain="coder" rights="none" pattern="PDF" \/>/<policy domain="coder" rights="read|write" pattern="PDF" \/>/' /etc/ImageMagick-6/policy.xml || true

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
        intl \
        exif && \
    docker-php-ext-configure exif --enable-exif && \
    pecl install redis && docker-php-ext-enable redis && \
    pecl install imagick && docker-php-ext-enable imagick

# Install and enable Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Copy Xdebug configuration file
COPY ./docker/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Create Supervisor log directory
RUN mkdir -p /var/log/supervisor && chmod -R 777 /var/run /var/log/supervisor

# Copy Supervisor configurations
COPY ./docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY ./docker/supervisor/queue-worker.conf /etc/supervisor/conf.d/queue-worker.conf
COPY ./docker/supervisor/laravel-scheduler.conf /etc/supervisor/conf.d/laravel-scheduler.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose ports
EXPOSE 9000

# Set Supervisor as the entrypoint
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
