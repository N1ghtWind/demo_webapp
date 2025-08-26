# 🛒 E-Commerce REST API

<div align="center">
  
[![Laravel](https://img.shields.io/badge/Laravel-10.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.2-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://docker.com)

*A modern, production-ready REST API for e-commerce applications*

</div>

## ✨ Overview

This application is a complete REST API e-commerce solution built with Laravel 10, featuring clean architecture patterns, comprehensive testing, and automatic API documentation generation.

### 🏗️ Architecture Stack

- **🔧 Backend**: Laravel 10 with Repository Pattern and Service Layer architecture
- **🔐 Authentication**: JWT-based auth with separate admin and user flows
- **📸 Image Management**: Configurable S3/Local storage with Glide processing
- **🗄️ Database**: MySQL 8.2 with comprehensive seeding and factories
- **📚 API Documentation**: Scramble for automatic OpenAPI documentation
- **🐳 Development**: Full Docker Compose development stack

## 🚀 Features

### 🛍️ Core E-commerce Features
- ✅ **Product Management**: Complete CRUD with categories and image support
- ✅ **Order System**: Full lifecycle management with order items
- ✅ **Image Processing**: Dynamic resizing and optimization with Glide
- ✅ **Category Management**: Hierarchical product categorization
- ✅ **User Management**: Registration, authentication, and profiles

### 🔒 Authentication & Security
- ✅ **JWT Authentication**: Secure token-based system with refresh tokens
- ✅ **Admin Panel**: Separate admin API endpoints and authentication
- ✅ **Role-based Access**: Admin and user role separation

### 🏛️ Architecture Features
- ✅ **Repository Pattern**: Clean data access layer abstraction
- ✅ **Strategy Pattern**: Configurable image upload strategies (S3/Local)
- ✅ **Service Layer**: Business logic separation and organization
- ✅ **API Documentation**: Auto-generated docs with Scramble

## 📋 Requirements

- **PHP**: ^8.1 with extensions (BCMath, Ctype, cURL, DOM, Fileinfo, JSON, Mbstring, OpenSSL, PCRE, PDO, Tokenizer, XML)
- **Node.js**: 16.x or higher
- **Composer**: 2.x
- **Docker**: 20.x + Docker Compose 3.7+ *(recommended)*

## 🐳 Docker Services

| Service | Description | Ports | Purpose |
|---------|-------------|-------|---------|
| **nginx** | Main web server | `8080:80` | Serves Laravel API |
| **image-nginx** | Image processing | `8090:80` | Handles image operations |
| **php** | PHP 8.2 FPM | - | Runs Laravel backend |
| **mysql82** | MySQL database | `33064:3306` | Primary database |
| **mysql_test** | Test database | `33065:3306` | Isolated test environment |
| **phpmyadmin** | DB admin interface | `8081:80` | Database management |
| **mailhog** | Email testing | `1025:1025`, `8025:8025` | Email debugging |
| **minio** | S3-compatible storage | `9000:9000`, `9001:9001` | File storage |

## 🛠️ Quick Setup

### Docker Setup (Recommended)

```bash
# 1. Clone and configure
git clone <repository-url>
cd demo_webapp
cp .env.example .env

# 2. Start Docker services
docker-compose up -d

# 3. Install dependencies and setup
docker-compose exec php composer install
docker-compose exec php php artisan key:generate
docker-compose exec php php artisan jwt:secret
docker-compose exec php php artisan migrate --seed
```

**🎉 Your API is ready!**
- **API**: http://localhost:8080/api
- **API Documentation**: http://localhost:8080/docs/api
- **phpMyAdmin**: http://localhost:8081
- **MailHog**: http://localhost:8025

### Local Setup

```bash
# 1. Clone and setup
git clone <repository-url>
cd demo_webapp
cp .env.example .env

# 2. Install dependencies
composer install

# 3. Configure application
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed

# 4. Start development server
php artisan serve
```

## 🧪 Development Commands

### Testing & Code Quality
```bash
./vendor/bin/phpunit                    # Run all tests
./vendor/bin/phpunit --testsuite=Unit   # Unit tests only
composer lint                           # PHP CodeSniffer
composer analyse                        # PHPStan static analysis
php artisan test                        # Laravel test runner
```

### Docker Development
```bash
# Execute commands in containers
docker-compose exec php php artisan migrate
docker-compose exec php composer install
docker-compose exec php php artisan test

# View logs
docker-compose logs -f php
```

## 🔧 Environment Configuration

Key environment variables in your `.env` file:

```env
# Application
APP_NAME="E-Commerce API"
APP_URL=http://localhost:8080

# Database
DB_HOST=mysql82
DB_DATABASE=webapp
DB_USERNAME=testuser
DB_PASSWORD=testuser

# JWT Authentication
JWT_SECRET=your-jwt-secret-here

# Image Upload Strategy
IMAGE_UPLOAD_TO=local  # or 's3'

# S3 Configuration (if using S3)
AWS_ACCESS_KEY_ID=minio
AWS_SECRET_ACCESS_KEY=minio123
AWS_BUCKET=images
AWS_ENDPOINT=http://localhost:9000
```

## 📚 API Documentation

This project uses **Scramble** for automatic OpenAPI documentation generation. Access the interactive API documentation at:

**http://localhost:8080/docs/api**

The documentation includes:
- All API endpoints with request/response examples
- Authentication requirements
- Request validation rules
- Response schemas

## 🚢 Production Deployment

```bash
# Build for production
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set production environment
APP_ENV=production
APP_DEBUG=false
```

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
