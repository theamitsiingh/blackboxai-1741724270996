# Smart Contract Audit System

A comprehensive web-based system for auditing smart contracts, providing automated analysis, security checks, and compliance verification.

## Features

- **Smart Contract Auditing**
  - Automated security analysis
  - Code quality assessment
  - Best practices verification
  - Gas optimization checks

- **User Management**
  - Role-based access control (Admin, Auditor, User)
  - Secure authentication
  - User profile management

- **Audit Management**
  - Audit request submission
  - Status tracking
  - Priority management
  - Detailed audit reports

- **Compliance Checking**
  - Standard compliance verification
  - Custom rule implementation
  - Compliance reporting

- **Reporting System**
  - Detailed audit reports
  - Findings documentation
  - Recommendations
  - Export capabilities

## Technology Stack

- **Backend**
  - PHP 7.4+
  - MySQL Database
  - JWT Authentication
  - PSR-4 Autoloading

- **Frontend**
  - HTML5
  - Tailwind CSS
  - JavaScript
  - Font Awesome Icons

- **Security**
  - Input validation
  - XSS protection
  - CSRF protection
  - SQL injection prevention

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/smart-contract-audit-system.git
cd smart-contract-audit-system
```

2. Install PHP dependencies:
```bash
composer install
```

3. Create and configure environment file:
```bash
cp .env.example .env
# Edit .env with your configuration
```

4. Set up the database:
```bash
# Import database schema
mysql -u your_username -p your_database < database/schema.sql
```

5. Configure web server:
```apache
# Apache configuration example
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/smart-contract-audit-system
    
    <Directory /path/to/smart-contract-audit-system>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

6. Set proper permissions:
```bash
chmod -R 755 .
chmod -R 777 backend/logs
```

## Configuration

1. Database Configuration (.env):
```env
DB_HOST=localhost
DB_NAME=smart_contract_audit
DB_USER=your_username
DB_PASS=your_password
```

2. JWT Configuration (.env):
```env
JWT_SECRET=your-secret-key
JWT_EXPIRATION=3600
```

3. Application Configuration (.env):
```env
APP_NAME=SmartContractAuditSystem
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-domain.com
```

## Usage

1. Access the system through your web browser:
```
http://your-domain.com
```

2. Register a new account or login with existing credentials.

3. Submit a smart contract for auditing:
   - Provide contract source code
   - Select audit type
   - Set priority level
   - Add any specific concerns or notes

4. Track audit progress:
   - View audit status
   - Check findings
   - Review recommendations
   - Download reports

## Development

1. Enable debug mode:
```env
APP_DEBUG=true
```

2. Run code analysis:
```bash
composer analyse
```

3. Run code style checks:
```bash
composer cs-check
```

4. Run tests:
```bash
composer test
```

## Security

- Keep your JWT secret key secure
- Regularly update dependencies
- Monitor system logs
- Implement rate limiting
- Use HTTPS in production
- Regular security audits

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, email support@scas.example.com or create an issue in the repository.

## Authors

- Smart Contract Audit System Team
- Email: team@scas.example.com

## Acknowledgments

- [Tailwind CSS](https://tailwindcss.com)
- [Font Awesome](https://fontawesome.com)
- [PHP JWT](https://github.com/firebase/php-jwt)
- [Monolog](https://github.com/Seldaek/monolog)
