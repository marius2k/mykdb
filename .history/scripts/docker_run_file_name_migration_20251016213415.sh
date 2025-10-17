#!/bin/bash
# Run file_name column migration in Docker container

echo "Running file_name column migration in Docker container..."

# Execute the SQL file directly
docker exec -i mykdb-webapp mysql -u$MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE < /home/marius/work/projects/mykdb/sql/migrations/add_file_name_column.sql

echo "Migration complete!"