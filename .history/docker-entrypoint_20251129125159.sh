#!/bin/bash
set -e

# Navigate to the app directory
cd /var/www/html

# Install npm dependencies if node_modules doesn't exist
if [ ! -d "node_modules" ]; then
    echo "Installing npm dependencies..."
    npm install
fi

# Start npm watch in the background
echo "Starting npm watch for React components..."
npm run watch &

# Start Apache in the foreground
echo "Starting Apache..."
apache2-foreground
