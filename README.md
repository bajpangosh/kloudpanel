# KloudPanel - LiteSpeed Hosting Control Panel

KloudPanel is a lightweight, modern hosting control panel specifically designed for LiteSpeed web server. It provides an easy-to-use interface for managing websites, SSL certificates, and server configurations.

![KloudPanel Dashboard](screenshots/dashboard.png)

## Features

- 🚀 **LiteSpeed Web Server Management**
  - Virtual Host Configuration
  - PHP Version Management
  - Cache Settings

- 🔒 **SSL Certificate Management**
  - Auto SSL with Let's Encrypt
  - SSL Installation and Renewal
  - SSL Status Monitoring

- 🌐 **Website Management**
  - Easy Website Creation
  - Domain Management
  - File Manager
  - Backup Management

- 📊 **System Monitoring**
  - CPU Usage
  - Memory Usage
  - Disk Space
  - Server Status

## System Requirements

- Ubuntu 22.04 LTS (Recommended)
- Minimum 2GB RAM
- Minimum 2 CPU Cores
- 20GB Free Disk Space

## Quick Installation

```bash
curl -s https://raw.githubusercontent.com/bajpangosh/kloudpanel/main/install.sh | sudo bash
```

## Access the Panel

After installation:
1. Open your browser and navigate to `https://your-server-ip:8443`
2. Login with the credentials shown at the end of installation
3. Default username is `admin`

## Security Features

- 🔐 SSL/TLS Encryption
- 🛡️ Firewall Configuration
- 🔑 Secure Password Storage
- 📝 Activity Logging
- 🚫 Brute Force Protection

## Components

KloudPanel integrates the following components:

- OpenLiteSpeed Web Server
- MariaDB 10.6
- PHP 8.1
- Redis Cache
- Let's Encrypt SSL

## Directory Structure

```
/usr/local/kloudpanel/
├── bin/           # Binary files
├── config/        # Configuration files
├── logs/          # Log files
├── templates/     # HTML templates
└── www/          # Web interface files
```

## Configuration

The main configuration file is located at:
```
/usr/local/kloudpanel/config/panel.conf
```

## Ports Used

- 8443: KloudPanel Web Interface
- 7080: OpenLiteSpeed Admin
- 80: HTTP
- 443: HTTPS

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Support

If you encounter any issues or need support:
- Open an issue on GitHub
- Check the documentation
- Join our community forum

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Acknowledgments

- OpenLiteSpeed Team
- Let's Encrypt
- MariaDB Team
- PHP Development Team

## Screenshots

### Dashboard
![Dashboard](screenshots/dashboard.png)

### Site Management
![Sites](screenshots/sites.png)

### SSL Management
![SSL](screenshots/ssl.png)

## Roadmap

- [ ] Multi-user Support
- [ ] Email Server Integration
- [ ] DNS Management
- [ ] Advanced Backup Solutions
- [ ] API Documentation
- [ ] Docker Support

## Author

[Bajpan Gosh](https://github.com/bajpangosh)

## Support the Project

⭐️ Star this repository if you find it helpful!

## Community & Support

- 🌟 Star this repo
- 🐞 Report issues [here](https://github.com/bajpangosh/kloudpanel/issues)
- 💬 Join discussions [here](https://github.com/bajpangosh/kloudpanel/discussions)
- 🤝 Contribute to the project

## Stay Updated

Watch this repository to stay updated with new features and improvements!
