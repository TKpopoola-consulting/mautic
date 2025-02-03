# Use a PHP base image with Apache
FROM php:7.4-apache

# Install necessary dependencies (example: curl)
RUN apt-get update && apt-get install -y curl

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy your application files into the image
COPY . /var/www/html/

# Expose necessary ports
EXPOSE 80

# Define the default command to run
CMD ["apache2-foreground"]


