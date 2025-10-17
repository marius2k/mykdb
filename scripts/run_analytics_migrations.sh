#!/bin/bash
# Run all analytics database migrations in the Docker container

echo "Running analytics database migrations..."

# Change to the application root directory
cd /var/www/html

# Run all migration scripts
echo "===== Running search analytics migration ====="
php scripts/add_search_analytics.php

echo "===== Running file downloads table migration ====="
php scripts/add_file_downloads_table.php

echo "===== All migrations completed! ====="