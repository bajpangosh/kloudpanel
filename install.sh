#!/bin/bash

# KloudPanel - LiteSpeed Hosting Control Panel Installation Script
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
TEMPLATES_DIR="$PANEL_BASE/templates"
BIN_DIR="$PANEL_BASE/bin"

# Log file
LOG_FILE="/var/log/kloudpanel-install.log"

# Error handler function
error_handler() {
    local exit_code=$1
    local line_no=$2
    local bash_lineno=$3
    local last_command=$4
    local func_stack=$5
    log_message "${RED}Error occurred in:"
    log_message "  Exit code: $exit_code"
    log_message "  Line number: $line_no"
    log_message "  Command: $last_command"
    log_message "  Function stack: $func_stack${NC}"
    exit $exit_code
}

# Function to check if a command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to check if a service is running
service_is_running() {
    systemctl is-active --quiet "$1"
}

# Function to check if a port is available
port_is_available() {
    ! netstat -tuln | grep -q ":$1 "
}

# Function to log messages
log_message() {
    echo -e "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Create base directories
create_directories() {
    log_message "${YELLOW}Creating base directories...${NC}"
    
    # Create all required directories
    for dir in "$CONFIG_DIR" "$WWW_DIR" "$LOGS_DIR" "$TEMPLATES_DIR" "$BIN_DIR"; do
        if ! mkdir -p "$dir"; then
            log_message "${RED}Failed to create directory: $dir${NC}"
            return 1
        fi
    done
    
    # Set proper permissions
    chown -R nobody:nogroup "$PANEL_BASE" || return 1
    chmod -R 755 "$PANEL_BASE" || return 1
    
    # Create log file with proper permissions
    touch "$LOG_FILE" || return 1
    chmod 644 "$LOG_FILE" || return 1
    
    log_message "${GREEN}Base directories created successfully${NC}"
}

# Install base packages
install_base() {
    log_message "${YELLOW}Installing base packages...${NC}"
    
    # Check if apt is available
    if ! command_exists apt; then
        log_message "${RED}apt package manager not found${NC}"
        return 1
    }
    
    # Update package lists
    apt update || return 1
    
    # Install Python 3.10 (Ubuntu 22.04 default)
    DEBIAN_FRONTEND=noninteractive apt install -y python3.10 python3.10-venv python3-pip || return 1
    
    # Install required packages for Ubuntu 22.04
    DEBIAN_FRONTEND=noninteractive apt install -y wget curl software-properties-common \
        apt-transport-https ca-certificates gnupg lsb-release redis-server acl \
        openssl net-tools ufw || return 1
    
    log_message "${GREEN}Base packages installed successfully${NC}"
}

# Install OpenLiteSpeed
install_litespeed() {
    log_message "${YELLOW}Installing OpenLiteSpeed...${NC}"
    
    # Check if port 8088 is available
    if ! port_is_available 8088; then
        log_message "${RED}Port 8088 is already in use${NC}"
        return 1
    fi
    
    # Remove any existing repository
    rm -f /etc/apt/sources.list.d/litespeed.list
    
    # Add OpenLiteSpeed repository
    if ! wget -O - https://repo.litespeed.sh | bash; then
        log_message "${RED}Failed to add OpenLiteSpeed repository${NC}"
        return 1
    fi
    
    # Update package lists
    apt update || return 1
    
    # Install OpenLiteSpeed
    DEBIAN_FRONTEND=noninteractive apt install -y openlitespeed || return 1
    
    # Set admin password
    ADMIN_PASS=$(openssl rand -base64 12)
    if ! echo "admin:$(openssl passwd -apr1 $ADMIN_PASS)" > /usr/local/lsws/admin/conf/htpasswd; then
        log_message "${RED}Failed to set admin password${NC}"
        return 1
    fi
    
    # Set proper permissions
    chown -R nobody:nogroup /usr/local/lsws/admin/conf/htpasswd || return 1
    chmod 600 /usr/local/lsws/admin/conf/htpasswd || return 1
    
    # Start OpenLiteSpeed service
    systemctl start litespeed || return 1
    systemctl enable litespeed || return 1
    
    # Verify service is running
    if ! service_is_running litespeed; then
        log_message "${RED}OpenLiteSpeed service failed to start${NC}"
        return 1
    fi
    
    log_message "${GREEN}OpenLiteSpeed installed successfully${NC}"
    log_message "Admin username: admin"
    log_message "Admin password: $ADMIN_PASS"
}

