# ==========================================
# Etapa 1: PHP Base con Extensiones
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

# Node y npm EN LA MISMA IMAGEN que PHP, no en una etapa `node:alpine` aparte.
#
# No es comodidad: este proyecto usa Wayfinder, y su plugin de Vite ejecuta
# `php artisan wayfinder:generate` durante el `vite build` para escribir
# `resources/js/actions`, `routes` y `wayfinder` — que estan en .gitignore, o sea
# que NO existen hasta que alguien los genera. Un contenedor solo-Node no tiene
# `php` ni `vendor/`, asi que el build muere ahi. El frontend de esta aplicacion
# no se puede compilar sin PHP al lado.
RUN apk add --no-cache nodejs npm

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# ==========================================
# Etapa 2: Compilar el Frontend (Vite/Inertia/React + Wayfinder)
# ==========================================
FROM base AS frontend-builder

WORKDIR /var/www

# `vendor/` primero: sin el, el `php artisan wayfinder:generate` que dispara el
# build de Vite no arranca. --no-scripts porque los scripts de Laravel tocan
# storage/ y aqui solo hace falta el autoloader.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && npm run build

# ==========================================
# Etapa 3: Producción (Empaquetado Completo para Kubernetes/Cloud)
# ==========================================
FROM base AS production

# Copiar el código fuente completo
COPY . .

# Copiar los activos compilados desde la etapa de frontend
COPY --from=frontend-builder /var/www/public/build ./public/build

# Y los ficheros que Wayfinder genera durante ese build: estan en .gitignore, asi
# que el `COPY . .` de arriba no los trae y sin ellos la aplicacion no resuelve
# sus propias rutas en el cliente.
COPY --from=frontend-builder /var/www/resources/js/actions ./resources/js/actions
COPY --from=frontend-builder /var/www/resources/js/routes ./resources/js/routes
COPY --from=frontend-builder /var/www/resources/js/wayfinder ./resources/js/wayfinder

# Instalar dependencias de PHP para producción
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Permisos de almacenamiento y cache de Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
