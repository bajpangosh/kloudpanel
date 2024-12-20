#!/bin/bash

# KloudPanel - Simple LiteSpeed WordPress Panel Installation Script
# Version: 1.0.0

# Enable strict error handling
set -Eeuo pipefail
trap 'error_handler $? $LINENO $BASH_LINENO "$BASH_COMMAND" $(printf "::%s" ${FUNCNAME[@]:-})' ERR

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
VHOSTS_DIR="$PANEL_BASE/vhosts"

# Log file
LOG_FILE="/var/log/kloudpanel-install.log"

# Error handler function
error_handler() {
    local exit_code=$1
    local line_no=$2
    log_message "${RED}Error occurred at line $line_no. Exit code: $exit_code${NC}"
    exit $exit_code
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
    
    # Check RAM
    TOTAL_RAM_MB=$(free -m | awk '/^Mem:/{print $2}')
    if [ "$TOTAL_RAM_MB" -lt 512 ]; then
        log_message "${RED}Minimum 512MB RAM required${NC}"
        exit 1
    fi
    
    log_message "${GREEN}System requirements check passed${NC}"
}

# Create base directories
create_directories() {
    log_message "${YELLOW}Creating base directories...${NC}"
    
    mkdir -p "$CONFIG_DIR" "$WWW_DIR" "$LOGS_DIR" "$VHOSTS_DIR"
    chmod 755 "$PANEL_BASE"
    chmod 700 "$CONFIG_DIR"
    
    log_message "${GREEN}Base directories created${NC}"
}

# Install base packages
install_base() {
    log_message "${YELLOW}Installing base packages...${NC}"
    
    # Update system
    apt update
    DEBIAN_FRONTEND=noninteractive apt upgrade -y
    
    # Install basic packages
    DEBIAN_FRONTEND=noninteractive apt install -y \
        wget curl software-properties-common \
        apt-transport-https ca-certificates gnupg \
        lsb-release openssl net-tools ufw python3 \
        python3-pip unzip

    log_message "${GREEN}Base packages installed${NC}"
}

# Install MySQL 5.7
install_mysql() {
    log_message "${YELLOW}Installing MySQL 5.7...${NC}"
    
    # Add MySQL 5.7 repository
    wget https://dev.mysql.com/get/mysql-apt-config_0.8.12-1_all.deb
    DEBIAN_FRONTEND=noninteractive dpkg -i mysql-apt-config_0.8.12-1_all.deb
    apt update
    
    # Install MySQL 5.7
    DEBIAN_FRONTEND=noninteractive apt install -y mysql-server
    
    # Generate root password
    DB_ROOT_PASS=$(openssl rand -base64 24)
    
    # Store credentials
    echo "ROOT_PASSWORD=${DB_ROOT_PASS}" > "$CONFIG_DIR/db.conf"
    chmod 600 "$CONFIG_DIR/db.conf"
    
    # Secure MySQL installation
    mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${DB_ROOT_PASS}';"
    mysql -u root -p"${DB_ROOT_PASS}" -e "DELETE FROM mysql.user WHERE User='';"
    mysql -u root -p"${DB_ROOT_PASS}" -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
    mysql -u root -p"${DB_ROOT_PASS}" -e "DROP DATABASE IF EXISTS test;"
    mysql -u root -p"${DB_ROOT_PASS}" -e "FLUSH PRIVILEGES;"
    
    log_message "${GREEN}MySQL installed successfully${NC}"
}

# Install OpenLiteSpeed
install_litespeed() {
    log_message "${YELLOW}Installing OpenLiteSpeed...${NC}"
    
    # Add OpenLiteSpeed repository
    wget -O - https://repo.litespeed.sh | bash
    
    # Install OpenLiteSpeed
    DEBIAN_FRONTEND=noninteractive apt install -y openlitespeed
    
    # Set admin password
    ADMIN_PASS=$(openssl rand -base64 12)
    echo "admin:$(openssl passwd -apr1 $ADMIN_PASS)" > /usr/local/lsws/admin/conf/htpasswd
    chmod 600 /usr/local/lsws/admin/conf/htpasswd
    
    # Install PHP 8.0
    DEBIAN_FRONTEND=noninteractive apt install -y \
        lsphp80 lsphp80-common lsphp80-mysql \
        lsphp80-opcache lsphp80-curl lsphp80-json \
        lsphp80-imagick lsphp80-intl lsphp80-gd
    
    # Create PHP symlink
    ln -sf /usr/local/lsws/lsphp80/bin/lsphp /usr/local/lsws/fcgi-bin/lsphp
    
    # Set PHP timezone
    sed -i "s|;date.timezone =|date.timezone = $(cat /etc/timezone)|" \
        /usr/local/lsws/lsphp80/etc/php/8.0/litespeed/php.ini
    
    # Start OpenLiteSpeed
    systemctl enable lsws
    systemctl start lsws
    
    log_message "${GREEN}OpenLiteSpeed and PHP installed successfully${NC}"
    log_message "Admin username: admin"
    log_message "Admin password: $ADMIN_PASS"
}

# Configure Firewall
setup_firewall() {
    log_message "${YELLOW}Configuring firewall...${NC}"
    
    # Reset UFW
    ufw --force reset
    
    # Allow SSH and web ports
    ufw allow 22/tcp
    ufw allow 80/tcp
    ufw allow 443/tcp
    ufw allow 7080/tcp
    ufw allow 8443/tcp
    
    # Enable firewall
    echo "y" | ufw enable
    
    log_message "${GREEN}Firewall configured${NC}"
}

# Install WP-CLI
install_wpcli() {
    log_message "${YELLOW}Installing WP-CLI...${NC}"
    
    curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
    chmod +x wp-cli.phar
    mv wp-cli.phar /usr/local/bin/wp
    
    log_message "${GREEN}WP-CLI installed${NC}"
}

# Main installation
main() {
    clear
    echo "KloudPanel Installation"
    echo "======================="
    
    check_requirements
    create_directories
    install_base
    install_mysql
    install_litespeed
    install_wpcli
    setup_firewall
    
    echo "======================="
    echo "Installation Complete!"
    echo "======================="
    echo "KloudPanel URL: https://$(hostname -I | awk '{print $1}'):8443"
    echo "OpenLiteSpeed Admin URL: https://$(hostname -I | awk '{print $1}'):7080"
    echo "Admin username: admin"
    echo "Admin password: $ADMIN_PASS"
    echo "MySQL Root Password: $DB_ROOT_PASS"
    echo "Installation log: $LOG_FILE"
    echo "======================="
}

# Start installation
main
