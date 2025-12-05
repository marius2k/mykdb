#!/bin/bash

################################################################################
# MYKDB Docker Migration - BACKUP Script
# This script creates a complete backup of your mykdb Docker setup
# Run this on the SOURCE machine (current machine)
################################################################################

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}MYKDB Migration Backup Script${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# Configuration
PROJECT_DIR=$(pwd)
BACKUP_DIR="${PROJECT_DIR}/migration_backup"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_NAME="mykdb_backup_${TIMESTAMP}"

echo -e "${YELLOW}📁 Project Directory: ${PROJECT_DIR}${NC}"
echo -e "${YELLOW}💾 Backup Directory: ${BACKUP_DIR}${NC}"
echo ""

# Create backup directory
mkdir -p "${BACKUP_DIR}"

echo -e "${GREEN}[1/6] Checking Docker containers...${NC}"
if ! docker ps --filter "name=mykdb" --format "{{.Names}}" | grep -q "mykdb"; then
    echo -e "${RED}❌ No mykdb containers are running!${NC}"
    echo -e "${YELLOW}Please start containers with: docker-compose up -d${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Containers are running${NC}"
echo ""

echo -e "${GREEN}[2/6] Creating MySQL database dump...${NC}"
docker exec mykdb-db mysqldump -u root --all-databases --routines --triggers --events > "${BACKUP_DIR}/${BACKUP_NAME}_database.sql"
DUMP_SIZE=$(du -h "${BACKUP_DIR}/${BACKUP_NAME}_database.sql" | cut -f1)
echo -e "${GREEN}✅ Database dump created (${DUMP_SIZE})${NC}"
echo ""

echo -e "${GREEN}[3/6] Backing up Docker volume...${NC}"
docker run --rm \
  -v mykdb_db_data:/data \
  -v "${BACKUP_DIR}":/backup \
  alpine tar czf "/backup/${BACKUP_NAME}_volume.tar.gz" -C /data .
VOLUME_SIZE=$(du -h "${BACKUP_DIR}/${BACKUP_NAME}_volume.tar.gz" | cut -f1)
echo -e "${GREEN}✅ Volume backup created (${VOLUME_SIZE})${NC}"
echo ""

echo -e "${GREEN}[4/6] Copying project files...${NC}"
# Exclude unnecessary files
tar czf "${BACKUP_DIR}/${BACKUP_NAME}_project.tar.gz" \
  --exclude='node_modules' \
  --exclude='vendor' \
  --exclude='.git' \
  --exclude='migration_backup' \
  --exclude='logs' \
  --exclude='.history' \
  -C "${PROJECT_DIR}/.." $(basename "${PROJECT_DIR}")
PROJECT_SIZE=$(du -h "${BACKUP_DIR}/${BACKUP_NAME}_project.tar.gz" | cut -f1)
echo -e "${GREEN}✅ Project files archived (${PROJECT_SIZE})${NC}"
echo ""

echo -e "${GREEN}[5/6] Creating migration info file...${NC}"
cat > "${BACKUP_DIR}/${BACKUP_NAME}_info.txt" << EOF
MYKDB Migration Backup Information
===================================
Backup Date: $(date)
Source Machine: $(hostname)
Source User: $(whoami)
Source Path: ${PROJECT_DIR}

Docker Information:
-------------------
Docker Version: $(docker --version)
Docker Compose Version: $(docker-compose --version)

Database Information:
---------------------
Database Name: knowledge_db
Database Size: $(sudo du -sh /var/lib/docker/volumes/mykdb_db_data/_data 2>/dev/null | cut -f1 || echo "N/A")
MySQL Version: $(docker exec mykdb-db mysql --version)

Containers Status:
------------------
$(docker ps --filter "name=mykdb" --format "{{.Names}}\t{{.Status}}\t{{.Ports}}")

Network Information:
--------------------
$(docker network ls | grep mykdb)

Volume Information:
-------------------
$(docker volume ls | grep mykdb)

Files in this backup:
---------------------
${BACKUP_NAME}_database.sql     - MySQL database dump
${BACKUP_NAME}_volume.tar.gz    - Docker volume data
${BACKUP_NAME}_project.tar.gz   - Project source code
${BACKUP_NAME}_info.txt         - This file
restore_on_new_machine.sh       - Restoration script

Instructions:
-------------
1. Copy the entire 'migration_backup' folder to the new machine
2. On the new machine, run: bash restore_on_new_machine.sh
3. Follow the prompts
EOF
echo -e "${GREEN}✅ Info file created${NC}"
echo ""

echo -e "${GREEN}[6/6] Creating restore script...${NC}"
cat > "${BACKUP_DIR}/restore_on_new_machine.sh" << 'RESTORE_SCRIPT'
#!/bin/bash

################################################################################
# MYKDB Docker Migration - RESTORE Script
# This script restores the mykdb Docker setup on a NEW machine
# Run this on the DESTINATION machine (new machine)
################################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}MYKDB Migration Restore Script${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# Check if running as root
if [[ $EUID -eq 0 ]]; then
   echo -e "${RED}❌ Do not run this script as root!${NC}"
   echo -e "${YELLOW}Run as regular user with sudo privileges${NC}"
   exit 1
fi

# Find the latest backup files
BACKUP_DIR=$(pwd)
LATEST_BACKUP=$(ls -t ${BACKUP_DIR}/*_database.sql 2>/dev/null | head -1)

if [ -z "$LATEST_BACKUP" ]; then
    echo -e "${RED}❌ No backup files found in current directory!${NC}"
    echo -e "${YELLOW}Make sure you're in the migration_backup folder${NC}"
    exit 1
fi

BACKUP_PREFIX=$(basename "$LATEST_BACKUP" _database.sql)
echo -e "${BLUE}Found backup: ${BACKUP_PREFIX}${NC}"
echo ""

# Function to install Docker
install_docker() {
    echo -e "${YELLOW}Docker is not installed. Would you like to install it now? (y/n)${NC}"
    read -p "> " install_choice
    
    if [[ "$install_choice" != "y" && "$install_choice" != "Y" ]]; then
        echo -e "${RED}❌ Docker is required. Exiting...${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}Installing Docker...${NC}"
    
    # Detect OS
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        OS=$ID
    else
        echo -e "${RED}❌ Cannot detect OS!${NC}"
        exit 1
    fi
    
    case $OS in
        ubuntu|debian)
            echo -e "${BLUE}Detected Ubuntu/Debian${NC}"
            sudo apt-get update
            sudo apt-get install -y apt-transport-https ca-certificates curl software-properties-common
            curl -fsSL https://download.docker.com/linux/$OS/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
            echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/$OS $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
            sudo apt-get update
            sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
            ;;
        fedora|rhel|centos)
            echo -e "${BLUE}Detected Fedora/RHEL/CentOS${NC}"
            sudo dnf -y install dnf-plugins-core
            sudo dnf config-manager --add-repo https://download.docker.com/linux/fedora/docker-ce.repo
            sudo dnf install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
            ;;
        *)
            echo -e "${RED}❌ Unsupported OS: $OS${NC}"
            echo -e "${YELLOW}Please install Docker manually: https://docs.docker.com/engine/install/${NC}"
            exit 1
            ;;
    esac
    
    # Start Docker service
    sudo systemctl start docker
    sudo systemctl enable docker
    
    # Add user to docker group
    sudo usermod -aG docker $USER
    
    echo -e "${GREEN}✅ Docker installed successfully${NC}"
    echo -e "${YELLOW}⚠️  You need to log out and back in for group changes to take effect${NC}"
    echo -e "${YELLOW}After logging back in, run this script again.${NC}"
    exit 0
}

# Function to install Docker Compose (standalone)
install_docker_compose() {
    echo -e "${GREEN}Installing Docker Compose...${NC}"
    
    # Try docker compose plugin first
    if docker compose version >/dev/null 2>&1; then
        echo -e "${GREEN}✅ Docker Compose (plugin) is already available${NC}"
        return 0
    fi
    
    # Install standalone docker-compose
    COMPOSE_VERSION=$(curl -s https://api.github.com/repos/docker/compose/releases/latest | grep 'tag_name' | cut -d\" -f4)
    sudo curl -L "https://github.com/docker/compose/releases/download/${COMPOSE_VERSION}/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    sudo chmod +x /usr/local/bin/docker-compose
    
    if command -v docker-compose >/dev/null 2>&1; then
        echo -e "${GREEN}✅ Docker Compose installed successfully${NC}"
    else
        echo -e "${RED}❌ Failed to install Docker Compose${NC}"
        exit 1
    fi
}

# Check prerequisites
echo -e "${GREEN}[1/9] Checking prerequisites...${NC}"

# Check Docker
if ! command -v docker >/dev/null 2>&1; then
    echo -e "${YELLOW}⚠️  Docker is not installed${NC}"
    install_docker
else
    echo -e "${GREEN}✅ Docker is installed${NC}"
    
    # Check if user is in docker group
    if ! groups | grep -q docker; then
        echo -e "${YELLOW}⚠️  User is not in docker group${NC}"
        echo -e "${YELLOW}Adding user to docker group...${NC}"
        sudo usermod -aG docker $USER
        echo -e "${YELLOW}⚠️  Please log out and back in, then run this script again${NC}"
        exit 0
    fi
fi

# Check Docker Compose
if ! command -v docker-compose >/dev/null 2>&1 && ! docker compose version >/dev/null 2>&1; then
    echo -e "${YELLOW}⚠️  Docker Compose is not installed${NC}"
    install_docker_compose
else
    echo -e "${GREEN}✅ Docker Compose is installed${NC}"
fi

echo ""

# Ask for target directory
echo -e "${YELLOW}Where do you want to install mykdb?${NC}"
read -p "Enter full path (e.g., /home/user/projects/mykdb): " TARGET_DIR

if [ -z "$TARGET_DIR" ]; then
    echo -e "${RED}❌ No directory specified!${NC}"
    exit 1
fi

# Create target directory
echo -e "${GREEN}[2/8] Creating project directory...${NC}"
mkdir -p "$TARGET_DIR"
echo -e "${GREEN}✅ Directory created: ${TARGET_DIR}${NC}"
echo ""

# Extract project files
echo -e "${GREEN}[3/8] Extracting project files...${NC}"
tar xzf "${BACKUP_PREFIX}_project.tar.gz" -C "$(dirname "$TARGET_DIR")"
echo -e "${GREEN}✅ Project files extracted${NC}"
echo ""

# Create Docker volume
echo -e "${GREEN}[4/8] Creating Docker volume...${NC}"
docker volume create mykdb_db_data
echo -e "${GREEN}✅ Volume created${NC}"
echo ""

# Restore volume data
echo -e "${GREEN}[5/8] Restoring database volume...${NC}"
docker run --rm \
  -v mykdb_db_data:/data \
  -v "${BACKUP_DIR}":/backup \
  alpine tar xzf "/backup/${BACKUP_PREFIX}_volume.tar.gz" -C /data
echo -e "${GREEN}✅ Volume data restored${NC}"
echo ""

# Start containers
echo -e "${GREEN}[6/8] Starting Docker containers...${NC}"
cd "$TARGET_DIR"
docker-compose up -d
echo -e "${GREEN}✅ Containers started${NC}"
echo ""

# Wait for database to be ready
echo -e "${GREEN}[7/8] Waiting for database to be ready...${NC}"
for i in {1..30}; do
    if docker exec mykdb-db mysqladmin ping -u root --silent >/dev/null 2>&1; then
        echo -e "${GREEN}✅ Database is ready${NC}"
        break
    fi
    echo -n "."
    sleep 2
done
echo ""

# Verify restoration
echo -e "${GREEN}[8/8] Verifying restoration...${NC}"
echo ""
echo -e "${BLUE}Container Status:${NC}"
docker ps --filter "name=mykdb" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
echo ""

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}✅ Migration Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${YELLOW}Access your application at:${NC}"
echo -e "  📱 Web App: ${GREEN}http://localhost:8080${NC}"
echo -e "  🗄️  phpMyAdmin: ${GREEN}http://localhost:8081${NC}"
echo ""
echo -e "${YELLOW}Useful commands:${NC}"
echo -e "  View logs: ${BLUE}docker-compose logs -f${NC}"
echo -e "  Stop containers: ${BLUE}docker-compose down${NC}"
echo -e "  Restart containers: ${BLUE}docker-compose restart${NC}"
echo ""
RESTORE_SCRIPT

chmod +x "${BACKUP_DIR}/restore_on_new_machine.sh"
echo -e "${GREEN}✅ Restore script created${NC}"
echo ""

# Summary
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}✅ Backup Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${BLUE}Backup Location: ${BACKUP_DIR}${NC}"
echo ""
echo -e "${YELLOW}Files created:${NC}"
ls -lh "${BACKUP_DIR}/${BACKUP_NAME}"* | awk '{print "  📄 " $9 " (" $5 ")"}'
ls -lh "${BACKUP_DIR}/restore_on_new_machine.sh" | awk '{print "  🔧 " $9 " (" $5 ")"}'
echo ""
TOTAL_SIZE=$(du -sh "${BACKUP_DIR}" | cut -f1)
echo -e "${BLUE}Total Backup Size: ${TOTAL_SIZE}${NC}"
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo -e "  1. Copy the '${GREEN}migration_backup${NC}' folder to your new machine"
echo -e "  2. On the new machine, run: ${GREEN}cd migration_backup && bash restore_on_new_machine.sh${NC}"
echo -e "  3. Follow the prompts"
echo ""
echo -e "${YELLOW}Transfer command example:${NC}"
echo -e "  ${BLUE}scp -r ${BACKUP_DIR} user@new-machine:/path/to/destination/${NC}"
echo ""
