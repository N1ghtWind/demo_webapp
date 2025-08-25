## Overview

This application implements a complete, yet simple e-commerce solution with:
- **Backend**: Laravel 10 with Repository Pattern and Service Layer architecture
- **Frontend**: Vue 3 with Vuetify, Pinia for state management, and Vue Router
- **Authentication**: JWT-based authentication with separate admin and user flows
- **Image Management**: Configurable S3 or local storage with Glide image processing
- **Database**: MySQL 8.2 with comprehensive seeding and factory support
- **Development Environment**: Docker Compose with full development stack

## Features

### Core E-commerce Features
-  **Product Management**: Complete CRUD operations with categories and image support
-  **Order System**: Full order lifecycle management with order items
-  **Image Processing**: Glide integration for dynamic image resizing and optimization
-  **Category Management**: Hierarchical product categorization
-  **User Management**: Customer registration, authentication, and profile management

### Authentication & Security
-  **JWT Authentication**: Secure token-based authentication system
-  **Admin Panel**: Separate admin authentication and management interface
-  **Refresh Tokens**: Automatic token refresh mechanism
-  **Role-based Access**: Admin and user role separation

### Architecture Features
-  **Repository Pattern**: Clean data access layer abstraction
-  **Strategy Pattern**: Configurable image upload strategies (S3/Local)
-  **Service Layer**: Business logic separation and organization
-  **API First**: RESTful API design with frontend SPA support

### Development & Quality
-  **Code Quality Tools**: PHPStan, PHPCS, PHPMD integration
-  **Testing Suite**: PHPUnit with Feature and Unit tests
-  **Docker Environment**: Complete containerized development stack
-  **Frontend Tooling**: Vite, ESLint, Prettier for modern frontend development

## Minimum Requirements

### System Requirements
- **PHP**: ^8.1
- **Node.js**: 16.x or higher
- **Composer**: 2.x
- **Docker**: 20.x (for containerized development)
- **Docker Compose**: 3.7+

### PHP Extensions
- BCMath PHP Extension
- Ctype PHP Extension
- cURL PHP Extension
- DOM PHP Extension
- Fileinfo PHP Extension
- JSON PHP Extension
- Mbstring PHP Extension
- OpenSSL PHP Extension
- PCRE PHP Extension
- PDO PHP Extension
- Tokenizer PHP Extension
- XML PHP Extension

## Installation & Setup

### 1. Clone Repository
```bash
git clone <repository-url>
cd demo_webapp
```

### 2. Environment Configuration
```bash
cp .env.example .env
# Configure your environment variables in .env
```

### 3. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### 4. Application Setup
```bash
# Generate application key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret

# Run database migrations
php artisan migrate

# Seed database (optional)
php artisan db:seed
```

### 5. Build Frontend Assets
```bash
# Development build
npm run dev

# Production build
npm run build

# SPA mode build
npm run build-spa
```

## Docker Services

### Available Services

The application includes a comprehensive Docker Compose setup with the following services:

| Service | Description | Ports | Container Name |
|---------|-------------|-------|----------------|
| **nginx** | Main web server | `${NGINX_PORT}:80` | webapp_nginx |
| **image-nginx** | Image processing server | `8090:80` | webapp_image_nginx |
| **php** | PHP 8.2 FPM | - | webapp_php |
| **mysql82** | MySQL 8.2 database | `33064:3306` | webapp_mysql82 |
| **mysql_test** | Test database | `33065:3306` | webapp_mysql_test |
| **phpmyadmin** | Database admin interface | `${PHPMYADMIN_PORT}:80` | webapp_phpmyadmin |
| **node** | Node.js development server | `8085:8080` | webapp_node |
| **mailhog** | Email testing service | `1025:1025`, `8025:8025` | webapp_mailhog |
| **minio** | S3-compatible object storage | `9000:9000`, `9001:9001` | webapp-minio |

### Docker Environment Setup

