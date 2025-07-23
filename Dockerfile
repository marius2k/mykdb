# Folosim o imagine oficială PHP cu serverul Apache preinstalat.
# Alege versiunea de PHP potrivită pentru proiectul tău (ex: 8.1, 8.0, 7.4).
FROM php:8.1-apache

# Instalează extensiile PHP necesare.
# `mysqli` este pentru conectarea la MySQL/MariaDB.
# Poți adăuga și alte extensii de care are nevoie aplicația ta (ex: pdo_mysql, gd, zip).
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli pdo_mysql

# Copiază codul sursă al aplicației tale în directorul web al serverului Apache din container.
# Punctul (.) reprezintă directorul curent (unde se află Dockerfile-ul).
# /var/www/html este directorul rădăcină al serverului Apache în container.
COPY . /var/www/html/

# (Opțional) Setează permisiunile corecte pentru directorul web.
# Acest lucru poate fi necesar pentru ca aplicația să poată scrie fișiere (ex: upload-uri).
RUN chown -R www-data:www-data /var/www/html

