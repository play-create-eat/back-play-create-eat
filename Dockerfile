# Use the official PHP image as the base image
FROM php:8.3-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN docker-php-ext-configure zip \
    && docker-php-ext-configure pgsql --with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    zip \
    exif \
    pcntl \
    bcmath \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring zip exif pcntl bcmath

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Copy Xdebug configuration file
COPY ./docker/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Create Supervisor log directory
RUN chmod -R 777 /var/run /var/log/supervisor


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
