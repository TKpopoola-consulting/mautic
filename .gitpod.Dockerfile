# Use a Node.js base image
FROM node:14

# Install any other dependencies you need here
RUN apt-get update && apt-get install -y curl

# Set the working directory
WORKDIR /app

# Copy your app's source code into the container
COPY . .

# Install app dependencies
RUN npm install

# Expose the necessary port for the app (e.g., for a web app, usually 8080)
EXPOSE 8080

# Run the application
CMD ["npm", "start"]
