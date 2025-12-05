# MYKDB Docker Migration Guide

## 📋 Overview

This guide will help you migrate your entire MYKDB Docker setup (application + database) from one machine to another.

## 🎯 What Gets Backed Up

- ✅ MySQL database (220MB of data)
- ✅ Docker volume (persistent data)
- ✅ Project source code
- ✅ Docker configuration (docker-compose.yml)
- ✅ All application files and uploads

## 🚀 Quick Start

### On Current Machine (Source)

```bash
# 1. Navigate to your project
cd /home/marius/work/projects/mykdb

# 2. Run the backup script
./backup_for_migration.sh

# 3. Wait for completion (creates migration_backup folder)
```

### Transfer to New Machine

```bash
# Option A: Using SCP
scp -r migration_backup user@new-machine:/path/to/destination/

# Option B: Using rsync (faster for large files)
rsync -avz --progress migration_backup user@new-machine:/path/to/destination/

# Option C: Manual transfer
# - ZIP the migration_backup folder
# - Transfer via USB, network share, or cloud storage
```

### On New Machine (Destination)

```bash
# 1. Install Docker and Docker Compose (if not installed)
# For Ubuntu/Debian:
sudo apt-get update
sudo apt-get install docker.io docker-compose

# 2. Add your user to docker group
sudo usermod -aG docker $USER
# Log out and back in for changes to take effect

# 3. Navigate to the backup folder
cd /path/to/migration_backup

# 4. Run the restore script
bash restore_on_new_machine.sh

# 5. Follow the prompts (it will ask where to install)
```

## 📁 Backup Contents

After running `backup_for_migration.sh`, you'll get:

```
migration_backup/
├── mykdb_backup_YYYYMMDD_HHMMSS_database.sql    # MySQL dump
├── mykdb_backup_YYYYMMDD_HHMMSS_volume.tar.gz   # Docker volume
├── mykdb_backup_YYYYMMDD_HHMMSS_project.tar.gz  # Source code
├── mykdb_backup_YYYYMMDD_HHMMSS_info.txt        # Migration info
└── restore_on_new_machine.sh                     # Restore script
```

## ⚙️ What the Scripts Do

### backup_for_migration.sh

1. ✅ Checks if containers are running
2. ✅ Creates MySQL database dump
3. ✅ Backs up Docker volume data
4. ✅ Archives project files (excludes node_modules, .git, logs)
5. ✅ Creates migration info file
6. ✅ Generates restore script

### restore_on_new_machine.sh (Auto-generated)

1. ✅ Checks Docker installation
2. ✅ Creates project directory
3. ✅ Extracts project files
4. ✅ Creates Docker volume
5. ✅ Restores volume data
6. ✅ Starts containers
7. ✅ Waits for database
8. ✅ Verifies installation

## 🔍 Verification Steps

After restoration on new machine:

```bash
# Check containers are running
docker ps --filter "name=mykdb"

# Check database
docker exec mykdb-db mysql -u root -e "SHOW DATABASES;"

# Check volume
docker volume ls | grep mykdb

# Test web access
curl http://localhost:8080
```

## 🌐 Access URLs (on new machine)

- 📱 **Web Application**: http://localhost:8080
- 🗄️ **phpMyAdmin**: http://localhost:8081

## 🛠️ Manual Commands (If Needed)

### Stop containers before backup
```bash
docker-compose down
```

### Start containers after restore
```bash
docker-compose up -d
```

### View logs
```bash
docker-compose logs -f
```

### Check database size
```bash
docker exec mykdb-db mysql -u root -e "
SELECT table_schema AS 'Database',
  ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
FROM information_schema.tables
WHERE table_schema = 'knowledge_db'
GROUP BY table_schema;"
```

## 🔧 Troubleshooting

### Issue: "Permission denied" when running scripts
```bash
chmod +x backup_for_migration.sh
# Or on new machine:
chmod +x restore_on_new_machine.sh
```

### Issue: "Cannot connect to Docker daemon"
```bash
# Start Docker service
sudo systemctl start docker

# Enable Docker to start on boot
sudo systemctl enable docker
```

### Issue: Port already in use
```bash
# Check what's using the port
sudo lsof -i :8080

# Change ports in docker-compose.yml if needed
# Edit the ports section:
# ports:
#   - "8090:80"  # Changed from 8080 to 8090
```

### Issue: Database not ready
```bash
# Wait longer and check logs
docker logs mykdb-db

# Manually restart database
docker-compose restart db
```

## 📝 Important Notes

- ⚠️ Containers must be **running** when you create the backup
- ⚠️ Ensure you have **enough disk space** (~500MB minimum)
- ⚠️ The restore script will ask where to install (you can choose any path)
- ⚠️ Backup files contain **sensitive data** - transfer securely
- ⚠️ Docker volumes are machine-independent (portable across systems)

## 🔐 Security Considerations

- The backup includes your database with all user data
- MySQL is configured with empty password (for development)
- For production, set proper passwords in docker-compose.yml:
  ```yaml
  environment:
    MYSQL_ROOT_PASSWORD: "your_secure_password"
  ```

## 📊 Expected Sizes

Based on your current setup:

- Database dump: ~220MB
- Volume backup: ~250MB (compressed)
- Project files: ~50MB (compressed)
- **Total**: ~520MB

## 🎓 Advanced Options

### Backup only database (no files)
```bash
docker exec mykdb-db mysqldump -u root knowledge_db > db_only.sql
```

### Restore only database (if files already there)
```bash
docker exec -i mykdb-db mysql -u root knowledge_db < db_only.sql
```

### Clone volume directly (if both machines accessible)
```bash
docker run --rm \
  -v mykdb_db_data:/from \
  -v new_db_data:/to \
  alpine sh -c "cd /from && cp -av . /to"
```

## 📞 Support

If you encounter issues:
1. Check the `*_info.txt` file in backup folder for system details
2. Review Docker logs: `docker-compose logs`
3. Verify disk space: `df -h`
4. Check Docker status: `systemctl status docker`

## ✅ Success Checklist

- [ ] Backup script completed without errors
- [ ] migration_backup folder created with all files
- [ ] Transferred backup to new machine
- [ ] Docker installed on new machine
- [ ] Restore script completed successfully
- [ ] Containers running (check with `docker ps`)
- [ ] Web application accessible
- [ ] Database contains all data
- [ ] Uploads folder contains files

---

**Created**: December 4, 2025  
**For**: MYKDB Knowledge Base System  
**Version**: 1.0
