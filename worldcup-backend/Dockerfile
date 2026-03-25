# ─────────────────────────────────────────
# Stage 1 : builder — install dependencies
# ─────────────────────────────────────────
FROM php:8.2-fpm-alpine AS builder

RUN apk add --no-cache \
    icu-dev \
    oniguruma-dev \
    && docker-php-ext-install pdo pdo_mysql intl mbstring opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && php bin/console cache:warmup --env=prod || true

# ─────────────────────────────────────────
# Stage 2 : production
# ─────────────────────────────────────────
FROM php:8.2-fpm-alpine AS production

RUN apk add --no-cache \
    icu-dev \
    oniguruma-dev \
    && docker-php-ext-install pdo pdo_mysql intl mbstring opcache

WORKDIR /app

COPY --from=builder /app .

# Utilisateur non-root
RUN adduser -D appuser && chown -R appuser:appuser /app/var
USER appuser

EXPOSE 9000

CMD ["php-fpm"]
