FROM php:7.4.29-fpm

ENV TZ=Europe/Moscow
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
RUN printf '[Date]\ndate.timezone="%s"\n', $TZ > /usr/local/etc/php/conf.d/tzone.ini
RUN docker-php-ext-install pdo mysqli pdo_mysql

COPY --from=composer /usr/bin/composer /usr/bin/composer
