ARG NGINX_VERSION=1.15
ARG PHP_BUILD_VERSION
### H2 PROXY
FROM alpine:latest AS symfony_docker_h2-proxy-cert

RUN apk add --no-cache openssl

# Use this self-generated certificate only in dev, IT IS NOT SECURE!
RUN openssl genrsa -des3 -passout pass:NotSecure -out server.pass.key 2048
RUN openssl rsa -passin pass:NotSecure -in server.pass.key -out server.key
RUN rm server.pass.key
RUN openssl req -new -passout pass:NotSecure -key server.key -out server.csr \
    -subj '/C=SS/ST=SS/L=Gotham City/O=Symfony/CN=localhost'
RUN openssl x509 -req -sha256 -days 365 -in server.csr -signkey server.key -out server.crt

FROM nginx:${NGINX_VERSION}-alpine AS symfony_docker_h2-proxy

RUN mkdir -p /etc/nginx/ssl/
COPY --from=symfony_docker_h2-proxy-cert server.key server.crt /etc/nginx/ssl/
COPY ./docker/h2-proxy/default.conf /etc/nginx/conf.d/default.conf



# Composer on correct PHP version
FROM php:${PHP_BUILD_VERSION}-cli as build
ARG BUILD_ROOT_PATH
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN apt-get update
RUN apt-get install -y zip unzip curl git wget gnupg
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir=/usr/bin --filename=composer
RUN php -r "unlink('composer-setup.php');"

#install gpg and phive
RUN wget -O phive.phar https://phar.io/releases/phive.phar && \
    wget -O phive.phar.asc https://phar.io/releases/phive.phar.asc

RUN chmod +x phive.phar && \
    mv phive.phar /usr/local/bin/phive
RUN curl -sL https://deb.nodesource.com/setup_14.x | bash -
RUN apt-get install -y nodejs

WORKDIR ${BUILD_ROOT_PATH}
COPY . ./
