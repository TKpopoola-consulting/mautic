# Step 1: Use the official PHP image
FROM php:7.4-apache

# Step 2: Install dependencies and enable Apache modules
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

# Step 3: Set the working directory
WORKDIR /var/www/html

# Step 4: Copy your application files into the container
COPY . /var/www/html/

# Step 5: Set permissions for Apache
RUN chown -R www-data:www-data /var/www/html

# Step 6: Expose the Apache HTTP port
EXPOSE 80

# Step 7: Restart Apache in the foreground
CMD ["apache2-foreground"]


