/**
 * VD Portal JavaScript - REST API Implementation
 * Handles form interactions, tab switching, device tracking, and real API calls
 */

(function($) {
    'use strict';

    // Global device tracking variables
    let deviceFingerprint = null;
    let deviceToken = null;
    let deviceCombinedId = null;

    /**
     * Generate random token
     */
    function generateRandomToken() {
        return Math.random().toString(36).substring(2, 15) +
               Math.random().toString(36).substring(2, 15);
    }

    /**
     * Generate SHA256 hash for combined ID
     */
    async function generateCombinedId(fingerprint, token) {
        const combined = fingerprint + '|' + token;
        const msgBuffer = new TextEncoder().encode(combined);
        const hashBuffer = await crypto.subtle.digest('SHA-256', msgBuffer);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    }

    /**
     * Initialize device fingerprinting
     */
    async function initFingerprint() {
        try {
            // Try to use FingerprintJS if available
            if (typeof FingerprintJS !== 'undefined') {
                const fp = await FingerprintJS.load();
                const result = await fp.get();
                deviceFingerprint = result.visitorId;
            } else {
                // Fallback: Create fingerprint from browser data
                deviceFingerprint = await generateBrowserFingerprint();
            }

            deviceToken = 'dt_' + generateRandomToken();
            deviceCombinedId = await generateCombinedId(deviceFingerprint, deviceToken);

            console.log('VD Portal: Device fingerprint initialized');
            console.log('Fingerprint:', deviceFingerprint);
            console.log('Combined ID:', deviceCombinedId);
        } catch (error) {
            console.error('VD Portal: Fingerprint error', error);

            // Fallback to random IDs
            deviceFingerprint = 'fp_' + generateRandomToken();
            deviceToken = 'dt_' + generateRandomToken();
            deviceCombinedId = await generateCombinedId(deviceFingerprint, deviceToken);
        }
    }

    /**
     * Generate browser fingerprint (fallback)
     */
    async function generateBrowserFingerprint() {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        ctx.textBaseline = 'top';
        ctx.font = '14px Arial';
        ctx.fillText('VD Browser Fingerprint', 2, 2);

        const fingerprint = [
            navigator.userAgent,
            navigator.language,
            screen.width + 'x' + screen.height,
            new Date().getTimezoneOffset(),
            canvas.toDataURL(),
            navigator.hardwareConcurrency || 0
        ].join('|');

        // Simple hash
        let hash = 0;
        for (let i = 0; i < fingerprint.length; i++) {
            const char = fingerprint.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Convert to 32bit integer
        }

        return Math.abs(hash).toString(36);
    }

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

        // Process license with real API call
        processLicense: function(licenseKey) {
            console.log('VD Portal: Processing license:', licenseKey);

            // Show loading state
            this.setLoading(true);

            // Make API call
            $.ajax({
                url: '/wp-json/vd/v1/license/access',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    license_key: licenseKey,
                    device_fingerprint: deviceFingerprint,
                    device_token: deviceToken,
                    device_combined_id: deviceCombinedId,
                    device_name: this.getDeviceName(),
                    user_agent: navigator.userAgent
                }),
                success: (response) => {
                    this.setLoading(false);

                    if (response.success) {
                        this.displayRealData(response);
                    } else {
                        this.showError(response.message || 'Có lỗi xảy ra');
                    }
                },
                error: (xhr) => {
                    this.setLoading(false);

                    let errorMessage = 'Không thể kết nối đến máy chủ';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.status === 404) {
                        errorMessage = 'Mã license không tồn tại';
                    } else if (xhr.status === 403) {
                        errorMessage = 'Truy cập bị từ chối';
                    } else if (xhr.status === 429) {
                        errorMessage = 'Đã vượt quá giới hạn yêu cầu';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Lỗi máy chủ. Vui lòng thử lại sau';
                    }

                    this.showError(errorMessage);
                }
            });
        },

        // Display real data from API response
        displayRealData: function(response) {
            console.log('VD Portal: Displaying real data', response);

            const { license, credentials, devices, usage } = response;

            // Update license info
            $('#display-license').text(license.key);
            $('#display-status').text(license.status === 'active' ? 'Đang hoạt động' : 'Không hoạt động');
            $('#display-expires').text(this.formatDateTime(license.expires_at));
            $('#display-product').text(license.product_name);

            // Show license info section
            $('#license-info').slideDown();

            // Show credentials (DYNAMIC based on API response)
            this.displayCredentials(credentials);

            // Show devices
            this.displayDevices(devices);

            // Show usage info
            this.displayUsageInfo(usage);

            // Show action buttons
            $('#actions').slideDown();

            console.log('VD Portal: Real data displayed');
        },

        // Display usage information
        displayUsageInfo: function(usage) {
            $('#usage-device-count').text(`${usage.devices_used}/${usage.devices_allowed}`);
            $('#usage-request-count').text(`${usage.requests_today}/${usage.requests_allowed}`);
            $('#usage-reset-time').text(usage.reset_at);
            $('#usage-info').slideDown();
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

            if (!devices || devices.length === 0) {
                $list.addClass('vd-empty-state').html(`
                    <p>📱 Chưa có thiết bị nào kết nối</p>
                `);
                return;
            }

            $list.removeClass('vd-empty-state').empty();

            devices.forEach((device, index) => {
                const isCurrent = index === 0; // First device is current
                const statusClass = device.status === 'active' ? 'success' : 'secondary';
                const statusText = device.status === 'active' ? 'Đang dùng' : 'Không hoạt động';

                const $device = $(`
                    <div class="vd-info-item ${!isCurrent ? 'inactive' : ''}">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <strong>${this.escapeHtml(device.device_name)}</strong>
                            <span class="vd-badge vd-badge-${statusClass}">
                                ${statusText}
                            </span>
                        </div>
                        <div style="font-size: 14px; color: #6c757d;">
                            <div>Lần đầu: ${this.formatDateTime(device.first_access_at)}</div>
                            <div>Lần cuối: ${this.formatDateTime(device.last_access_at)}</div>
                            <div>IP: ${this.maskIP(device.last_ip)}</div>
                            <div>Số lần truy cập: ${device.access_count}</div>
                        </div>
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

        // Refresh data (re-call API)
        refreshData: function() {
            const licenseKey = $('#display-license').text();
            if (licenseKey && licenseKey !== '-') {
                this.processLicense(licenseKey);
                console.log('VD Portal: Data refreshed');
            }
        },

        // Helper methods
        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, (m) => map[m]);
        },

        formatDateTime: function(datetime) {
            if (!datetime) return '-';

            const date = new Date(datetime);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');

            return `${day}/${month}/${year} ${hours}:${minutes}`;
        },

        maskIP: function(ip) {
            if (!ip || ip === '0.0.0.0') return 'N/A';

            const parts = ip.split('.');
            if (parts.length === 4) {
                return `${parts[0]}.${parts[1]}.xxx.xxx`;
            }

            return ip;
        },

        getDeviceName: function() {
            const ua = navigator.userAgent;
            let deviceName = '';

            // Detect OS
            if (/Windows NT 10.0/.test(ua)) {
                deviceName = 'Windows 10';
            } else if (/Windows NT 11.0/.test(ua)) {
                deviceName = 'Windows 11';
            } else if (/Mac OS X/.test(ua)) {
                deviceName = 'macOS';
            } else if (/iPhone/.test(ua)) {
                deviceName = 'iPhone';
            } else if (/iPad/.test(ua)) {
                deviceName = 'iPad';
            } else if (/Android/.test(ua)) {
                deviceName = 'Android';
            } else {
                deviceName = 'Unknown Device';
            }

            // Detect browser
            let browser = '';
            if (/Chrome/.test(ua) && !/Edge/.test(ua)) {
                browser = 'Chrome';
            } else if (/Firefox/.test(ua)) {
                browser = 'Firefox';
            } else if (/Safari/.test(ua) && !/Chrome/.test(ua)) {
                browser = 'Safari';
            } else if (/Edge/.test(ua)) {
                browser = 'Edge';
            }

            if (browser) {
                deviceName += ' - ' + browser;
            }

            return deviceName;
        }
    };

    // Wait for DOM ready, then initialize fingerprint, then portal
    $(document).ready(function() {
        // Initialize fingerprint first
        initFingerprint().then(() => {
            // Then initialize portal
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
    });

})(jQuery);