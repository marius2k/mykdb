# Folosim o imagine oficială PHP cu serverul Apache preinstalat.
# Alege versiunea de PHP potrivită pentru proiectul tău (ex: 8.1, 8.0, 7.4).
FROM php:8.1-apache

# Instalează extensiile PHP necesare.
# `mysqli` este pentru conectarea la MySQL/MariaDB.
# Poți adăuga și alte extensii de care are nevoie aplicația ta (ex: pdo_mysql, gd, zip).
RUN apt-get update \
	&& apt-get install -y unzip git libzip-dev \
	&& docker-php-ext-install mysqli pdo pdo_mysql zip \
	&& docker-php-ext-enable mysqli pdo_mysql zip

# Copiază codul sursă al aplicației tale în directorul web al serverului Apache din container.
# Punctul (.) reprezintă directorul curent (unde se află Dockerfile-ul).
# /var/www/html este directorul rădăcină al serverului Apache în container.
COPY . /var/www/html/

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer require dompdf/dompdf

# (Opțional) Setează permisiunile corecte pentru directorul web.
# Acest lucru poate fi necesar pentru ca aplicația să poată scrie fișiere (ex: upload-uri).
RUN chown -R www-data:www-data /var/www/html

