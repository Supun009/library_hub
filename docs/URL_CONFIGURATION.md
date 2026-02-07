# URL Configuration Guide

## Overview

This application uses **environment-based configuration** with **helper functions** for centralized URL management. This is an industry-standard approach that makes the application easy to deploy across different environments (development, staging, production).

## Files Structure

```
library_system/
├── config/
│   └── app_config.php          # Application configuration
├── includes/
│   └── url_helper.php          # URL helper functions
└── ...
```

## Configuration

### Development (Current Setup)

The application is configured for XAMPP development:

- Base URL: `/lib_system/library_system`
- Asset URL: `/lib_system/library_system/assets`

### Production Deployment

When deploying to production, you have two options:

#### Option 1: Environment Variables (Recommended)

Set these environment variables in your server configuration:

```bash
APP_URL=https://yourdomain.com
ASSET_URL=https://cdn.yourdomain.com/assets  # Optional: if using CDN
APP_ENV=production
APP_DEBUG=false
```

#### Option 2: Edit Configuration File

Edit `config/app_config.php`:

```php
return [
    'base_url' => 'https://yourdomain.com',
    'asset_url' => 'https://yourdomain.com/assets',
    'environment' => 'production',
    'debug' => false,
];
```

## Available Helper Functions

### URL Generation

#### `url($path)`

Generate a URL relative to the application base.

```php
echo url('admin/dashboard.php');
// Output: /lib_system/library_system/admin/dashboard.php
```

#### `adminUrl($path)`

Generate an admin URL.

```php
echo adminUrl('manage_books.php');
// Output: /lib_system/library_system/admin/manage_books.php
```

#### `memberUrl($path)`

Generate a member URL.

```php
echo memberUrl('index.php');
// Output: /lib_system/library_system/member/index.php
```

#### `authUrl($path)`

Generate an auth URL.

```php
echo authUrl('logout.php');
// Output: /lib_system/library_system/auth/logout.php
```

#### `asset($path)`

Generate an asset URL (for CSS, JS, images).

```php
echo asset('css/style.css');
// Output: /lib_system/library_system/assets/css/style.css

echo asset('images/logo.png');
// Output: /lib_system/library_system/assets/images/logo.png
```

#### `apiUrl($path)`

Generate an API URL.

```php
echo apiUrl('search_books.php');
// Output: /lib_system/library_system/api/search_books.php
```

### Utility Functions

#### `redirect($path, $statusCode = 302)`

Redirect to a URL.

```php
redirect('admin/dashboard.php');
// Redirects to: /lib_system/library_system/admin/dashboard.php
```

#### `isCurrentUrl($path)`

Check if the current URL matches a path.

```php
if (isCurrentUrl('admin/dashboard.php')) {
    echo 'You are on the dashboard';
}
```

## Usage Examples

### In HTML/PHP Files

```php
<!-- Navigation Links -->
<a href="<?php echo adminUrl('dashboard.php'); ?>">Dashboard</a>
<a href="<?php echo memberUrl('my_loans.php'); ?>">My Loans</a>

<!-- Assets -->
<link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
<script src="<?php echo asset('js/app.js'); ?>"></script>
<img src="<?php echo asset('images/logo.png'); ?>" alt="Logo">

<!-- Forms -->
<form action="<?php echo adminUrl('process_book.php'); ?>" method="POST">
    <!-- form fields -->
</form>

<!-- Redirects -->
<?php
if ($success) {
    redirect('admin/dashboard.php');
}
?>
```

### In JavaScript Files

For JavaScript files that need to make AJAX requests:

```javascript
// In your HTML, pass the base URL to JavaScript
<script>
    const BASE_URL = '<?php echo getBaseUrl(); ?>';
    const API_URL = '<?php echo url('api'); ?>';
</script>

// Then in your JS file
fetch(`${API_URL}/search_books.php?q=${query}`)
    .then(response => response.json())
    .then(data => console.log(data));
```

## Benefits

✅ **Easy Deployment**: Change one config file to deploy anywhere  
✅ **Environment Support**: Different URLs for dev/staging/production  
✅ **Maintainable**: Update URLs in one place  
✅ **CDN Ready**: Separate asset URLs for CDN integration  
✅ **Type Safe**: IDE autocomplete for helper functions  
✅ **Clean Code**: No hardcoded URLs in templates

## Migration from Hardcoded URLs

If you have old files with hardcoded URLs, replace them like this:

```php
<!-- Old (Hardcoded) -->
<a href="/lib_system/library_system/admin/dashboard.php">Dashboard</a>

<!-- New (Helper Function) -->
<a href="<?php echo adminUrl('dashboard.php'); ?>">Dashboard</a>
```

```php
<!-- Old (Hardcoded) -->
<link rel="stylesheet" href="/lib_system/library_system/assets/css/style.css">

<!-- New (Helper Function) -->
<link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
```

## Troubleshooting

### URLs not working after deployment

1. Check `config/app_config.php` has correct base URL
2. Verify environment variables are set correctly
3. Clear any PHP opcode cache (if using)

### Assets not loading

1. Verify `asset_url` in config is correct
2. Check file permissions on assets directory
3. Ensure `.htaccess` allows access to assets

## Best Practices

1. **Always use helper functions** - Never hardcode URLs
2. **Use specific helpers** - Use `adminUrl()`, `memberUrl()`, etc. instead of generic `url()`
3. **Keep paths relative** - Don't include leading slashes in helper function arguments
4. **Test in production** - Always test URL generation in staging before production

## Support

For issues or questions about URL configuration, refer to this guide or contact the development team.
