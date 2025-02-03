FROM node:14 AS build

# Set the working directory
WORKDIR /app

# Copy the package.json and install dependencies
COPY package.json ./
RUN npm install

# Copy the rest of the application code
COPY . .

# Expose the port
EXPOSE 8080

# Create a new image for Apache
FROM httpd:alpine

# Install Node.js dependencies (from the build image)
COPY --from=build /app /usr/local/apache2/htdocs/

# Expose the default HTTP port
EXPOSE 80

CMD ["httpd", "-D", "FOREGROUND"]



