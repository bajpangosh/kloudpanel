#!/bin/bash

# KloudPanel - LiteSpeed Hosting Control Panel Installation Script
# Version: 1.0.0

# Enable error handling
set -e
trap 'echo "Error occurred at line $LINENO. Exit code: $?"; exit 1' ERR

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Base paths
PANEL_BASE="/usr/local/kloudpanel"
CONFIG_DIR="$PANEL_BASE/config"
WWW_DIR="$PANEL_BASE/www"
LOGS_DIR="$PANEL_BASE/logs"
TEMPLATES_DIR="$PANEL_BASE/templates"
BIN_DIR="$PANEL_BASE/bin"

# Log file
LOG_FILE="/var/log/kloudpanel-install.log"

# Create base directories
create_directories() {
    log_message "${YELLOW}Creating base directories...${NC}"
    
    # Create all required directories
    mkdir -p "$CONFIG_DIR"
    mkdir -p "$WWW_DIR"
    mkdir -p "$LOGS_DIR"
    mkdir -p "$TEMPLATES_DIR"
    mkdir -p "$BIN_DIR"
    
    # Set proper permissions
    chown -R nobody:nogroup "$PANEL_BASE"
    chmod -R 755 "$PANEL_BASE"
    
    # Create log file with proper permissions
    touch "$LOG_FILE"
    chmod 644 "$LOG_FILE"
    
    log_message "${GREEN}Base directories created${NC}"
}

