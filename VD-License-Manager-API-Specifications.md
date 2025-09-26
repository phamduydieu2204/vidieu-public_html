# VD License Manager - API Specifications

## 📋 Table of Contents
1. [API Overview](#api-overview)
2. [Authentication](#authentication)
3. [Customer API](#customer-api)
4. [Admin API](#admin-api)
5. [Error Handling](#error-handling)
6. [Rate Limiting](#rate-limiting)
7. [API Testing](#api-testing)

---

## 🔌 API Overview

### Base URLs
```
Customer API: /wp-json/vd/v1/
Admin API:    /wp-json/vd/v1/admin/
```

### Response Format
```json
{
  "success": true|false,
  "data": {...},           // Success response data
  "error": "...",         // Error message (if success = false)
  "error_code": "...",    // Machine-readable error code
  "timestamp": "2024-01-01T12:00:00Z"
}
```

### HTTP Status Codes
- `200` - Success
- `400` - Bad Request (invalid parameters)
- `401` - Unauthorized (invalid/missing auth)
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found
- `429` - Too Many Requests (rate limited)
- `500` - Internal Server Error

---

## 🔐 Authentication

### Customer API Authentication
**No authentication required** - Uses license_key + device fingerprint for validation

### Admin API Authentication
```http
Authorization: Bearer {wordpress_auth_token}
X-WP-Nonce: {wp_nonce}
```

#### Generate Auth Token
```php
// WordPress REST API authentication
$user = wp_get_current_user();
$token = wp_generate_auth_cookie($user->ID, time() + 3600, 'auth');
```

#### Permission Checks
```php
// Required capabilities for admin endpoints
'vd_manage_licenses'         // View/manage licenses
'vd_manage_provider_accounts' // Manage provider accounts
'vd_approve_devices'         // Approve/block devices
'vd_view_audit_logs'        // View audit trails
'vd_manage_rate_limits'     // Configure rate limits
```

---

## 🏠 Customer API

### 1. Main License Resolution

#### `POST /wp-json/vd/v1/license/resolve-info`

**Purpose**: Main endpoint - resolve license và trả về content

```http
POST /wp-json/vd/v1/license/resolve-info
Content-Type: application/json

{
  "license_key": "VD-1234-ABCD-5678",
  "device_fp": "a1b2c3d4e5f6789abcdef1234567890abcdef1234567890abcdef1234567890ab",
  "device_info": {
    "ip": "1.2.3.4",
    "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
    "country": "VN",
    "browser": "Chrome",
    "os": "Windows",
    "screen_resolution": "1920x1080",
    "timezone": "Asia/Ho_Chi_Minh",
    "language": "vi-VN"
  }
}
```

**Success Response (200)**:
```json
{
  "success": true,
  "data": {
    "license_id": 12345,
    "product_id": 6789,
    "assigned_account_id": 7,
    "provider": "helium10",
    "share_type": "credentials_2fa",
    "content": {
      // Content được filter theo cấu hình field sharing của product
      "Email đăng nhập": "account@helium10.com",
      "Mật khẩu": "decrypted_password_here",
      "Mật khẩu_type": "sensitive",
      "Mã 2FA": "123456",
      "Mã 2FA_type": "sensitive",
      "Cookie đăng nhập": "session=abc123; token=xyz789",
      "Cookie đăng nhập_type": "sensitive",
      "Ngày đăng ký": "2024-01-01",
      "Ngày hết hạn": "2024-12-31",
      "Số tiền đăng ký": "99.00",
      "Trạng thái tài khoản": "active",
      "Số license gán": 3,
      "Lần check cuối": "2024-01-20T08:00:00Z",
      "Lần thành công cuối": "2024-01-20T07:45:00Z",
      "Số lỗi": 0,
      "Ghi chú": "Premium account - high priority",
      "version": 15,
      "updated_at": "2024-01-15T10:30:00Z"
    },
    "devices": [
      {
        "device_fp": "a1b2c3d4e5f6...",
        "status": "approved",
        "first_seen": "2024-01-10T10:30:00Z",
        "approved_at": "2024-01-10T10:31:15Z",
        "auto_approved": true,
        "country": "VN",
        "last_access": "2024-01-20T08:15:00Z",
        "risk_score": 15.5
      }
    ],
    "history": [
      {
        "timestamp": "2024-01-20T08:15:00Z",
        "ip": "1.2.3.4",
        "country": "VN",
        "status": "success",
        "response_time": 145,
        "content_version": 15
      }
    ],
    "rate_limit": {
      "window_seconds": 300,
      "max_hits": 10,
      "current_count": 3,
      "remaining": 7,
      "reset_at": "2024-01-20T10:30:00Z",
      "bypassed": false
    },
    "device_limits": {
      "max_devices": 3,
      "current_devices": 1,
      "remaining_slots": 2
    }
  },
  "timestamp": "2024-01-20T10:25:30Z"
}
```

**Error Responses**:

```json
// License expired
{
  "success": false,
  "error": "License đã hết hạn",
  "error_code": "license_expired",
  "details": {
    "expired_at": "2024-01-01T00:00:00Z",
    "can_renew": true
  }
}

// Device limit exceeded
{
  "success": false,
  "error": "Đã đạt giới hạn 3 thiết bị. Vui lòng nâng cấp gói hoặc liên hệ để tăng giới hạn.",
  "error_code": "device_limit_exceeded",
  "details": {
    "current_devices": 3,
    "max_devices": 3,
    "can_upgrade": true,
    "upgrade_url": "https://vidieu.vn/upgrade"
  }
}

// Rate limited
{
  "success": false,
  "error": "Vượt quá giới hạn request. Vui lòng thử lại sau 3 phút",
  "error_code": "rate_limited",
  "details": {
    "retry_after": 180,
    "reset_at": "2024-01-20T10:30:00Z",
    "max_hits": 10,
    "window_seconds": 300
  }
}

// Device pending approval
{
  "success": false,
  "error": "Thiết bị đang chờ phê duyệt. Admin sẽ xem xét trong 24h.",
  "error_code": "device_pending_approval",
  "details": {
    "device_fp": "a1b2c3d4e5f6...",
    "risk_score": 75.5,
    "submitted_at": "2024-01-20T08:00:00Z",
    "expected_review_time": "24 hours"
  }
}

// No provider available
{
  "success": false,
  "error": "Không thể cấp phát tài khoản. Vui lòng liên hệ hỗ trợ.",
  "error_code": "no_provider_available",
  "details": {
    "product_id": 6789,
    "provider_type": "helium10",
    "contact_support": "support@vidieu.vn"
  }
}
```

### 2. Device Management

#### `GET /wp-json/vd/v1/license/{license_key}/devices`

**Purpose**: Lấy danh sách devices của license

```json
{
  "success": true,
  "data": {
    "license_key": "VD-1234-ABCD-5678",
    "max_devices": 3,
    "devices": [
      {
        "device_fp": "abc123...",
        "status": "approved",
        "device_info": {
          "browser": "Chrome",
          "os": "Windows",
          "screen": "1920x1080"
        },
        "first_seen": "2024-01-10T10:30:00Z",
        "last_access": "2024-01-20T08:15:00Z",
        "country": "VN",
        "risk_score": 15.5,
        "auto_approved": true
      }
    ]
  }
}
```

### 3. License Validation (Simple)

#### `GET /wp-json/vd/v1/license/{license_key}/validate`

**Purpose**: Simple validation - chỉ check license còn hạn không

```json
{
  "success": true,
  "data": {
    "valid": true,
    "status": "active",
    "expires_at": "2024-12-31T23:59:59Z",
    "days_remaining": 45,
    "max_devices": 3
  }
}
```

---

## 🛠️ Admin API

### 1. License Management

#### `GET /wp-json/vd/v1/admin/licenses`

**Purpose**: Danh sách tất cả licenses với filtering

**Parameters**:
- `status`: active|expired|suspended
- `product_id`: Filter by product
- `user_id`: Filter by user
- `page`: Pagination
- `per_page`: Items per page (default: 20, max: 100)

```json
{
  "success": true,
  "data": {
    "licenses": [
      {
        "id": 12345,
        "license_key": "VD-1234-ABCD-5678",
        "product_id": 6789,
        "product_name": "Helium10 Premium",
        "user_id": 567,
        "username": "john_doe",
        "status": "active",
        "max_devices": 3,
        "current_devices": 2,
        "expires_at": "2024-12-31T23:59:59Z",
        "created_at": "2024-01-01T00:00:00Z",
        "assignment": {
          "provider_account_id": 7,
          "account_name": "main-h10-01",
          "assigned_at": "2024-01-01T00:05:00Z",
          "last_accessed": "2024-01-20T08:15:00Z"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 150,
      "total_pages": 8
    }
  }
}
```

#### `GET /wp-json/vd/v1/admin/license/{id}`

**Purpose**: Chi tiết license cụ thể

```json
{
  "success": true,
  "data": {
    "license": {
      "id": 12345,
      "license_key": "VD-1234-ABCD-5678",
      "product_id": 6789,
      "order_id": 98765,
      "user_id": 567,
      "status": "active",
      "max_devices": 5,  // Override from product default
      "expires_at": "2024-12-31T23:59:59Z"
    },
    "assignment": {
      "provider_account_id": 7,
      "account_name": "main-h10-01",
      "provider": "helium10",
      "assigned_at": "2024-01-01T00:05:00Z",
      "last_accessed": "2024-01-20T08:15:00Z",
      "status": "active"
    },
    "devices": [
      {
        "device_fp": "abc123...",
        "status": "approved",
        "risk_score": 15.5,
        "first_seen": "2024-01-10T10:30:00Z",
        "country": "VN",
        "auto_approved": true
      }
    ],
    "recent_activity": [
      {
        "timestamp": "2024-01-20T08:15:00Z",
        "action": "content_access",
        "ip": "1.2.3.4",
        "status": "success"
      }
    ]
  }
}
```

### 4. License Settings Management

#### `GET /wp-json/vd/v1/admin/license/{id}/settings`

**Purpose**: Lấy settings hiện tại của license (inheritance chain)

```json
{
  "success": true,
  "data": {
    "license_id": 12345,
    "effective_settings": {
      "max_devices": 10,
      "rate_limit_requests": 150,
      "rate_limit_window_hours": 2,
      "auto_approval_enabled": true,
      "grace_period_hours": 48
    },
    "settings_source": {
      "source": "license_override",
      "description": "Cài đặt riêng cho license này",
      "override_id": 567
    },
    "inheritance_chain": {
      "license_override": {
        "max_devices": 10,
        "rate_limit_requests": 150,
        "rate_limit_window_hours": null,
        "notes": "VIP customer - increased limits"
      },
      "product_settings": {
        "max_devices": 5,
        "rate_limit_requests": 100,
        "rate_limit_window_hours": 2,
        "auto_approval_enabled": true
      },
      "global_settings": {
        "max_devices": 3,
        "rate_limit_requests": 50,
        "rate_limit_window_hours": 1,
        "auto_approval_enabled": false
      }
    }
  }
}
```

#### `POST /wp-json/vd/v1/admin/license/{id}/settings/override`

**Purpose**: Tạo hoặc cập nhật license settings override

```http
POST /wp-json/vd/v1/admin/license/12345/settings/override
Authorization: Bearer {token}
Content-Type: application/json

{
  "max_devices": 15,
  "rate_limit_requests": 200,
  "rate_limit_window_hours": 3,
  "auto_approval_enabled": true,
  "notes": "Customer upgrade - premium support package"
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "license_id": 12345,
    "override_id": 678,
    "updated_settings": {
      "max_devices": 15,
      "rate_limit_requests": 200,
      "rate_limit_window_hours": 3,
      "auto_approval_enabled": true
    },
    "message": "License settings override updated successfully"
  }
}
```

#### `DELETE /wp-json/vd/v1/admin/license/{id}/settings/override`

**Purpose**: Xóa license settings override (trở về product settings)

```json
{
  "success": true,
  "data": {
    "license_id": 12345,
    "message": "License settings override removed. Using product defaults.",
    "new_effective_settings": {
      "max_devices": 5,
      "rate_limit_requests": 100,
      "rate_limit_window_hours": 1
    }
  }
}
```

#### `POST /wp-json/vd/v1/admin/license/reassign`

**Purpose**: Admin thay đổi provider account cho license

```http
POST /wp-json/vd/v1/admin/license/reassign
Authorization: Bearer {token}
Content-Type: application/json

{
  "license_id": 12345,
  "new_provider_account_id": 9,
  "reason": "Customer requested account change due to login issues"
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "license_id": 12345,
    "old_provider_account_id": 7,
    "new_provider_account_id": 9,
    "reassigned_at": "2024-01-20T10:30:00Z",
    "assignment_id": 456,
    "change_reason": "Customer requested account change due to login issues"
  }
}
```

### 5. Product Settings Management

#### `GET /wp-json/vd/v1/admin/products/settings`

**Purpose**: Lấy danh sách product settings

```json
{
  "success": true,
  "data": {
    "products": [
      {
        "product_id": 8210,
        "product_name": "Helium10 Premium Access",
        "settings": {
          "max_devices": 5,
          "rate_limit_requests": 100,
          "rate_limit_window_hours": 1,
          "auto_approval_enabled": true,
          "grace_period_hours": 72
        },
        "stats": {
          "total_licenses": 234,
          "active_licenses": 198,
          "assigned_providers": 3
        },
        "created_at": "2024-01-01T00:00:00Z",
        "updated_at": "2024-01-15T10:30:00Z"
      }
    ]
  }
}
```

#### `POST /wp-json/vd/v1/admin/products/{product_id}/settings`

**Purpose**: Tạo hoặc cập nhật product settings

```http
POST /wp-json/vd/v1/admin/products/8210/settings
Authorization: Bearer {token}
Content-Type: application/json

{
  "max_devices": 8,
  "rate_limit_requests": 150,
  "rate_limit_window_hours": 2,
  "auto_approval_enabled": true,
  "grace_period_hours": 48
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "product_id": 8210,
    "settings_id": 123,
    "updated_settings": {
      "max_devices": 8,
      "rate_limit_requests": 150,
      "rate_limit_window_hours": 2,
      "auto_approval_enabled": true,
      "grace_period_hours": 48
    },
    "affected_licenses": 234,
    "message": "Product settings updated successfully"
  }
}
```

#### `GET /wp-json/vd/v1/admin/products/{product_id}/analytics`

**Purpose**: Lấy analytics cho product cụ thể

```json
{
  "success": true,
  "data": {
    "product_id": 8210,
    "product_name": "Helium10 Premium Access",
    "stats": {
      "total_licenses": 234,
      "active_licenses": 198,
      "expired_licenses": 30,
      "suspended_licenses": 6,
      "assigned_providers": 3,
      "total_devices": 856,
      "approved_devices": 789
    },
    "settings": {
      "max_devices": 5,
      "rate_limit_requests": 100,
      "rate_limit_window_hours": 1,
      "auto_approval_enabled": true
    },
    "assigned_providers": [
      {
        "provider_account_id": 7,
        "account_name": "helium10-main-01",
        "provider": "helium10",
        "status": "active",
        "license_count": 89,
        "success_rate": 98.2,
        "avg_response_time": 145
      },
      {
        "provider_account_id": 8,
        "account_name": "helium10-backup-01",
        "provider": "helium10",
        "status": "active",
        "license_count": 67,
        "success_rate": 97.8,
        "avg_response_time": 156
      }
    ]
  }
}
```

### 6. Provider Account Lookup & Management

#### `GET /wp-json/vd/v1/admin/providers/search`

**Purpose**: Tra cứu provider account theo tên

```http
GET /wp-json/vd/v1/admin/providers/search?account_name=helium10-main-01
Authorization: Bearer {token}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "account_info": {
      "id": 7,
      "account_name": "helium10-main-01",
      "provider": "helium10",
      "share_type": "credentials_2fa",
      "capacity": 20,
      "current_load": 15,
      "status": "active",
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-20T08:00:00Z"
    },
    "assigned_licenses": [
      {
        "license_id": 12345,
        "license_key": "VD-1234-ABCD-5678",
        "product_id": 8210,
        "status": "active",
        "device_count": 3,
        "approved_devices": 3,
        "assignment_method": "auto",
        "assigned_at": "2024-01-01T00:05:00Z",
        "last_accessed": "2024-01-20T08:15:00Z"
      }
    ],
    "served_products": [
      {
        "product_id": 8210,
        "license_count": 15,
        "avg_response_time": 145,
        "total_requests": 45670,
        "successful_requests": 44892,
        "success_rate": 98.3
      }
    ],
    "stats": {
      "total_assigned_licenses": 15,
      "total_served_products": 1,
      "capacity_usage": "15/20"
    }
  }
}
```

#### `PUT /wp-json/vd/v1/admin/providers/{id}/credentials`

**Purpose**: Cập nhật thông tin đăng nhập provider account

```http
PUT /wp-json/vd/v1/admin/providers/7/credentials
Authorization: Bearer {token}
Content-Type: application/json

{
  "email": "newaccount@helium10.com",
  "password": "newpassword123",
  "twofa_code": "987654",
  "cookies": "session=abc123; token=xyz789; expires=...",
  "content_data": "{\"additional_info\": \"updated_manually\"}"
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "provider_account_id": 7,
    "version_number": 15,
    "updated_at": "2024-01-20T10:45:00Z",
    "content_hash": "new_hash_value_abc123",
    "message": "Provider credentials updated successfully",
    "test_required": true
  }
}
```

### 7. Global Settings Management

#### `GET /wp-json/vd/v1/admin/settings/global`

**Purpose**: Lấy toàn bộ global settings

```json
{
  "success": true,
  "data": {
    "settings": {
      "default_max_devices": 3,
      "default_rate_limit_requests": 100,
      "default_rate_limit_window_hours": 1,
      "auto_approval_enabled": true,
      "grace_period_hours": 72,
      "risk_threshold": 70,
      "support_email": "support@vidieu.vn"
    }
  }
}
```

#### `POST /wp-json/vd/v1/admin/settings/global`

**Purpose**: Cập nhật global settings

```http
POST /wp-json/vd/v1/admin/settings/global
Authorization: Bearer {token}
Content-Type: application/json

{
  "default_max_devices": 5,
  "default_rate_limit_requests": 150,
  "default_rate_limit_window_hours": 2,
  "auto_approval_enabled": false,
  "grace_period_hours": 48
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "updated_settings": {
      "default_max_devices": 5,
      "default_rate_limit_requests": 150,
      "default_rate_limit_window_hours": 2,
      "auto_approval_enabled": false,
      "grace_period_hours": 48
    },
    "affected_products_without_settings": 12,
    "affected_licenses_without_overrides": 567,
    "message": "Global settings updated successfully"
  }
}
```

### 8. Assignment History

#### `GET /wp-json/vd/v1/admin/license/{id}/assignment-history`

**Purpose**: Lấy lịch sử thay đổi assignment của license

```json
{
  "success": true,
  "data": {
    "license_id": 12345,
    "license_key": "VD-1234-ABCD-5678",
    "current_assignment": {
      "provider_account_id": 9,
      "account_name": "helium10-premium-01",
      "assigned_at": "2024-01-20T10:30:00Z",
      "assignment_method": "manual"
    },
    "history": [
      {
        "id": 789,
        "old_provider_account_id": 7,
        "old_account_name": "helium10-main-01",
        "new_provider_account_id": 9,
        "new_account_name": "helium10-premium-01",
        "changed_by": 123,
        "admin_username": "admin_user",
        "change_method": "manual",
        "change_reason": "Customer requested account change due to login issues",
        "created_at": "2024-01-20T10:30:00Z"
      },
      {
        "id": 456,
        "old_provider_account_id": null,
        "old_account_name": null,
        "new_provider_account_id": 7,
        "new_account_name": "helium10-main-01",
        "changed_by": 100,
        "admin_username": "system",
        "change_method": "auto_rebalance",
        "change_reason": "Initial assignment",
        "created_at": "2024-01-01T00:05:00Z"
      }
    ]
  }
}
    "reason": "Customer requested account change due to login issues"
  }
}
```

### 2. Provider Account Management

#### `GET /wp-json/vd/v1/admin/providers`

**Purpose**: Danh sách provider accounts với load info

```json
{
  "success": true,
  "data": {
    "providers": [
      {
        "id": 7,
        "provider": "helium10",
        "share_type": "credentials_2fa",
        "account_name": "main-h10-01",
        "capacity": 15,
        "current_load": 12,
        "load_percentage": 80.0,
        "status": "active",
        "last_content_update": "2024-01-15T10:30:00Z",
        "health_status": "healthy"
      }
    ],
    "summary": {
      "total_capacity": 50,
      "total_load": 35,
      "overall_load_percentage": 70.0,
      "healthy_accounts": 8,
      "unhealthy_accounts": 0
    }
  }
}
```

#### `POST /wp-json/vd/v1/admin/provider`

**Purpose**: Tạo provider account mới

```http
POST /wp-json/vd/v1/admin/provider
{
  "provider": "helium10",
  "share_type": "credentials_2fa",
  "account_name": "backup-h10-02",
  "capacity": 20,
  "credentials": {
    "email": "backup@helium10.com",
    "password": "password123",
    "totp_secret": "NEWTOTP123456789"
  }
}
```

#### `PUT /wp-json/vd/v1/admin/provider/{id}/content`

**Purpose**: Update content (cookie/credentials) cho provider account

```http
PUT /wp-json/vd/v1/admin/provider/7/content
{
  "content_type": "credentials",
  "data": {
    "email": "updated@helium10.com",
    "password": "newpassword456",
    "totp_secret": "UPDATEDTOTP789"
  },
  "format": "json"
}
```

### 3. Device Approval Management

#### `GET /wp-json/vd/v1/admin/devices/pending`

**Purpose**: Danh sách devices đang chờ approval

```json
{
  "success": true,
  "data": {
    "pending_devices": [
      {
        "id": 456,
        "license_id": 12345,
        "license_key": "VD-1234-ABCD-5678",
        "device_fp": "xyz789...",
        "device_info": {
          "browser": "Chrome",
          "os": "Windows",
          "screen": "1920x1080",
          "user_agent": "Mozilla/5.0..."
        },
        "risk_score": 75.5,
        "ip_address": "5.6.7.8",
        "country": "US",
        "first_seen": "2024-01-20T08:00:00Z",
        "waiting_time": "2 hours",
        "notes": null
      }
    ],
    "summary": {
      "total_pending": 12,
      "high_risk": 3,
      "medium_risk": 7,
      "low_risk": 2
    }
  }
}
```

#### `POST /wp-json/vd/v1/admin/device/approve`

**Purpose**: Approve/block/pending device

```http
POST /wp-json/vd/v1/admin/device/approve
{
  "device_request_id": 456,
  "action": "approve",  // approve, block, pending
  "notes": "Verified with customer via phone call"
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "device_request_id": 456,
    "action": "approve",
    "approved_by": 123,
    "approved_at": "2024-01-20T10:30:00Z",
    "notes": "Verified with customer via phone call"
  }
}
```

### 4. Product Provider Mapping

#### `GET /wp-json/vd/v1/admin/product/{product_id}/providers`

**Purpose**: Lấy providers được cấu hình cho product

```json
{
  "success": true,
  "data": {
    "product_id": 8210,
    "product_name": "Helium10 Premium",
    "providers": [
      {
        "mapping_id": 1,
        "provider_account_id": 1,
        "account_name": "main-h10-01",
        "provider": "helium10",
        "allocation_strategy": "least_loaded",
        "priority": 1,
        "is_active": true,
        "current_load": 8,
        "capacity": 15,
        "load_percentage": 53.3
      },
      {
        "mapping_id": 2,
        "provider_account_id": 2,
        "account_name": "backup-h10-01",
        "provider": "helium10",
        "allocation_strategy": "least_loaded",
        "priority": 2,
        "is_active": true,
        "current_load": 3,
        "capacity": 10,
        "load_percentage": 30.0
      }
    ]
  }
}
```

#### `POST /wp-json/vd/v1/admin/product/provider-mapping`

**Purpose**: Cấu hình providers cho product

```http
POST /wp-json/vd/v1/admin/product/provider-mapping
{
  "product_id": 8210,
  "provider_mappings": [
    {
      "provider_account_id": 1,
      "allocation_strategy": "least_loaded",
      "priority": 1,
      "is_active": true
    },
    {
      "provider_account_id": 2,
      "allocation_strategy": "least_loaded",
      "priority": 2,
      "is_active": true
    },
    {
      "provider_account_id": 3,
      "allocation_strategy": "sequential",
      "priority": 3,
      "is_active": true
    }
  ]
}
```

### 5. Product Field Sharing Management

#### `GET /wp-json/vd/v1/admin/product/{product_id}/field-sharing`

**Purpose**: Lấy cấu hình field sharing hiện tại của product

```http
GET /wp-json/vd/v1/admin/product/8210/field-sharing
Authorization: Bearer {token}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "product_id": 8210,
    "product_name": "Helium10 Premium",
    "available_fields": {
      "Login Info": [
        {
          "field_name": "email",
          "label": "Email đăng nhập",
          "is_shared": true,
          "display_name": "Email đăng nhập",
          "sort_order": 1,
          "is_sensitive": false
        },
        {
          "field_name": "password",
          "label": "Mật khẩu",
          "is_shared": true,
          "display_name": "Mật khẩu",
          "sort_order": 2,
          "is_sensitive": true
        },
        {
          "field_name": "twofa_code",
          "label": "Mã 2FA",
          "is_shared": true,
          "display_name": "Mã 2FA",
          "sort_order": 3,
          "is_sensitive": true
        }
      ],
      "Recovery Info": [
        {
          "field_name": "recovery_email",
          "label": "Email khôi phục",
          "is_shared": false,
          "display_name": "Email khôi phục",
          "sort_order": 5,
          "is_sensitive": false
        }
      ],
      "Account Info": [
        {
          "field_name": "account_registration_date",
          "label": "Ngày đăng ký",
          "is_shared": true,
          "display_name": "Ngày đăng ký",
          "sort_order": 8,
          "is_sensitive": false
        }
      ],
      "Operational": [
        {
          "field_name": "assigned_licenses_count",
          "label": "Số license gán",
          "is_shared": true,
          "display_name": "Số license gán",
          "sort_order": 12,
          "is_sensitive": false
        }
      ]
    },
    "sharing_summary": {
      "total_fields": 16,
      "shared_fields": 9,
      "sensitive_shared": 3
    }
  }
}
```

#### `PUT /wp-json/vd/v1/admin/product/{product_id}/field-sharing`

**Purpose**: Cập nhật cấu hình field sharing cho product

```http
PUT /wp-json/vd/v1/admin/product/8210/field-sharing
Authorization: Bearer {token}
Content-Type: application/json

{
  "field_configs": {
    "email": {
      "is_shared": true,
      "display_name": "Email đăng nhập",
      "sort_order": 1,
      "is_sensitive": false
    },
    "password": {
      "is_shared": true,
      "display_name": "Mật khẩu",
      "sort_order": 2,
      "is_sensitive": true
    },
    "twofa_code": {
      "is_shared": false,
      "display_name": "Mã 2FA",
      "sort_order": 3,
      "is_sensitive": true
    },
    "recovery_email": {
      "is_shared": false,
      "display_name": "Email khôi phục",
      "sort_order": 5,
      "is_sensitive": false
    },
    "account_registration_date": {
      "is_shared": true,
      "display_name": "Ngày đăng ký tài khoản",
      "sort_order": 8,
      "is_sensitive": false
    }
  }
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "updated_fields": 16,
    "shared_fields": 8,
    "changes": {
      "twofa_code": {
        "action": "disabled_sharing",
        "old_value": true,
        "new_value": false
      },
      "account_registration_date": {
        "action": "updated_display_name",
        "old_value": "Ngày đăng ký",
        "new_value": "Ngày đăng ký tài khoản"
      }
    },
    "audit_log_id": 12345
  }
}
```

#### `GET /wp-json/vd/v1/admin/product/{product_id}/field-sharing/preview`

**Purpose**: Preview nội dung sẽ được chia sẻ với khách hàng

```http
GET /wp-json/vd/v1/admin/product/8210/field-sharing/preview?provider_account_id=7
Authorization: Bearer {token}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "product_id": 8210,
    "provider_account_id": 7,
    "account_name": "main-h10-01",
    "filtered_content": {
      "Email đăng nhập": "premium@helium10.com",
      "Mật khẩu": "SuperSecretPassword123",
      "Mật khẩu_type": "sensitive",
      "Cookie đăng nhập": "session_id=abc123xyz; auth_token=def456",
      "Cookie đăng nhập_type": "sensitive",
      "Ngày đăng ký tài khoản": "2024-01-15",
      "Ngày hết hạn": "2024-12-31",
      "Số tiền đăng ký": "299.00",
      "Trạng thái tài khoản": "active",
      "Số license gán": 12,
      "Lần check cuối": "2024-01-20T08:30:00Z",
      "Số lỗi": 0,
      "Ghi chú": "Premium tier - priority support"
    },
    "excluded_fields": [
      "recovery_email",
      "recovery_password",
      "recovery_twofa_code",
      "twofa_code"
    ],
    "sensitive_field_count": 2
  }
}
```

#### `POST /wp-json/vd/v1/admin/product/field-sharing/bulk-update`

**Purpose**: Cập nhật cấu hình field sharing cho nhiều products

```http
POST /wp-json/vd/v1/admin/product/field-sharing/bulk-update
Authorization: Bearer {token}
Content-Type: application/json

{
  "product_ids": [8210, 8211, 8212],
  "operation": "apply_template",
  "template": {
    "email": {"is_shared": true, "sort_order": 1},
    "password": {"is_shared": true, "sort_order": 2},
    "twofa_code": {"is_shared": false, "sort_order": 3},
    "cookies": {"is_shared": true, "sort_order": 4},
    "recovery_email": {"is_shared": false, "sort_order": 5},
    "recovery_password": {"is_shared": false, "sort_order": 6},
    "account_registration_date": {"is_shared": true, "sort_order": 8},
    "account_expiry_date": {"is_shared": true, "sort_order": 9},
    "status": {"is_shared": true, "sort_order": 11},
    "notes": {"is_shared": false, "sort_order": 16}
  }
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "processed_products": 3,
    "successful_updates": 3,
    "failed_updates": 0,
    "results": [
      {
        "product_id": 8210,
        "success": true,
        "updated_fields": 16,
        "audit_log_id": 12346
      },
      {
        "product_id": 8211,
        "success": true,
        "updated_fields": 16,
        "audit_log_id": 12347
      },
      {
        "product_id": 8212,
        "success": true,
        "updated_fields": 16,
        "audit_log_id": 12348
      }
    ]
  }
}
```

### 6. Analytics & Statistics

#### `GET /wp-json/vd/v1/admin/stats/overview`

**Purpose**: Overview statistics

```json
{
  "success": true,
  "data": {
    "licenses": {
      "total": 1500,
      "active": 1200,
      "expired": 200,
      "suspended": 100,
      "expiring_soon": 25
    },
    "devices": {
      "total": 3200,
      "approved": 2800,
      "pending": 150,
      "blocked": 250
    },
    "providers": {
      "total": 12,
      "active": 10,
      "maintenance": 2,
      "average_load": 65.5
    },
    "requests": {
      "today": 15420,
      "this_week": 98765,
      "success_rate": 94.2,
      "avg_response_time": 145
    }
  }
}
```

#### `GET /wp-json/vd/v1/admin/stats/assignments`

**Purpose**: Assignment statistics với timeframe

**Parameters**: `timeframe` (24h, 7d, 30d)

```json
{
  "success": true,
  "data": {
    "timeframe": "7d",
    "total_licenses": 1200,
    "active_assignments": 1150,
    "provider_distribution": {
      "helium10": {
        "total": 800,
        "active": 750
      },
      "midjourney": {
        "total": 300,
        "active": 280
      },
      "freepik": {
        "total": 100,
        "active": 90
      }
    },
    "load_balancing": {
      "main-h10-01": {
        "load": 12,
        "capacity": 15,
        "percentage": 80.0
      }
    },
    "trends": [
      {
        "period": "2024-01-19",
        "assignments": 45,
        "active_assignments": 42
      }
    ]
  }
}
```

### 6. Rate Limiting Configuration

#### `POST /wp-json/vd/v1/admin/rate-limit/config`

**Purpose**: Cấu hình rate limiting

```http
POST /wp-json/vd/v1/admin/rate-limit/config
{
  "entity_type": "license",  // product, license, global
  "entity_id": 12345,
  "window_seconds": 300,
  "max_hits": 15,
  "bypass_on_content_change": true
}
```

#### `GET /wp-json/vd/v1/admin/rate-limit/stats`

**Purpose**: Rate limiting statistics

```json
{
  "success": true,
  "data": {
    "total_requests_today": 15420,
    "rate_limited_requests": 234,
    "bypass_count": 45,
    "effectiveness": 1.5,
    "top_licenses": [
      {
        "license_id": 123,
        "license_key": "VD-1234-ABCD",
        "requests": 500,
        "rate_limited": 25,
        "avg_response_time": 156
      }
    ]
  }
}
```

---

## ❌ Error Handling

### Standard Error Codes

#### License Errors
- `license_not_found` - License key không tồn tại
- `license_expired` - License đã hết hạn
- `license_suspended` - License bị tạm khóa
- `license_invalid` - License key format không đúng

#### Device Errors
- `device_limit_exceeded` - Vượt quá số thiết bị cho phép
- `device_blocked` - Thiết bị bị chặn
- `device_pending_approval` - Thiết bị chờ phê duyệt
- `device_fingerprint_invalid` - Device fingerprint không đúng format

#### Provider Errors
- `no_provider_available` - Không có provider account khả dụng
- `provider_maintenance` - Provider account đang maintenance
- `provider_overloaded` - Tất cả providers đã đầy

#### Rate Limiting
- `rate_limited` - Vượt quá giới hạn request
- `rate_limit_config_invalid` - Cấu hình rate limit không hợp lệ

#### System Errors
- `encryption_error` - Lỗi encrypt/decrypt
- `database_error` - Lỗi database
- `validation_error` - Dữ liệu đầu vào không hợp lệ

### Error Response Format
```json
{
  "success": false,
  "error": "Human readable error message",
  "error_code": "machine_readable_code",
  "details": {
    "field": "specific_field_with_error",
    "validation_errors": ["error1", "error2"]
  },
  "timestamp": "2024-01-20T10:30:00Z"
}
```

---

## ⚡ Rate Limiting

### Customer API Limits
- **Default**: 60 requests/minute per IP
- **Authenticated**: 100 requests/minute per license
- **Burst**: 10 requests/second short term

### Admin API Limits
- **Default**: 300 requests/minute per user
- **Bulk operations**: 30 requests/minute
- **Analytics**: 60 requests/minute

### Rate Limit Headers
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1642680900
X-RateLimit-Retry-After: 15
```

---

## 🧪 API Testing

### Testing Endpoints

#### Health Check
```http
GET /wp-json/vd/v1/health
```

#### API Documentation
```http
GET /wp-json/vd/v1/docs
```

### Sample Test Cases

#### Valid License Resolution
```bash
curl -X POST https://vidieu.vn/wp-json/vd/v1/license/resolve-info \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "VD-TEST-1234-ABCD",
    "device_fp": "test_device_fingerprint_64_chars_long_sha256_hash_string_here",
    "device_info": {
      "ip": "1.2.3.4",
      "user_agent": "Mozilla/5.0 Test Agent",
      "country": "VN",
      "browser": "Chrome",
      "os": "Windows"
    }
  }'
```

#### Admin License Listing
```bash
curl -X GET https://vidieu.vn/wp-json/vd/v1/admin/licenses?status=active&page=1 \
  -H "Authorization: Bearer {admin_token}" \
  -H "X-WP-Nonce: {wp_nonce}"
```

### Postman Collection
```json
{
  "info": {
    "name": "VD License Manager API",
    "version": "1.0.0"
  },
  "item": [
    {
      "name": "Customer API",
      "item": [
        {
          "name": "Resolve License Info",
          "request": {
            "method": "POST",
            "url": "{{base_url}}/wp-json/vd/v1/license/resolve-info",
            "body": {
              "mode": "raw",
              "raw": "{\n  \"license_key\": \"{{license_key}}\",\n  \"device_fp\": \"{{device_fp}}\",\n  \"device_info\": {\n    \"ip\": \"1.2.3.4\",\n    \"user_agent\": \"Test Agent\",\n    \"country\": \"VN\"\n  }\n}"
            }
          }
        }
      ]
    }
  ]
}
```

---

**🔌 API Specifications hoàn chỉnh với 15+ endpoints, authentication, error handling và testing guidelines.**