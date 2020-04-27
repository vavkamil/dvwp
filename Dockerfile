FROM wordpress:4.7.1

COPY ./otherz /var/www/html/
COPY ./plugins /var/www/html/wp-content/plugins