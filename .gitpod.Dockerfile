# Use Gitpod's full workspace as the base image
FROM gitpod/workspace-full

# Set the shell to Bash
SHELL ["/bin/bash", "-c"]

# Install curl to download necessary packages
RUN sudo apt-get update && sudo apt-get install -y curl

# Create the directory for the apt keyrings and add the DDEV GPG key
RUN sudo install -m 0755 -d /etc/apt/keyrings
RUN curl -fsSL https://pkg.ddev.com/apt/gpg.key | gpg --dearmor | sudo tee /etc/apt/keyrings/ddev.gpg > /dev/null
RUN sudo chmod a+r /etc/apt/keyrings/ddev.gpg

# Add the DDEV APT repository to the system
RUN echo "deb [signed-by=/etc/apt/keyrings/ddev.gpg] https://pkg.ddev.com/apt/ * *" | sudo tee /etc/apt/sources.list.d/ddev.list >/dev/null

# Update package information and install DDEV
RUN sudo apt-get update && sudo apt-get install -y ddev

# Optionally, you can add other steps like installing additional software
# RUN sudo apt-get install -y <other-packages>

# Set a working directory (optional, for your custom project)
# WORKDIR /workspace

# Optionally, copy your source code into the container (if you have a project to work on)
# COPY . /workspace

# Expose any necessary ports (optional, depending on your application)
# EXPOSE 8080

# Set the default command (optional, depending on your needs)
# CMD ["your-command"]