# Install MariaDB
install_mariadb() {
    log_message "${YELLOW}Installing MariaDB...${NC}"
    
    # Check if port 3306 is available
    if ! port_is_available 3306; then
        log_message "${RED}Port 3306 is already in use${NC}"
        return 1
    fi
    
    # Install MariaDB
    DEBIAN_FRONTEND=noninteractive apt install -y mariadb-server mariadb-client || return 1
    
    # Start MariaDB service
    systemctl start mariadb || return 1
    systemctl enable mariadb || return 1
    
    # Verify service is running
    if ! service_is_running mariadb; then
        log_message "${RED}MariaDB service failed to start${NC}"
        return 1
    fi
    
    # Wait for MariaDB to be ready
    sleep 5
    
    # Generate passwords
    ROOT_PASS=$(openssl rand -base64 24)
    PANEL_DB_PASS=$(openssl rand -base64 24)
    
    # Store database credentials first
    mkdir -p "$CONFIG_DIR"
    if ! cat > "$CONFIG_DIR/db.conf" << EOF
ROOT_PASSWORD=${ROOT_PASS}
PANEL_DB_PASSWORD=${PANEL_DB_PASS}
EOF
    then
        log_message "${RED}Failed to store database credentials${NC}"
        return 1
    fi
    chmod 600 "$CONFIG_DIR/db.conf" || return 1
    
    # Set root password
    if ! mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '${ROOT_PASS}';"; then
        log_message "${RED}Failed to set root password${NC}"
        return 1
    fi
    
    # Create .my.cnf for root access
    if ! cat > /root/.my.cnf << EOF
[client]
user=root
password=${ROOT_PASS}
EOF
    then
        log_message "${RED}Failed to create .my.cnf${NC}"
        return 1
    fi
    chmod 600 /root/.my.cnf || return 1
    
    # Secure the installation
    if ! mysql --user=root --password="${ROOT_PASS}" << EOF
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
DROP DATABASE IF EXISTS test;
FLUSH PRIVILEGES;
EOF
    then
        log_message "${RED}Failed to secure MariaDB installation${NC}"
        return 1
    fi
    
    # Create KloudPanel database and user
    if ! mysql --user=root --password="${ROOT_PASS}" << EOF
CREATE DATABASE IF NOT EXISTS kloudpanel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'kloudpanel'@'localhost' IDENTIFIED BY '${PANEL_DB_PASS}';
GRANT ALL PRIVILEGES ON kloudpanel.* TO 'kloudpanel'@'localhost';
FLUSH PRIVILEGES;
EOF
    then
        log_message "${RED}Failed to create KloudPanel database and user${NC}"
        return 1
    fi
    
    # Remove .my.cnf after setup
    rm -f /root/.my.cnf
    
    log_message "${GREEN}MariaDB installed successfully${NC}"
    log_message "Root password: $ROOT_PASS"
    log_message "Panel DB password: $PANEL_DB_PASS"
}

# Install PHP
install_php() {
    log_message "${YELLOW}Installing PHP...${NC}"
    
    # Install PHP packages
    DEBIAN_FRONTEND=noninteractive apt install -y \
        lsphp81 lsphp81-common lsphp81-mysql lsphp81-opcache \
        lsphp81-curl lsphp81-json lsphp81-xml lsphp81-zip lsphp81-redis \
        lsphp81-imagick lsphp81-intl lsphp81-gd lsphp81-cli || return 1
    
    # Create symbolic link
    ln -sf /usr/local/lsws/lsphp81/bin/lsphp /usr/local/lsws/fcgi-bin/lsphp || return 1
    
    # Set PHP timezone
    if ! sed -i "s|;date.timezone =|date.timezone = $(cat /etc/timezone)|" \
        /usr/local/lsws/lsphp81/etc/php/8.1/litespeed/php.ini; then
        log_message "${RED}Failed to set PHP timezone${NC}"
        return 1
    fi
    
    # Restart OpenLiteSpeed
    systemctl restart litespeed || return 1
    
    # Verify PHP installation
    if ! /usr/local/lsws/lsphp81/bin/php -v > /dev/null; then
        log_message "${RED}PHP installation verification failed${NC}"
        return 1
    fi
    
    log_message "${GREEN}PHP installed successfully${NC}"
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
After=network.target mariadb.service litespeed.service
Requires=mariadb.service litespeed.service

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
    
    # Check if running as root
    if [ "$EUID" -ne 0 ]; then
        log_message "${RED}Please run as root${NC}"
        exit 1
    fi
    
    # Create directories first
    create_directories || exit 1
    
    # Ensure config directory exists and has proper permissions
    mkdir -p "$CONFIG_DIR" || exit 1
    chown root:root "$CONFIG_DIR" || exit 1
    chmod 700 "$CONFIG_DIR" || exit 1
    
    # Run installation steps
    check_requirements || exit 1
    install_base || exit 1
    install_litespeed || exit 1
    install_mariadb || exit 1
    install_php || exit 1
    setup_kloudpanel || exit 1
    setup_firewall || exit 1
    
    # Final permission check
    chown -R nobody:nogroup "$PANEL_BASE" || exit 1
    chmod -R 755 "$PANEL_BASE" || exit 1
    chmod 700 "$CONFIG_DIR" || exit 1
    chmod 600 "$CONFIG_DIR"/*.conf || exit 1
    
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
