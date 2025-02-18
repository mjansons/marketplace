FROM phpstorm/php-apache:8.2-xdebug3.2

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN apt-get update && apt-get -y install libzip-dev libicu-dev
RUN docker-php-ext-install pdo pdo_mysql zip mysqli
RUN docker-php-ext-enable pdo pdo_mysql zip mysqli

# Enable mod_rewrite for URL rewriting
RUN a2enmod rewrite

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow .htaccess overrides for URL rewriting
RUN sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

COPY . /var/www/html
