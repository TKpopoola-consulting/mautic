# Use a PHP or Node base image (depending on your application's requirements)
FROM php:7.4-apache

# Install necessary dependencies (example: curl)
RUN apt-get update && apt-get install -y curl

# Copy your application files into the image
COPY . /var/www/html/

# Expose necessary ports
EXPOSE 80

# Define the default command to run
CMD ["apache2-foreground"]

