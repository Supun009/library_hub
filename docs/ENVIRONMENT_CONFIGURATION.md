# Environment Configuration Guide

## Overview

This application uses `.env` files for environment-specific configuration. This is the industry-standard approach used by Laravel, Symfony, and other modern PHP frameworks.

## Quick Start

### 1. Copy the Example File

```bash
cp .env.example .env
```

### 2. Edit Your Configuration

Open `.env` and update the values for your environment:

```dotenv
# Database Configuration
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=library_db
DB_USERNAME=root
DB_PASSWORD=your_password_here

# Application Settings
APP_URL=/lib_system/library_system
```

### 3. Done!

The application will automatically load your configuration.

## Configuration Files

### `.env`

- **Your actual configuration** (contains sensitive data)
- **NEVER commit this to Git** (already in `.gitignore`)
- Each developer/server has their own `.env` file

### `.env.example`

- **Template file** showing all available options
- **Safe to commit to Git** (no sensitive data)
- Used as a reference for setting up new environments

## Available Configuration Options

### Application Settings

```dotenv
# Application name (displayed in UI)
APP_NAME=LibraryHub

# Environment: development, staging, production
APP_ENV=development

# Enable/disable debug mode
APP_DEBUG=true

# Base URL of your application
APP_URL=/lib_system/library_system
```

### Database Configuration

```dotenv
# Database host
DB_HOST=127.0.0.1

# Database port (default MySQL port is 3306)
DB_PORT=3306

# Database name
DB_DATABASE=library_db

# Database username
DB_USERNAME=root

# Database password (leave empty for XAMPP default)
DB_PASSWORD=
```

### Asset Configuration

```dotenv
# URL for static assets (CSS, JS, images)
ASSET_URL=/lib_system/library_system/assets

# For CDN usage:
# ASSET_URL=https://cdn.yourdomain.com/assets
```

### Session Configuration

```dotenv
# Session lifetime in seconds (7200 = 2 hours)
SESSION_LIFETIME=7200

# Use secure cookies (set to true for HTTPS)
SESSION_SECURE=false

# HTTP only cookies (recommended for security)
SESSION_HTTP_ONLY=true
```

## Environment-Specific Configurations

### Development (XAMPP/Local)

```dotenv
APP_ENV=development
APP_DEBUG=true
APP_URL=/lib_system/library_system

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=library_db
DB_USERNAME=root
DB_PASSWORD=

SESSION_SECURE=false
```

### Staging Server

```dotenv
APP_ENV=staging
APP_DEBUG=true
APP_URL=https://staging.yourdomain.com

DB_HOST=staging-db.yourdomain.com
DB_PORT=3306
DB_DATABASE=library_db_staging
DB_USERNAME=staging_user
DB_PASSWORD=secure_password_here

SESSION_SECURE=true
```

### Production Server

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_HOST=production-db.yourdomain.com
DB_PORT=3306
DB_DATABASE=library_db
DB_USERNAME=prod_user
DB_PASSWORD=very_secure_password_here

ASSET_URL=https://cdn.yourdomain.com/assets

SESSION_SECURE=true
SESSION_LIFETIME=3600
```

## Using Environment Variables in Code

### Using the `env()` Helper Function

```php
// Get value with fallback
$dbHost = env('DB_HOST', '127.0.0.1');

// Get value (returns null if not set)
$apiKey = env('API_KEY');

// Boolean values
$debug = env('APP_DEBUG', false); // Returns actual boolean

// In configuration files
return [
    'database' => [
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 3306),
    ]
];
```

### Accessing Configuration

```php
// Database is automatically configured
// No need to manually access DB env vars

// For custom configuration
$config = require __DIR__ . '/config/app_config.php';
$appName = $config['app_name'];
```

## Security Best Practices

### ✅ DO

- **Keep `.env` in `.gitignore`** - Already configured
- **Use different passwords** for each environment
- **Set `APP_DEBUG=false`** in production
- **Use `SESSION_SECURE=true`** with HTTPS
- **Commit `.env.example`** as a template
- **Document all env variables** in `.env.example`

### ❌ DON'T

- **Never commit `.env`** to version control
- **Never share `.env`** files publicly
- **Never hardcode** sensitive data in code
- **Never use production credentials** in development

## Deployment Checklist

### Setting Up a New Environment

1. **Copy the example file**

   ```bash
   cp .env.example .env
   ```

2. **Update database credentials**

   ```dotenv
   DB_HOST=your_host
   DB_DATABASE=your_database
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

3. **Update application URL**

   ```dotenv
   APP_URL=https://yourdomain.com
   ASSET_URL=https://yourdomain.com/assets
   ```

4. **Set environment to production**

   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   ```

5. **Configure session security**

   ```dotenv
   SESSION_SECURE=true
   SESSION_HTTP_ONLY=true
   ```

6. **Test the configuration**
   - Visit your application
   - Check database connection
   - Verify assets load correctly

## Troubleshooting

### Database Connection Failed

**Problem:** "Database Connection Failed" error

**Solutions:**

1. Check `.env` file exists
2. Verify database credentials are correct
3. Ensure database server is running
4. Check `DB_HOST` and `DB_PORT` are correct

### Assets Not Loading

**Problem:** CSS/JS files not loading

**Solutions:**

1. Check `ASSET_URL` in `.env`
2. Verify assets directory exists
3. Check file permissions

### Configuration Not Updating

**Problem:** Changes to `.env` not taking effect

**Solutions:**

1. Clear PHP opcode cache (if using)
2. Restart web server
3. Check file permissions on `.env`

## File Permissions

Ensure proper permissions on sensitive files:

```bash
# Linux/Mac
chmod 600 .env
chmod 644 .env.example

# The .env file should only be readable by the web server user
```

## Migration from Hardcoded Values

If you're migrating from hardcoded configuration:

### Before

```php
$host = '127.0.0.1';
$username = 'root';
$password = '';
```

### After

```php
$host = env('DB_HOST', '127.0.0.1');
$username = env('DB_USERNAME', 'root');
$password = env('DB_PASSWORD', '');
```

## Additional Resources

- [The Twelve-Factor App - Config](https://12factor.net/config)
- [PHP dotenv Library](https://github.com/vlucas/phpdotenv)
- [Environment Variables Best Practices](https://www.twilio.com/blog/environment-variables-php)

## Support

For issues with environment configuration:

1. Check this documentation
2. Verify `.env` file syntax
3. Review error logs
4. Contact the development team

---

**Remember:** Never commit your `.env` file to version control! 🔒
