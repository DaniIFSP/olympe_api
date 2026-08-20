FROM richarvey/nginx-php-fpm:3.1.6

COPY . .

#Configurando a aplicação
ENV SKIP_COMPOSER 1
ENV WEBROOT /var/www/html/public
ENV PHP-ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1

#Laravel
ENV APP_ENV production
ENV APP_DEBUG false
ENV LOG_CHANNEL stderr 

#Composer
ENV COMPOSER_ALLOW_SUPERUSER 1

CMD ["/start.sh"]