FROM php:8.4-cli-trixie

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    postgresql-client \
    && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions \
    grpc \
    protobuf \
    pdo_pgsql \
    pgsql \
    intl \
    opcache \
    pcntl \
    sockets

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY --from=ghcr.io/roadrunner-server/roadrunner:2025 /usr/bin/rr /usr/local/bin/rr

WORKDIR /app

COPY .container/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
