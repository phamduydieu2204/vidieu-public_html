-- SQL Commands to Insert Test Data for Provider Accounts
-- Run these in phpMyAdmin or database management tool

-- Check if table exists
SHOW TABLES LIKE 'bz_vd_provider_accounts';

-- Check current data
SELECT COUNT(*) FROM bz_vd_provider_accounts;

-- Insert test data
INSERT INTO bz_vd_provider_accounts
(provider, account_login, display_name, capacity, status, created_at, updated_at)
VALUES
('netflix', 'test1@example.com', 'Test Netflix Account', 5, 'active', NOW(), NOW()),
('spotify', 'test2@example.com', 'Test Spotify Account', 3, 'active', NOW(), NOW()),
('youtube', 'test3@example.com', 'Test YouTube Account', 10, 'suspended', NOW(), NOW());

-- Verify insertion
SELECT * FROM bz_vd_provider_accounts ORDER BY id DESC LIMIT 10;

-- Check total count
SELECT COUNT(*) as total_accounts FROM bz_vd_provider_accounts;