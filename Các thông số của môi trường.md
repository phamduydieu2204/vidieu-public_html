Trước khi tiến hành triển khai, tôi cung cấp cho bạn một số thông tin quan trọng cho môi trường của tôi, bạn hãy lưu vào 1 tệp và lưu vào các vị trí cần thiết cho chỉ số/số liệu đó:
# CORE
WP_VERSION=6.8.2
TABLE_PREFIX=bz_
PHP_VERSION=7.4.27
các Extension: mysqli, curl, mbstring



# DB
DB_NAME=vidieu_db
DB_USER=vidieu
DB_HOST=localhost
DB_CHARSET=utf8mb4
DB_COLLATE=utf8mb4_unicode_ci

# ENCRYPTION
VD_ENCRYPTION_KEY=base64:VkQtTGljZW5zZS1NYW5hZ2VyLUtleS0zMi1CeXRlcyE=

# REST
REST_BASE=/wp-json/vd/v1
ENDPOINT_RESOLVE=/license/resolve-info

# LICENSE SOURCE
LICENSE_SOURCE=LMFWC_DB   # or EXTERNAL_API
LMFWC_TABLE=bz_lmfwc_licenses
Các cột hiện có trong bz_lmfwc_licenses là: id, order_id, product_id, user_id, license_key, hash, expires_at
valid_for, source, status, times_activated, times_activated_max, created_at, created_by, updated_at, updated_by
LMFWC_FIELDS=license_key,product_id,status,expires_at
các bảng khác:
bz_lmfwc_activations
bz_lmfwc_api_keys
bz_lmfwc_generators
bz_lmfwc_licenses_meta

API LMFWC:
GET - v2/licenses
GET - v2/licenses/{license_key}
POST - v2/licenses
PUT - v2/licenses/{license_key}
DELETE - v2/licenses/{license_key}
GET - v2/licenses/activate/{license_key}
GET - v2/licenses/deactivate/{activation_token}
GET - v2/licenses/validate/{license_key}
GET - v2/generators
GET - v2/generators/{id}
POST - v2/generators
PUT - v2/generators/{id}
DELETE - v2/generators/{id}

REST API:
Consumer key: ck_208d18a140490def109b29fcc14739765427d8cb
Consumer secret: cs_36f463fa7f9548f6aff9cf195a3143a064b159ed


# PRODUCT → SHARE TYPE
PRODUCT_8210=COOKIE
PRODUCT_1357=USERPASS
PRODUCT_6456=USERPASS_2FA


# TEST DATA (optional)
TEST_LICENSE=H10D-DIJD-14RC-SOLE-6KUV30
TEST_LICENSE_PRODUCT=8210
TEST_LICENSE_STATUS=active
TEST_SHARE_TYPE=COOKIE
