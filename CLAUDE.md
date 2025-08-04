# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Setup
```bash
# Install dependencies
composer install

# Setup database
php setup.php

# Setup demo data
php setup_demo.php
```

### Database Management
- Database schema files are in `database/` directory
- Configuration in `config/database.php`
- Run `php setup.php` to initialize the database

### Cron Job for Recurring Transactions
Set up a cron job to process recurring transactions:
```bash
0 0 * * * php /path/to/project/cron/process_recurring.php
```

### Development Server
The application runs on any PHP-compatible web server. Point the web root to the `/public` directory.

## Architecture Overview

### Core Framework
- **Custom PHP Framework**: No external framework used, built with custom MVC pattern
- **PSR-4 Autoloading**: Uses composer autoloader + custom autoloader in `autoload.php`
- **Namespace Structure**: `App\` as root namespace with subnamespaces for Core, Controllers, Models, etc.

### Key Components

#### Router (`core/Router.php`)
- Custom router handling all HTTP requests
- Routes defined in `public/index.php`
- Supports HTTP method restrictions
- Automatic controller/action dispatching

#### Database Layer (`core/Database.php`)
- Singleton pattern PDO wrapper
- Built-in query builder methods (insert, update, delete)
- Connection pooling and error handling

#### Authentication (`core/Auth.php`)
- Session-based authentication system
- User management with login/logout/registration
- Profile management and password reset functionality

#### Controllers (`controllers/`)
- Inherit from `core/Controller.php`
- Use `requireLogin()` for protected routes
- Layout rendering with `startBuffering()` method

### Directory Structure
```
├── controllers/     # MVC Controllers
├── core/           # Framework core classes
├── models/         # Database models
├── views/          # Templates and layouts
├── services/       # Business logic services
├── helpers/        # Utility functions
├── database/       # SQL schema files
├── public/         # Web root directory
├── config/         # Configuration files
└── cron/          # Scheduled tasks
```

### Key Features
- **Financial Management**: Transactions, budgets, accounts, categories
- **Reporting**: Financial reports with charts
- **Export**: PDF/Excel export functionality (uses TCPDF and PhpSpreadsheet)
- **Recurring Transactions**: Automated transaction processing
- **Savings Goals**: Goal tracking and contributions
- **Notifications**: User notification system
- **Progressive Web App**: Mobile-optimized with manifest.json

### Database Schema
Main tables: users, accounts, transactions, categories, budgets, savings_goals, recurring_transactions, notifications

### Frontend
- **Tailwind CSS**: For styling
- **Vanilla JavaScript**: No frontend framework
- **Responsive Design**: Mobile-first approach
- **Chart.js**: For financial charts and reports

### Services
- `ExportService`: Handles PDF/Excel exports
- `NotificationService`: Manages user notifications

### Security
- Password hashing for user authentication
- Session management with CSRF protection
- Input validation and sanitization
- SQL injection prevention with prepared statements