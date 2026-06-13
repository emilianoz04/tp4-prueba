# Puedo elegir la version de php que voy a instalar
FROM php:8.1-apache
# Puedo elegir la las librerias
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN apt-get update && \
	apt-get upgrade -y && \
	apt-get install -y git

RUN echo "date.timezone = America/Argentina/Buenos_Aires" > /usr/local/etc/php/php.ini
RUN echo "session.cookie_lifetime=120" >> /usr/local/etc/php/php.ini
RUN echo "session.gc_maxlifetime=120" >> /usr/local/etc/php/php.ini
