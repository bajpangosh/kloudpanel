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

# Log file
LOG_FILE="/var/log/kloudpanel-install.log"
exec 1> >(tee -a "$LOG_FILE")
exec 2>&1

# Function to log messages
log_message() {
    echo -e "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
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
    
    # Check RAM
    TOTAL_RAM_MB=$(free -m | awk '/^Mem:/{print $2}')
    if [ "$TOTAL_RAM_MB" -lt 2048 ]; then
        log_message "${RED}Minimum 2GB RAM required${NC}"
        exit 1
    fi
    
    # Check CPU cores
    CPU_CORES=$(nproc)
    if [ "$CPU_CORES" -lt 2 ]; then
        log_message "${RED}Minimum 2 CPU cores required${NC}"
        exit 1
    fi
    
    log_message "${GREEN}System requirements check passed${NC}"
}

# Install base packages
install_base() {
    log_message "${YELLOW}Installing base packages...${NC}"
    apt update
    apt install -y wget curl software-properties-common apt-transport-https \
        ca-certificates gnupg lsb-release redis-server python3 python3-pip
}

# Install OpenLiteSpeed
install_litespeed() {
    log_message "${YELLOW}Installing OpenLiteSpeed...${NC}"
    
    # Add OpenLiteSpeed repository
    wget -O - https://repo.litespeed.sh | bash
    
    # Install OpenLiteSpeed
    apt install -y openlitespeed
    
    # Set admin password
    ADMIN_PASS=$(openssl rand -base64 12)
    echo "admin:$(openssl passwd -apr1 $ADMIN_PASS)" > /usr/local/lsws/admin/conf/htpasswd
    
    log_message "${GREEN}OpenLiteSpeed installed successfully${NC}"
    log_message "Admin username: admin"
    log_message "Admin password: $ADMIN_PASS"
}

# Install MariaDB
install_mariadb() {
    log_message "${YELLOW}Installing MariaDB...${NC}"
    
    # Add MariaDB repository
    curl -LsS https://downloads.mariadb.com/MariaDB/mariadb_repo_setup | \
        bash -s -- --mariadb-server-version=10.6
    
    # Install MariaDB
    apt install -y mariadb-server mariadb-client
    
    # Secure installation
    ROOT_PASS=$(openssl rand -base64 24)
    mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '${ROOT_PASS}';"
    mysql -e "DELETE FROM mysql.user WHERE User='';"
    mysql -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
    mysql -e "DROP DATABASE IF EXISTS test;"
    mysql -e "FLUSH PRIVILEGES;"
    
    log_message "${GREEN}MariaDB installed successfully${NC}"
    log_message "Root password: $ROOT_PASS"
}

# Install PHP
install_php() {
    log_message "${YELLOW}Installing PHP...${NC}"
    
    # Install PHP 8.1
    apt install -y lsphp81 lsphp81-common lsphp81-mysql lsphp81-opcache \
        lsphp81-curl lsphp81-json lsphp81-xml lsphp81-zip lsphp81-redis \
        lsphp81-imagick lsphp81-intl lsphp81-gd
    
    # Create symbolic link
    ln -sf /usr/local/lsws/lsphp81/bin/lsphp /usr/local/lsws/fcgi-bin/lsphp
    
    log_message "${GREEN}PHP installed successfully${NC}"
}

# Setup KloudPanel
setup_kloudpanel() {
    log_message "${YELLOW}Setting up KloudPanel...${NC}"
    
    # Create directories
    mkdir -p /usr/local/kloudpanel/{bin,config,templates,www,logs}
    
    # Set permissions
    chown -R nobody:nogroup /usr/local/kloudpanel
    chmod -R 755 /usr/local/kloudpanel
    
    # Create configuration
    cat > /usr/local/kloudpanel/config/panel.conf << EOF
[panel]
version = 1.0.0
port = 8443
ssl = true

[database]
host = localhost
port = 3306
user = kloudpanel
name = kloudpanel

[paths]
www = /usr/local/kloudpanel/www
logs = /usr/local/kloudpanel/logs
templates = /usr/local/kloudpanel/templates
EOF
    
    # Create systemd service
    cat > /etc/systemd/system/kloudpanel.service << EOF
[Unit]
Description=KloudPanel LiteSpeed Control Panel
After=network.target

[Service]
Type=simple
User=root
ExecStart=/usr/local/kloudpanel/bin/kloudpanel
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
EOF
    
    systemctl daemon-reload
    systemctl enable kloudpanel
    
    log_message "${GREEN}KloudPanel setup completed${NC}"
}

# Configure firewall
setup_firewall() {
    log_message "${YELLOW}Configuring firewall...${NC}"
    
    apt install -y ufw
    ufw default deny incoming
    ufw default allow outgoing
    ufw allow 22/tcp
    ufw allow 80/tcp
    ufw allow 443/tcp
    ufw allow 7080/tcp  # OpenLiteSpeed Admin
    ufw allow 8443/tcp  # KloudPanel
    
    echo "y" | ufw enable
    
    log_message "${GREEN}Firewall configured${NC}"
}

# Main installation
main() {
    clear
    echo "KloudPanel Installation"
    echo "======================="
    
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
