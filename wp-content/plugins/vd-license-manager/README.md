# VD License Manager

Advanced license management system for Helium10, Midjourney, Freepik with sticky provider assignment and device management.

## Overview

VD License Manager is a comprehensive WordPress plugin designed to manage software licenses with advanced features including:

- **Sticky Provider Assignment**: Intelligent assignment of licenses to provider accounts
- **Device Management**: Track and approve devices with automatic risk scoring
- **Field Sharing Configuration**: Granular control over what account information to share
- **Audit Logging**: Complete audit trail for security and compliance
- **Rate Limiting**: Multi-level rate limiting protection
- **Encryption**: AES-256-GCM encryption for sensitive data

## Sprint 1 Status: ✅ COMPLETED

**Plugin Foundation** - All core infrastructure implemented and tested.

### Implemented Features

- ✅ **Plugin Structure**: Complete folder hierarchy and file organization
- ✅ **Activation System**: Robust activation with requirements checking
- ✅ **Admin Interface**: Basic admin menu and dashboard
- ✅ **Asset Management**: CSS/JS loading system for admin and public
- ✅ **PHP 7.4 Compatibility**: Full compatibility with PHP 7.4.27+
- ✅ **Requirements Validation**: Comprehensive system requirements checking
- ✅ **Error Handling**: Proper error handling and user feedback

## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **MySQL/MariaDB**: 5.7/10.3 or higher
- **PHP Extensions**: openssl, json, mysqli, curl, mbstring

## Installation

### 1. Upload Plugin
```bash
# Upload to wp-content/plugins/ directory
unzip vd-license-manager.zip -d /path/to/wp-content/plugins/
```

### 2. Configure Encryption Key
Add to your `wp-config.php` file:
```php
define('VD_ENCRYPTION_KEY', 'base64:VkQtTGljZW5zZS1NYW5hZ2VyLUtleS0zMi1CeXRlcyE=');
```

### 3. Activate Plugin
1. Go to WordPress Admin → Plugins
2. Find "VD License Manager"
3. Click "Activate"

## Configuration

### Admin Dashboard
Access the admin dashboard at:
```
WordPress Admin → VD License → Dashboard
```

### System Status
Check system requirements and configuration:
```
WordPress Admin → VD License → System Status
```

### Settings
Configure basic plugin settings:
```
WordPress Admin → VD License → Settings
```

## File Structure

```
wp-content/plugins/vd-license-manager/
├── vd-license-manager.php         # Main plugin file
├── README.md                      # This file
├── includes/                      # Core PHP classes
│   ├── class-vd-license-manager.php
│   ├── class-vd-activator.php
│   └── functions.php
├── admin/                         # Admin interface
│   ├── class-vd-admin-menu.php
│   └── assets/
│       ├── css/admin.css
│       └── js/admin.js
├── public/                        # Public/frontend assets
│   └── assets/
│       ├── css/public.css
│       └── js/public.js
├── api/                          # REST API (Sprint 4)
├── security/                     # Security classes (Sprint 3)
├── integrations/                 # External integrations (Sprint 5)
├── languages/                    # Translation files
├── tests/                        # Unit and integration tests
└── config/                       # Configuration files
```

## Development Status

### ✅ Sprint 1: Plugin Foundation (COMPLETED)
- Core plugin structure
- Activation/deactivation system
- Admin menu framework
- Asset loading system
- Requirements validation

### ⏳ Sprint 2: Database Layer (PENDING)
- Database schema creation
- Migration system
- CRUD operations
- Data validation

### ⏳ Sprint 3: Security & Encryption (PENDING)
- AES-256-GCM encryption
- Audit logging
- Security manager
- User capabilities

### ⏳ Sprint 4: API Layer (PENDING)
- REST API endpoints
- Authentication
- Rate limiting
- Input validation

### ⏳ Sprint 5: LMfWC Integration (PENDING)
- License Manager for WooCommerce integration
- Product mapping
- License validation

### ⏳ Sprint 6: Admin Interface (PENDING)
- License management interface
- Provider account management
- Device approval system
- Audit log viewer

### ⏳ Sprint 7: Frontend Portal (PENDING)
- Customer portal shortcode
- Device management interface
- Usage history
- Copy-only functionality

### ⏳ Sprint 8: Testing & Optimization (PENDING)
- Comprehensive testing
- Performance optimization
- Security audit
- Production deployment

## API Endpoints (Future)

Once Sprint 4 is completed, the following API endpoints will be available:

```
GET    /wp-json/vd/v1/status                    # System status
POST   /wp-json/vd/v1/license/resolve-info     # License resolution
GET    /wp-json/vd/v1/device/approve/{id}      # Device approval
POST   /wp-json/vd/v1/device/reject/{id}       # Device rejection
```

## Testing

### Manual Testing (Sprint 1)
1. **Plugin Activation**:
   - Go to WordPress Admin → Plugins
   - Activate "VD License Manager"
   - Verify no errors appear

2. **Admin Menu**:
   - Check "VD License" appears in admin menu
   - Access Dashboard, System Status, and Settings pages
   - Verify all pages load without errors

3. **Requirements Check**:
   - Go to System Status page
   - Verify all requirements show green checkmarks
   - If encryption key not configured, verify error message appears

4. **Settings**:
   - Go to Settings page
   - Change debug mode and device limit settings
   - Save and verify settings are preserved

### Unit Tests (Future)
Unit tests will be implemented in Sprint 8.

## Troubleshooting

### Common Issues

#### Plugin won't activate
- Check PHP version is 7.4+
- Verify required PHP extensions are installed
- Ensure encryption key is configured in wp-config.php

#### Admin menu not appearing
- Check user has administrator role
- Verify plugin is properly activated
- Check for plugin conflicts

#### System Status shows errors
- Review each requirement individually
- Install missing PHP extensions
- Update WordPress/PHP if needed

### Error Logs
Check WordPress debug logs for detailed error information:
```
wp-content/debug.log
```

## Support

### Debug Mode
Enable debug logging in Settings → Debug Mode to get detailed information about plugin operations.

### System Information
Use the System Status page to gather system information for support requests.

## Security

### Encryption
All sensitive data is encrypted using AES-256-GCM encryption. The encryption key must be properly configured in wp-config.php.

### Audit Logging
All administrative actions are logged for security auditing (implemented in Sprint 3).

### Rate Limiting
API endpoints are protected with rate limiting to prevent abuse (implemented in Sprint 4).

## Contributing

This plugin follows WordPress coding standards and uses semantic versioning.

### Development Environment
- WordPress 6.8.2+
- PHP 7.4.27+
- MariaDB 10.4+

### Code Standards
- WordPress Coding Standards
- PHP 7.4 compatibility
- PSR-4 autoloading (future)

## Changelog

### Version 1.0.0 (Sprint 1)
- Initial plugin foundation
- Admin menu system
- Requirements validation
- Asset management
- PHP 7.4 compatibility

## License

GPL v2 or later

## Credits

Developed by VD Team for vidieu.vn infrastructure.