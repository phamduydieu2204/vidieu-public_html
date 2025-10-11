# VD License Manager

A WordPress plugin for managing shared provider accounts via license keys.

## Description

VD License Manager allows you to share provider accounts (Netflix, Spotify, Helium10, etc.) with customers through license keys. The plugin integrates with WooCommerce and License Manager for WooCommerce (LMfWC) to provide a complete license-based account sharing solution.

## Features

- **Provider Account Management**: Store and manage account credentials for various providers
- **Product Pool System**: Group accounts into pools and assign them to WooCommerce products
- **License Key Integration**: Seamless integration with LMfWC for license key generation
- **Device Tracking**: Monitor and limit device access per license
- **Rate Limiting**: Prevent API abuse with configurable rate limits
- **VPS Detection**: Detect and block VPS/proxy usage
- **Encrypted Storage**: All sensitive data is encrypted using AES-256-CBC
- **Comprehensive Logging**: Track all access attempts and system events
- **Email Notifications**: Automated delivery of license credentials

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- WooCommerce plugin
- License Manager for WooCommerce (LMfWC) plugin
- OpenSSL PHP extension

## Installation

1. Upload the plugin files to `/wp-content/plugins/vd-license-manager/`
2. Configure encryption key in `wp-config.php`:
   ```php
   define('VD_ENCRYPTION_KEY', 'base64:your-32-byte-key-here');
   ```
3. Activate the plugin through the WordPress admin
4. Configure plugin settings in the admin panel

## Configuration

### Required wp-config.php Settings

```php
// VD License Manager - Encryption
define('VD_ENCRYPTION_KEY', 'base64:VkQtTGljZW5zZS1NYW5hZ2VyLUtleS0zMi1CeXRlcyE=');

// Optional: Device fingerprint salt
define('VD_DEVICE_FINGERPRINT_SALT', 'your-device-salt-here');

// Optional: Debug settings
define('VD_DEBUG', true);
define('VD_DEBUG_LOG', WP_CONTENT_DIR . '/debug-vd.log');
define('VD_LOG_LEVEL', 'debug');
```

### API Endpoints

The plugin provides the following REST API endpoints:

- `GET /wp-json/vd/v1/license/resolve-info` - Get license information
- `POST /wp-json/vd/v1/device/verify` - Verify device access
- `GET /wp-json/vd/v1/account/info` - Get account credentials
- `GET /wp-json/vd/v1/history` - Get access history
- `POST /wp-json/vd/v1/device/rotate` - Request device rotation

## Usage

### Admin Panel

1. **Dashboard**: Overview of system status and statistics
2. **Pools**: Manage product pools and account assignments
3. **Accounts**: Add and manage provider account credentials
4. **Licenses**: View and manage license keys from LMfWC
5. **Devices**: Monitor customer device usage
6. **Logs**: View access logs and system events
7. **Settings**: Configure plugin options

### Customer Portal

Customers can access their license information through the portal API using their license key and device information.

## Development

### File Structure

```
vd-license-manager/
├── vd-license-manager.php          # Main plugin file
├── includes/                       # Core functionality
│   ├── services/                   # Business logic
│   ├── repositories/               # Database access
│   └── utils/                      # Helper utilities
├── admin/                          # Admin interface
├── public/                         # Public-facing code
└── templates/                      # Email templates
```

### Database Tables

The plugin creates 11 database tables:

1. `bz_vd_provider_accounts` - Provider account credentials
2. `bz_vd_product_pools` - Product to pool mappings
3. `bz_vd_pool_accounts` - Pool to account mappings
4. `bz_vd_cookie_assignments` - Cookie assignments
5. `bz_vd_product_share_configs` - Product response configurations
6. `bz_vd_device_fingerprints` - Device fingerprint storage
7. `bz_vd_license_devices` - License to device mappings
8. `bz_vd_license_device_limits` - Device limits per license
9. `bz_vd_account_fetch_log` - Account fetch history
10. `bz_vd_license_access_log` - License access attempts
11. `bz_vd_license_rate_limits` - Rate limiting tracking

### Coding Standards

This plugin follows WordPress Coding Standards and includes:

- PSR-4 autoloading via Composer
- Comprehensive PHPDoc documentation
- Input sanitization and output escaping
- Prepared database statements
- Proper error handling with WP_Error

## Security

- All sensitive data is encrypted using AES-256-CBC
- SQL injection protection via prepared statements
- XSS prevention through proper output escaping
- Rate limiting to prevent API abuse
- Device fingerprinting for access control
- VPS detection and blocking

## Support

For support and bug reports, please contact the development team at admin@vidieu.vn.

## Changelog

### 1.0.0
- Initial release
- Basic plugin structure
- Admin menu system
- Encryption service
- Logging service
- Validation utilities

## License

This plugin is licensed under GPL v2 or later.

## Credits

Developed by the Vidieu Team for managing shared provider accounts efficiently and securely.