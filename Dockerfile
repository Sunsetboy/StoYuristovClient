FROM php:8.2-cli-alpine

# Install unzip (required by Composer for package extraction)
RUN apk add --no-cache unzip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy dependency manifests first for better layer caching
COPY composer.json composer.lock ./

RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Copy the rest of the source
COPY . .

CMD ["./vendor/bin/phpunit", "--no-coverage"]

