/**
 * VD Portal JavaScript - Optimized Implementation
 * Handles form interactions, tab switching, and mock data display
 */

jQuery(document).ready(function($) {
    'use strict';

    // Portal object
    const VDPortal = {

        // Initialize portal
        init: function() {
            console.log('VD Portal: Initializing...');

            this.bindEvents();
            this.setupTabs();
            this.formatLicenseKey();

            console.log('VD Portal: Ready!');
        },

        // Bind event handlers
        bindEvents: function() {
            // License form submission
            $('#vd-license-form').on('submit', this.handleSubmit.bind(this));

            // Tab switching
            $('.vd-tab-btn').on('click', this.switchTab.bind(this));

            // License key formatting
            $('#license-key').on('input', this.formatLicenseInput.bind(this));

            // Copy button handlers (use delegation for dynamic content)
            $(document).on('click', '.vd-btn-copy', this.handleCopy.bind(this));

            // Action button handlers
            $(document).on('click', '#new-license-btn', this.resetForm.bind(this));
            $(document).on('click', '#refresh-btn', this.refreshData.bind(this));
        },

        // Setup tabs functionality
        setupTabs: function() {
            const $tabs = $('.vd-tab-btn');
            const $contents = $('.vd-tab-content');

            // Ensure first tab is active
            $tabs.first().addClass('active');
            $contents.first().addClass('active');
        },

        // Switch tabs
        switchTab: function(e) {
            e.preventDefault();

            const $btn = $(e.target);
            const tabId = $btn.data('tab');

            // Update active tab button
            $('.vd-tab-btn').removeClass('active');
            $btn.addClass('active');

            // Update active tab content
            $('.vd-tab-content').removeClass('active');
            $('#tab-' + tabId).addClass('active');

            console.log('VD Portal: Switched to tab:', tabId);
        },

        // Format license key input (XXXX-XXXX-XXXX-XXXX)
        formatLicenseInput: function(e) {
            const input = e.target;
            let value = input.value.replace(/[^A-Z0-9]/g, '').toUpperCase();

            // Format with dashes
            if (value.length > 0) {
                value = value.match(/.{1,4}/g).join('-');
                if (value.length > 19) {
                    value = value.substr(0, 19);
                }
            }

            input.value = value;
        },

        // Auto-format license key
        formatLicenseKey: function() {
            const $input = $('#license-key');

            $input.attr('maxlength', '19');
            $input.attr('placeholder', 'XXXX-XXXX-XXXX-XXXX');

            // Auto-uppercase and format
            $input.on('input', function() {
                let value = this.value.replace(/[^A-Z0-9]/g, '').toUpperCase();
                value = value.replace(/(.{4})/g, '$1-').replace(/-$/, '');
                this.value = value;
            });
        },

        // Handle form submission
        handleSubmit: function(e) {
            e.preventDefault();

            const licenseKey = $('#license-key').val().trim();

            if (!licenseKey) {
                this.showError('Vui lòng nhập mã license');
                return;
            }

            if (!this.isValidLicenseFormat(licenseKey)) {
                this.showError('Định dạng mã license không đúng. Vui lòng sử dụng: XXXX-XXXX-XXXX-XXXX');
                return;
            }

            this.processLicense(licenseKey);
        },

        // Display mock data for demonstration - Vietnamese with copy buttons
        displayMockData: function(licenseKey) {
            console.log('VD Portal: Displaying mock data with Vietnamese UI');

            const mockData = {
                license: licenseKey,
                status: 'Đang hoạt động',
                expires: '31/12/2024',
                product: 'Video Creator Pro',
                credentials: {
                    'Email đăng nhập': 'demo@vidieu.vn',
                    'Mật khẩu': 'VD2024@Secure',
                    'Cookie Session': 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoxMjM0NX0...',
                    'API Key': 'vd_api_abc123def456ghi789',
                    'User Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                },
                devices: [
                    { name: 'iPhone 13 Pro', ip: '192.168.1.100', last_seen: '5 phút trước' },
                    { name: 'MacBook Pro M2', ip: '192.168.1.101', last_seen: '1 giờ trước' },
                    { name: 'Windows Desktop', ip: '192.168.1.102', last_seen: '2 giờ trước' }
                ],
                history: [
                    { action: 'Đăng nhập thành công', ip: '192.168.1.100', time: '10:30 SA' },
                    { action: 'Tải thông tin tài khoản', ip: '192.168.1.101', time: '9:45 SA' },
                    { action: 'Xác minh thiết bị', ip: '192.168.1.102', time: '9:30 SA' },
                    { action: 'Cập nhật cookie', ip: '192.168.1.100', time: '8:15 SA' }
                ]
            };

            this.showLicenseData(mockData);
        },

        // Validate license key format
        isValidLicenseFormat: function(key) {
            const pattern = /^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/;
            return pattern.test(key);
        },

        // Process license (mock implementation)
        processLicense: function(licenseKey) {
            console.log('VD Portal: Processing license:', licenseKey);

            // Show loading state
            this.setLoading(true);

            // Simulate API call
            setTimeout(() => {
                this.setLoading(false);

                // Mock data for demonstration - Vietnamese
                if (licenseKey === 'DEMO-DEMO-DEMO-DEMO') {
                    this.displayMockData(licenseKey);
                } else {
                    this.showError('Không tìm thấy mã license hoặc mã không hợp lệ');
                }
            }, 1500);
        },

        // Show license data
        showLicenseData: function(data) {
            // Update license info
            $('#display-license').text(data.license);
            $('#display-status').text(data.status);
            $('#display-expires').text(data.expires);
            $('#display-product').text(data.product);

            // Show license info section
            $('#license-info').slideDown();

            // Show credentials
            this.displayCredentials(data.credentials);

            // Show devices
            this.displayDevices(data.devices);

            // Show history
            this.displayHistory(data.history);

            // Show action buttons
            $('#actions').slideDown();

            console.log('VD Portal: License data displayed');
        },

        // Display credentials with copy buttons - Vietnamese labels
        displayCredentials: function(credentials) {
            const $list = $('#credentials-list');
            $list.empty();

            Object.entries(credentials).forEach(([key, value]) => {
                const copyId = 'copy-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);

                const $item = $(`
                    <div class="vd-info-item">
                        <div class="vd-credential-field">
                            <span class="label">${key}:</span>
                            <div class="vd-credential-value">
                                <span class="value" id="${copyId}">${value}</span>
                                <button class="vd-btn-copy" data-copy-target="${copyId}" title="Sao chép ${key}">
                                    📋
                                </button>
                            </div>
                        </div>
                    </div>
                `);
                $list.append($item);
            });

            $('#credentials').slideDown();
        },

        // Display devices
        displayDevices: function(devices) {
            const $list = $('#device-list');
            $list.removeClass('vd-empty-state').empty();

            devices.forEach(device => {
                const $device = $(`
                    <div class="vd-info-item">
                        <div>
                            <strong>${device.name}</strong><br>
                            <small>IP: ${device.ip}</small>
                        </div>
                        <small>${device.last_seen}</small>
                    </div>
                `);
                $list.append($device);
            });
        },

        // Display history
        displayHistory: function(history) {
            const $list = $('#history-list');
            $list.removeClass('vd-empty-state').empty();

            history.forEach(entry => {
                const $entry = $(`
                    <div class="vd-info-item">
                        <div>
                            <strong>${entry.action}</strong><br>
                            <small>IP: ${entry.ip}</small>
                        </div>
                        <small>${entry.time}</small>
                    </div>
                `);
                $list.append($entry);
            });
        },

        // Set loading state
        setLoading: function(loading) {
            const $form = $('#vd-license-form');
            const $btn = $form.find('button[type="submit"]');

            if (loading) {
                $form.addClass('vd-loading');
                $btn.html('<span class="vd-spinner"></span> Đang xử lý...');
            } else {
                $form.removeClass('vd-loading');
                $btn.html('🔓 Truy Cập');
            }
        },

        // Show error message
        showError: function(message) {
            // Remove existing error
            $('.vd-error').remove();

            // Add new error
            const $error = $(`<div class="vd-error">${message}</div>`);
            $('#vd-license-form').after($error);

            // Auto-hide after 5 seconds
            setTimeout(() => {
                $error.fadeOut(() => $error.remove());
            }, 5000);

            console.warn('VD Portal Error:', message);
        },

        // Handle copy to clipboard
        handleCopy: function(e) {
            e.preventDefault();

            const $btn = $(e.target);
            const targetId = $btn.data('copy-target');
            const $target = $('#' + targetId);

            if (!$target.length) {
                console.warn('VD Portal: Copy target not found:', targetId);
                return;
            }

            const textToCopy = $target.text().trim();

            // Use Clipboard API if available
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(textToCopy).then(() => {
                    this.showCopySuccess($btn);
                }).catch((err) => {
                    console.warn('VD Portal: Clipboard API failed:', err);
                    this.fallbackCopy(textToCopy, $btn);
                });
            } else {
                this.fallbackCopy(textToCopy, $btn);
            }
        },

        // Fallback copy method for older browsers
        fallbackCopy: function(text, $btn) {
            const $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();

            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    this.showCopySuccess($btn);
                } else {
                    this.showCopyError($btn);
                }
            } catch (err) {
                console.warn('VD Portal: Fallback copy failed:', err);
                this.showCopyError($btn);
            }

            $temp.remove();
        },

        // Show copy success feedback
        showCopySuccess: function($btn) {
            const originalIcon = $btn.html();
            $btn.html('✅').addClass('copied');

            setTimeout(() => {
                $btn.html(originalIcon).removeClass('copied');
            }, 2000);
        },

        // Show copy error feedback
        showCopyError: function($btn) {
            const originalIcon = $btn.html();
            $btn.html('❌').addClass('copy-error');

            setTimeout(() => {
                $btn.html(originalIcon).removeClass('copy-error');
            }, 2000);
        },

        // Reset form to initial state
        resetForm: function() {
            $('#license-key').val('');
            $('#license-info, #credentials, #usage-info, #actions').slideUp();
            $('#device-list, #history-list').addClass('vd-empty-state').html('<p>📱 Nhập mã license để xem thiết bị</p>');
            $('.vd-error').fadeOut();
            console.log('VD Portal: Form reset');
        },

        // Refresh data (placeholder for future API integration)
        refreshData: function() {
            const licenseKey = $('#display-license').text();
            if (licenseKey && licenseKey !== '-') {
                this.setLoading(true);
                setTimeout(() => {
                    this.setLoading(false);
                    this.displayMockData(licenseKey);
                    console.log('VD Portal: Data refreshed');
                }, 1000);
            }
        }
    };

    // Initialize portal
    VDPortal.init();

    // Layout verification
    setTimeout(function() {
        const $container = $('.vd-portal-container');
        const display = $container.css('display');

        if (display === 'flex') {
            console.log('✅ VD Portal: Two-column layout active');
        } else {
            console.warn('❌ VD Portal: Layout issue detected');
        }
    }, 100);

});