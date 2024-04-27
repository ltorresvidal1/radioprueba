FROM amd64/ubuntu:latest

RUN apt-get -y update && \
    DEBIAN_FRONTEND=noninteractive apt-get -y install tzdata && \
    ln -fs /usr/share/zoneinfo/America/Bogota /etc/localtime && \
    dpkg-reconfigure --frontend noninteractive tzdata

RUN apt-get -y update && \
    apt-get -y install nginx software-properties-common
RUN ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

RUN add-apt-repository ppa:ondrej/php
RUN apt-get update

RUN apt-get install -y php8.1-fpm php8.1-gd php8.1-pdo-pgsql php8.1-zip php8.1-curl php8.1-xml composer npm nano
RUN apt-get install -y supervisor
RUN mkdir -p /var/log/supervisor

COPY . /var/www/html

RUN cd /var/www/html && composer install


RUN rm -f /var/www/html/.env
RUN mv /var/www/html/.envdocker /var/www/html/.env

RUN cd /var/www/html && npm install && npm run build

RUN chmod -R 777 /var/www/html/bootstrap/cache

# Limpieza de la caché de Laravel
RUN cd /var/www/html php artisan cache:clear
RUN cd /var/www/html php artisan view:clear
RUN cd /var/www/html php artisan config:clear

# Copiamos la configuración de Nginx
COPY nginx/default /etc/nginx/sites-available/default

# Generación de certificado autofirmado para HTTPS
RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /etc/ssl/private/nginx-selfsigned.key -out /etc/ssl/certs/nginx-selfsigned.crt -subj "/C=US/ST=State/L=City/O=Organization/CN=olser.site"

# Ajustes para habilitar HTTPS en Nginx
#RUN sed -i '/listen 80 default_server;/a \    listen 443 ssl default_server;' /etc/nginx/sites-available/default
#RUN sed -i '/listen \[::\]:80 default_server;/a \    listen \[::\]:443 ssl default_server;' /etc/nginx/sites-available/default
#RUN sed -i '/server_name _;/a \    ssl_certificate /etc/ssl/certs/nginx-selfsigned.crt;' /etc/nginx/sites-available/default
#RUN sed -i '/server_name _;/a \    ssl_certificate_key /etc/ssl/private/nginx-selfsigned.key;' /etc/nginx/sites-available/default


EXPOSE 80
EXPOSE 443


# Copia el archivo de configuración de Supervisor
COPY supervisor/* /etc/supervisor/conf.d/
RUN chown -R www-data:www-data /var/www/html/storage

# Añade los comandos para iniciar los servicios de NGINX, PHP-FPM y Supervisor
CMD service php8.1-fpm start && supervisord -n