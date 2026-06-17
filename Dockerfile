FROM php:8.1-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN docker-php-ext-enable mysqli pdo_mysql
RUN a2enmod rewrite

# Augmenter les limites d'upload PHP (par defaut 2MB, trop petit pour PDF/video)
RUN { \
    echo 'upload_max_filesize = 200M'; \
    echo 'post_max_size = 210M'; \
    echo 'max_execution_time = 300'; \
    echo 'max_input_time = 300'; \
    echo 'memory_limit = 256M'; \
    } > /usr/local/etc/php/conf.d/uploads.ini

COPY . /var/www/html/
RUN mkdir -p /var/www/html/uploads/pdfs /var/www/html/uploads/videos /var/www/html/uploads/avatars \
    && chmod -R 775 /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/
RUN echo '<Directory /var/www/html>\nAllowOverride All\nRequire all granted\n</Directory>' >> /etc/apache2/apache2.conf
EXPOSE 80
CMD ["apache2-foreground"]
