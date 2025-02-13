# Use an official PHP runtime as a parent image
FROM php:7.4-apache

# Install any necessary extensions or dependencies
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Set the working directory in the container
WORKDIR /var/www/html

# Copy the current directory contents into the container
COPY . .

# Expose port 80 to access the app
EXPOSE 80

# Start Apache in the foreground
CMD ["apache2-foreground"]

