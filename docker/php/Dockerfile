FROM dunglas/frankenphp:1-php8.3

# Install system dependencies & PHP extensions for Laravel + PostgreSQL + Redis
RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    redis \
    pcntl \
    bcmath \
    intl \
    zip \
    gd \
    opcache

# Set Caddy configuration for Octane
ENV SERVER_NAME=":8000"

WORKDIR /app

# Copy application files
COPY . /app

# Run FrankenPHP with Octane worker
CMD ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=8000"]
