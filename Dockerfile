# Use the official FrankenPHP Alpine image with PHP 8.3
FROM dunglas/frankenphp:1-php8.3-alpine

# Install system dependencies and ca-certificates for secure cloud database connections (TiDB)
RUN apk add --no-cache ca-certificates

# Install required PHP extensions for MySQL and performance
RUN install-php-extensions mysqli pdo_mysql opcache

# Apply production PHP configuration
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    echo "session.save_path = /tmp" >> "$PHP_INI_DIR/php.ini" && \
    echo "upload_max_filesize = 10M" >> "$PHP_INI_DIR/php.ini" && \
    echo "post_max_size = 12M" >> "$PHP_INI_DIR/php.ini" && \
    echo "memory_limit = 256M" >> "$PHP_INI_DIR/php.ini"

# Set application directory
WORKDIR /app

# Copy application files
COPY . /app

# Copy custom Caddyfile configuration
COPY Caddyfile /etc/caddy/Caddyfile

# Ensure runtime data directories exist and are writable
RUN mkdir -p /app/data/announcements /app/data/chat /app/data/debug /app/data/votes /tmp && \
    chmod -R 777 /app/data /tmp

# Default dynamic port for Vercel (overridden at runtime by Vercel's PORT environment variable)
ENV PORT=80

# Run FrankenPHP with custom Caddyfile
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
