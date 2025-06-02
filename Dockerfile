FROM php:8.3-cli

RUN docker-php-ext-install bcmath

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


RUN apt-get update && apt-get install -y \
    bash \
    unzip \
    vim \
    && rm -rf /var/lib/apt/lists/*

# Ustaw katalog roboczy
WORKDIR /app

CMD ["php", "-a"]