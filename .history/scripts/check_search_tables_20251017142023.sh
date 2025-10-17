#!/bin/bash
# Check search analytics table structure

echo "Checking search_queries table structure..."
docker exec -i mykdb-webapp mysql -uroot knowledge_db -e "DESCRIBE search_queries;"

echo ""
echo "Checking search_result_clicks table structure..."
docker exec -i mykdb-webapp mysql -uroot knowledge_db -e "DESCRIBE search_result_clicks;"

echo ""
echo "Checking if tables exist..."
docker exec -i mykdb-webapp mysql -uroot knowledge_db -e "SHOW TABLES LIKE 'search%';"
