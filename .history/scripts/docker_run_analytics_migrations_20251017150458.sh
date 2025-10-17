#!/bin/bash
# Run all analytics database migrations in the Docker container

echo "Running analytics migrations in Docker container..."
docker exec -it mykdb-webapp bash /var/www/html/scripts/run_analytics_migrations.sh

echo "Migration complete!"