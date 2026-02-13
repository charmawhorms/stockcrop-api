# Use an official PHP image with Apache
FROM php:8.2-apache

# Install the MySQL extension so PHP can talk to Clever Cloud
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copy your local files into the container
COPY . /var/www/html/

RUN chmod -R 755 /var/www/html/

# Expose the port Render expects
EXPOSE 80
