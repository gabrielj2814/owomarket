# ==========================================
# Etapa 1: Node.js - Compilar Frontend (Vite/Inertia/React)
# ==========================================
FROM node:20-alpine AS frontend-builder

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build

# ==========================================
# Etapa 2: PHP Base con Extensiones
# ==========================================
# 8.5, que es la version del entorno de desarrollo y contra la que esta resuelto el
# composer.lock. Con la 8.3 que habia aqui el contenedor construia bien y despues moria en
# CADA peticion — "Your Composer dependencies require a PHP version >= 8.4.0" — que es la
# peor forma de estar roto: parece que funciona hasta que sirve algo.
FROM php:8.5-fpm-alpine AS base

# Instalar dependencias del sistema y extensiones de PHP necesarias para Laravel
RUN apk add --no-cache \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    libzip-dev \
    unzip \
    oniguruma-dev \
    icu-dev \
    icu-libs \
    freetype-dev \
    libjpeg-turbo-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    # Sin `mbstring` ni `opcache`: la imagen oficial YA los trae compilados. Estaban en
    # esta lista, asi que cada build los recompilaba desde el codigo fuente para nada —y
    # mbstring, con toda libmbfl, es con diferencia el compilado mas largo de los ocho.
    # Esa sola linea era la razon de que construir tardara un cuarto de hora.
    && docker-php-ext-install pdo_mysql exif pcntl bcmath gd zip intl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# ==========================================
# Etapa 3: Producción (Empaquetado Completo para Kubernetes/Cloud)
# ==========================================
FROM base AS production

COPY package*.json composer*.json ./

# Copiar el código fuente completo
COPY . .

# Copiar los activos compilados desde la etapa de frontend
COPY --from=frontend-builder /app/public/build ./public/build

# Instalar dependencias de PHP para producción
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Permisos de almacenamiento y cache de Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
