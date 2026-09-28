FROM node:22-bookworm

ENV DEBIAN_FRONTEND=noninteractive
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        unzip \
        sqlite3 \
        php8.2-cli \
        php8.2-bcmath \
        php8.2-curl \
        php8.2-mbstring \
        php8.2-xml \
        php8.2-zip \
        php8.2-sqlite3 \
        php8.2-intl \
        php8.2-opcache \
        python3 \
        python3-pip \
        python3-venv \
        tesseract-ocr \
        libgl1 \
        libglib2.0-0 \
        libsm6 \
        libxext6 \
        libxrender1 \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

RUN composer dump-autoload --optimize --no-interaction \
    && php artisan package:discover --ansi

RUN python3 -m venv /opt/ocr-venv \
    && /opt/ocr-venv/bin/pip install --no-cache-dir --upgrade pip \
    && /opt/ocr-venv/bin/pip install --no-cache-dir -r ocr/requirements.txt

COPY docker/php.ini /etc/php/8.2/cli/conf.d/99-kmu.ini

ENV OCR_PYTHON_BIN=/opt/ocr-venv/bin/python
ENV TESSERACT_CMD=/usr/bin/tesseract

RUN mkdir -p storage/app/tmp-ocr storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && php artisan optimize:clear

EXPOSE 8000 5173

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
