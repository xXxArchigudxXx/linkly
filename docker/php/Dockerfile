FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    curl \
    git \
    zip \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    oniguruma-dev

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    mysqli \
    zip \
    gd \
    mbstring \
    xml \
    opcache \
    bcmath

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Create non-root user
RUN addgroup -g 1000 appuser \
    && adduser -u 1000 -G appuser -s /bin/sh -D appuser

# Set permissions
RUN chown -R appuser:appuser /var/www/html

# Switch to non-root user
USER appuser

# Expose port
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]
