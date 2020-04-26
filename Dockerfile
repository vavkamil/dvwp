FROM wordpress:php7.1-apache

COPY ./otherz /var/www/html/
COPY ./plugins /var/www/html/wp-content/plugins