# Function to log messages
log_message() {
    echo -e "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Check system requirements
check_requirements() {
    log_message "${YELLOW}Checking system requirements...${NC}"
    
    # Check if root
    if [ "$EUID" -ne 0 ]; then
        log_message "${RED}Please run as root${NC}"
        exit 1
    fi
    
    # Check OS
    if [ ! -f /etc/os-release ] || ! grep -q "Ubuntu 22.04" /etc/os-release; then
        log_message "${RED}This script requires Ubuntu 22.04 LTS${NC}"
        exit 1
    fi
    
    # Check RAM and create swap if needed
    TOTAL_RAM_MB=$(free -m | awk '/^Mem:/{print $2}')
    if [ "$TOTAL_RAM_MB" -lt 512 ]; then
        log_message "${RED}Minimum 512MB RAM required${NC}"
        exit 1
    fi
    
    # Create swap if RAM is less than 1GB
    if [ "$TOTAL_RAM_MB" -lt 1024 ]; then
        log_message "${YELLOW}Creating swap space for low memory system...${NC}"
        
        # Remove existing swap file if it exists
        swapoff /swapfile 2>/dev/null || true
        rm -f /swapfile 2>/dev/null || true
        
        # Create 1GB swap file
        dd if=/dev/zero of=/swapfile bs=1M count=1024
        chmod 600 /swapfile
        mkswap /swapfile
        swapon /swapfile
        
        # Add to fstab if not already there
        if ! grep -q "/swapfile" /etc/fstab; then
            echo '/swapfile none swap sw 0 0' >> /etc/fstab
        fi
        
        log_message "${GREEN}Swap space created successfully${NC}"
    fi
    
    log_message "${GREEN}System requirements check passed${NC}"
}

# Install base packages
install_base() {
    log_message "${YELLOW}Installing base packages...${NC}"
    
    # Update package lists
    apt update
    
    # Install Python 3.10 (Ubuntu 22.04 default)
    apt install -y python3.10 python3.10-venv python3-pip
    
    # Install required packages for Ubuntu 22.04
    apt install -y wget curl software-properties-common apt-transport-https \
        ca-certificates gnupg lsb-release redis-server acl \
        openssl net-tools ufw
    
    log_message "${GREEN}Base packages installed${NC}"
}

# Install OpenLiteSpeed
install_litespeed() {
    log_message "${YELLOW}Installing OpenLiteSpeed...${NC}"
    
    # Remove any existing repository
    rm -f /etc/apt/sources.list.d/litespeed.list
    
    # Add OpenLiteSpeed repository for Ubuntu 22.04
    wget -O - https://repo.litespeed.sh | bash
    
    # Update package lists
    apt update
    
    # Install OpenLiteSpeed
    apt install -y openlitespeed
    
    # Set admin password
    ADMIN_PASS=$(openssl rand -base64 12)
    echo "admin:$(openssl passwd -apr1 $ADMIN_PASS)" > /usr/local/lsws/admin/conf/htpasswd
    
    # Set proper permissions for Ubuntu 22.04
    chown -R nobody:nogroup /usr/local/lsws/admin/conf/htpasswd
    chmod 600 /usr/local/lsws/admin/conf/htpasswd
    
    # Start and enable service
    systemctl start lsws
    systemctl enable lsws
    
    log_message "${GREEN}OpenLiteSpeed installed successfully${NC}"
    log_message "Admin username: admin"
    log_message "Admin password: $ADMIN_PASS"
}

# Install MariaDB
install_mariadb() {
    log_message "${YELLOW}Installing MariaDB...${NC}"
    
    # Install MariaDB (Ubuntu 22.04 repository version)
    apt install -y mariadb-server mariadb-client
    
    # Start MariaDB service
    systemctl start mariadb
    systemctl enable mariadb
    
    # Generate passwords
    ROOT_PASS=$(openssl rand -base64 24)
    PANEL_DB_PASS=$(openssl rand -base64 24)
    
    # Wait for MariaDB to be ready
    sleep 5
    
    # Set root password for Ubuntu 22.04 MariaDB
    mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '${ROOT_PASS}';"
    
    # Create .my.cnf for root access
    cat > /root/.my.cnf << EOF
[client]
user=root
password=${ROOT_PASS}
EOF
    chmod 600 /root/.my.cnf
    
    # Secure the installation
    mysql --user=root --password="${ROOT_PASS}" << EOF
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
DROP DATABASE IF EXISTS test;
FLUSH PRIVILEGES;
EOF
    
    # Create KloudPanel database and user
    mysql --user=root --password="${ROOT_PASS}" << EOF
CREATE DATABASE IF NOT EXISTS kloudpanel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'kloudpanel'@'localhost' IDENTIFIED BY '${PANEL_DB_PASS}';
GRANT ALL PRIVILEGES ON kloudpanel.* TO 'kloudpanel'@'localhost';
FLUSH PRIVILEGES;
EOF
    
    # Remove .my.cnf after setup
    rm -f /root/.my.cnf
    
    # Store database credentials
    cat > "$CONFIG_DIR/db.conf" << EOF
ROOT_PASSWORD=${ROOT_PASS}
PANEL_DB_PASSWORD=${PANEL_DB_PASS}
EOF
    chmod 600 "$CONFIG_DIR/db.conf"
    
    log_message "${GREEN}MariaDB installed successfully${NC}"
    log_message "Root password: $ROOT_PASS"
    log_message "Panel DB password: $PANEL_DB_PASS"
}

# Install PHP
install_php() {
    log_message "${YELLOW}Installing PHP...${NC}"
    
    # Install PHP 8.1 (Ubuntu 22.04 compatible version)
    apt install -y lsphp81 lsphp81-common lsphp81-mysql lsphp81-opcache \
        lsphp81-curl lsphp81-json lsphp81-xml lsphp81-zip lsphp81-redis \
        lsphp81-imagick lsphp81-intl lsphp81-gd lsphp81-cli
    
    # Create symbolic link
    ln -sf /usr/local/lsws/lsphp81/bin/lsphp /usr/local/lsws/fcgi-bin/lsphp
    
    # Set PHP timezone
    sed -i "s|;date.timezone =|date.timezone = $(cat /etc/timezone)|" /usr/local/lsws/lsphp81/etc/php/8.1/litespeed/php.ini
    
    # Restart OpenLiteSpeed
    systemctl restart lsws
    
    log_message "${GREEN}PHP installed successfully${NC}"
}

# Setup KloudPanel
setup_kloudpanel() {
    log_message "${YELLOW}Setting up KloudPanel...${NC}"
    
    # Create panel configuration
    cat > "$CONFIG_DIR/panel.conf" << EOF
[panel]
version = 1.0.0
port = 8443
ssl = true

[database]
host = localhost
port = 3306
user = kloudpanel
password = ${PANEL_DB_PASS}
name = kloudpanel

[paths]
www = ${WWW_DIR}
logs = ${LOGS_DIR}
templates = ${TEMPLATES_DIR}
EOF
    
    # Create systemd service with proper Ubuntu 22.04 dependencies
    cat > /etc/systemd/system/kloudpanel.service << EOF
[Unit]
Description=KloudPanel LiteSpeed Control Panel
After=network.target mariadb.service lsws.service
Requires=mariadb.service lsws.service

[Service]
Type=simple
User=root
Environment=PYTHONUNBUFFERED=1
ExecStart=/usr/bin/python3 ${BIN_DIR}/kloudpanel
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
EOF
    
    # Reload systemd and enable service
    systemctl daemon-reload
    systemctl enable kloudpanel
    
    log_message "${GREEN}KloudPanel setup completed${NC}"
}

# Configure firewall
setup_firewall() {
    log_message "${YELLOW}Configuring firewall...${NC}"
    
    # Install UFW if not present
    apt install -y ufw
    
    # Reset UFW to default state
    ufw --force reset
    
    # Configure UFW
    ufw default deny incoming
    ufw default allow outgoing
    ufw allow 22/tcp comment 'SSH'
    ufw allow 80/tcp comment 'HTTP'
    ufw allow 443/tcp comment 'HTTPS'
    ufw allow 7080/tcp comment 'OpenLiteSpeed Admin'
    ufw allow 8443/tcp comment 'KloudPanel'
    
    # Enable UFW non-interactively
    echo "y" | ufw enable
    
    log_message "${GREEN}Firewall configured${NC}"
}

# Main installation
main() {
    clear
    echo "KloudPanel Installation"
    echo "======================="
    
    # Create directories first
    create_directories
    
    # Then proceed with installation
    check_requirements
    install_base
    install_litespeed
    install_mariadb
    install_php
    setup_kloudpanel
    setup_firewall
    
    echo "======================="
    echo "Installation Complete!"
    echo "======================="
    echo "KloudPanel URL: https://$(hostname -I | awk '{print $1}'):8443"
    echo "OpenLiteSpeed Admin URL: https://$(hostname -I | awk '{print $1}'):7080"
    echo "Admin username: admin"
    echo "Admin password: $ADMIN_PASS"
    echo "MariaDB Root Password: $ROOT_PASS"
    echo "Installation log: $LOG_FILE"
    echo "======================="
}

# Start installation
main