#### 1. Configure Environment
Ensure your `.env` file includes Docker-specific variables:
```env
# Docker Configuration
NGINX_PORT=8080
PHPMYADMIN_PORT=8081
DB_HOST=mysql82
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
DB_ROOT_PASSWORD=root_password

# MinIO S3 Configuration
AWS_ACCESS_KEY_ID=minio
AWS_SECRET_ACCESS_KEY=minio123
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=webapp
AWS_ENDPOINT=http://localhost:9000
```

#### 2. Start Docker Services
```bash
# Start all services
docker-compose up -d

# Start specific services
docker-compose up -d nginx php mysql82

# View logs
docker-compose logs -f

# Stop services
docker-compose down
```

#### 3. Docker Development Commands
```bash
# Execute commands in PHP container
docker-compose exec php php artisan migrate
docker-compose exec php composer install
docker-compose exec php php artisan test

# Execute commands in Node container
docker-compose exec node npm install
docker-compose exec node npm run dev

# Access MySQL
docker-compose exec mysql82 mysql -u root -p
```

### Service Details

#### **Web Servers**
- **nginx**: Main application server serving Laravel backend and Vue frontend
- **image-nginx**: Dedicated server for image processing and serving

#### **Database**
- **mysql82**: Primary MySQL 8.2 database with persistent volume
- **mysql_test**: Isolated test database for running tests
- **phpmyadmin**: Web-based MySQL administration interface

#### **Development Tools**
- **node**: Node.js container for frontend development and build processes
- **mailhog**: SMTP testing server with web interface for email debugging

#### **Storage**
- **minio**: S3-compatible object storage for file uploads and image management

## Development Commands

### PHP/Laravel Commands
```bash
# Dependencies
composer install                    # Install PHP dependencies

# Testing
./vendor/bin/phpunit                # Run PHPUnit tests
php artisan test                    # Run Laravel tests

# Code Quality
composer lint                       # Run PHP CodeSniffer
composer lint-fix                   # Fix coding standards
composer analyse                    # Run PHPStan analysis
composer phpmd                      # Run mess detector
composer deptrac                    # Analyze dependencies
composer phpmetrics                 # Generate code metrics

# Application
php artisan serve                   # Start development server
php artisan migrate                 # Run database migrations
php artisan db:seed                 # Seed database
php artisan queue:work              # Start queue worker
```

### Frontend Commands
```bash
npm run dev                         # Start Vite development server
npm run build                       # Production build
npm run build-spa                   # SPA mode build
npm run watch                       # Watch mode for assets
npm run spa                         # SPA development server
```

## Testing

### Running Tests
```bash
# All tests
php artisan test

# Specific test suite
./vendor/bin/phpunit --testsuite=Feature
./vendor/bin/phpunit --testsuite=Unit

# Coverage report
./vendor/bin/phpunit --coverage-html coverage
```

### Test Environment
- **Database**: In-memory SQLite for fast execution
- **Cache**: Array driver
- **Queue**: Sync driver for immediate execution
- **Mail**: Log driver for email testing

## Production Deployment

### Build Process
```bash
# Install production dependencies
composer install --no-dev --optimize-autoloader

# Build frontend assets
npm run build

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Environment Configuration
Ensure production environment variables are properly configured:
- Database connection
- JWT secrets
- S3 credentials (if using S3 storage)
- Mail configuration
- Queue driver configuration

## Contributing

1. **Code Standards**: Follow PSR-12 coding standards
2. **Testing**: Ensure all tests pass before submitting PRs
3. **Code Quality**: Run linting and static analysis tools
4. **Documentation**: Update documentation for new features

### Development Workflow
```bash
# Start development environment
docker-compose up -d

# Install dependencies
composer install && npm install

# Run migrations and seeders
php artisan migrate --seed

# Start frontend development
npm run dev

# Run tests
php artisan test
```

## Troubleshooting

### Common Issues

#### Permission Issues (Linux/macOS)
```bash
sudo chown -R $USER:$USER storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

#### Database Connection Issues
- Verify Docker MySQL service is running
- Check `.env` database configuration
- Ensure MySQL port is not in use by another service

#### Frontend Build Issues
```bash
# Clear npm cache
npm cache clean --force

# Remove node_modules and reinstall
rm -rf node_modules package-lock.json
npm install
```

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
