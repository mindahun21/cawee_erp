FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    libpq-dev \
    gnupg

# Install Node.js 20 (Vite 7 requires Node >=20)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install and configure PHP extensions
RUN docker-php-ext-configure intl \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd pdo_pgsql pgsql intl zip

# Install Redis extension (using specific version to avoid channel issues)
RUN pecl install redis-6.0.2 \
    && docker-php-ext-enable redis

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Expose port and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
