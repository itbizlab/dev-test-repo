# PHP image for EIDOC (Web Push / minishlink requires curl, mbstring, openssl, gmp or bcmath).
FROM php:8.2-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    libcurl4-openssl-dev \
    libicu-dev \
    libgmp-dev \
    libonig-dev \
    libpq-dev \
    && docker-php-ext-configure gmp \
    && docker-php-ext-install -j$(nproc) mysqli pdo pdo_mysql pdo_pgsql curl mbstring opcache bcmath gmp \
    && rm -rf /var/lib/apt/lists/*
