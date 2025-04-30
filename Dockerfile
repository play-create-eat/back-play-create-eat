FROM php:8.3-fpm

ENV DEBIAN_FRONTEND=noninteractive
ENV INSTALL_NO_STRIP=1

WORKDIR /var/www/html

# Fix possible issues with apt
RUN apt-get update && apt-get install -y --no-install-recommends \
    apt-utils \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install dependencies in smaller groups to isolate problematic packages
RUN apt-get update && apt-get install -y --no-install-recommends \
    autoconf \
    build-essential \
    ca-certificates \
    curl \
    git \
    gnupg \
    libcurl4-openssl-dev \
    libicu-dev \
    libidn2-dev \
    libonig-dev \
    libpng-dev \
    libpq-dev \
    libssl-dev \
    libxml2-dev \
    libxslt-dev \
    libzip-dev \
    locales \
    make \
    nano \
    unzip \
    vim \
    wget \
    zip \
    && rm -rf /var/lib/apt/lists/*

# Install fonts and font-related packages
RUN apt-get update && apt-get install -y --no-install-recommends \
    fonts-dejavu \
    fonts-freefont-ttf \
    fonts-liberation \
    fontconfig \
    && rm -rf /var/lib/apt/lists/*

# Install image processing tools
RUN apt-get update && apt-get install -y --no-install-recommends \
    jpegoptim \
    optipng \
    pngquant \
    && rm -rf /var/lib/apt/lists/*

# Try installing ImageMagick without Ghostscript
RUN apt-get update && apt-get install -y --no-install-recommends \
    imagemagick \
    libmagickcore-dev \
    libmagickwand-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libexif-dev \
    && rm -rf /var/lib/apt/lists/*

# Install Redis and supervisor
RUN apt-get update && apt-get install -y --no-install-recommends \
    redis \
    redis-tools \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Install Chrome/Puppeteer dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libasound2 \
    libatk-bridge2.0-0 \
    libgbm1 \
    libgtk-3-0 \
    libnss3 \
    libxss1 \
    libx11-xcb1 \
    libxcomposite1 \
    libxcursor1 \
    libxdamage1 \
    libxi6 \
    libxtst6 \
    libglib2.0-0 \
    libcups2 \
    libxrandr2 \
    libatk1.0-0 \
    libpangocairo-1.0-0 \
    && rm -rf /var/lib/apt/lists/*

# Fix ImageMagick policy to allow PDF processing
RUN if [ -f /etc/ImageMagick-6/policy.xml ]; then \
    sed -i 's/<policy domain="coder" rights="none" pattern="PDF" \/>/<policy domain="coder" rights="read|write" pattern="PDF" \/>/' /etc/ImageMagick-6/policy.xml; \
    fi

# Install Node
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

RUN node -v && npm -v

# Install Puppeteer globally and install Chrome
# Install Puppeteer globally and install Chrome
RUN npm install -g puppeteer
RUN npx puppeteer browsers install chrome-headless-shell

# Make sure Chrome can be found and executed properly - with proper path detection
RUN mkdir -p /var/www/.cache/puppeteer \
    && chown -R www-data:www-data /var/www/.cache \
    && chmod -R 777 /var/www/.cache/puppeteer \
    && find /usr/local/lib/node_modules -name puppeteer -type d | xargs -I{} chmod -R 777 {} || echo "Puppeteer directory not found in expected location" \
    && chmod -R 777 /usr/local/lib/node_modules || echo "Node modules directory not accessible"

# Make sure Chrome can be found and executed properly
RUN mkdir -p /var/www/.cache/puppeteer
RUN chown -R www-data:www-data /var/www/.cache
RUN chmod -R 777 /var/www/.cache/puppeteer

# Install Imagick PHP extension
RUN git clone https://github.com/Imagick/imagick.git \
    && cd imagick \
    && phpize \
    && ./configure \
    && make \
    && make install \
    && docker-php-ext-enable imagick \
    && cd .. && rm -rf imagick

# Copy Xdebug configuration
COPY ./docker/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Prepare supervisor directories
RUN mkdir -p /var/log/supervisor \
    && chmod -R 777 /var/run /var/log/supervisor

# Install PHP extensions
RUN docker-php-ext-configure exif --enable-exif \
    && docker-php-ext-install exif gd pcntl pdo pdo_pgsql pgsql zip intl

# Install PECL extensions
RUN pecl install redis xdebug \
    && docker-php-ext-enable redis xdebug

# Copy supervisor configurations
COPY ./docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY ./docker/supervisor/queue-worker.conf /etc/supervisor/conf.d/queue-worker.conf
COPY ./docker/supervisor/laravel-scheduler.conf /etc/supervisor/conf.d/laravel-scheduler.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Find and display Chrome path for debugging
RUN echo "Chrome executable location:" && \
    find /usr/local/lib/node_modules -name chrome-linux -type d || \
    find /usr/local/lib/node_modules -name chrome-headless-shell -type f || \
    echo "Chrome not found in expected locations"

# Create a symbolic link to make Chrome easily accessible
RUN mkdir -p /usr/bin/chromium && \
    ln -sf $(find /usr/local/lib/node_modules -name chrome-headless-shell -type f | head -n 1) /usr/bin/chromium/chrome || \
    echo "Could not create symbolic link to Chrome"

EXPOSE 9000

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
