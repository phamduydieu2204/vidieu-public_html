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
                this.showError('Please enter a license key');
                return;
            }

            if (!this.isValidLicenseFormat(licenseKey)) {
                this.showError('Invalid license key format. Please use: XXXX-XXXX-XXXX-XXXX');
                return;
            }

            this.processLicense(licenseKey);
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

                // Mock data for demonstration
                if (licenseKey === 'DEMO-DEMO-DEMO-DEMO') {
                    this.showLicenseData({
                        license: licenseKey,
                        status: 'Active',
                        expires: '2024-12-31',
                        product: 'Premium Account',
                        credentials: {
                            'Login Email': 'demo@example.com',
                            'Password': '••••••••••',
                            'API Key': 'ak_demo_123456789'
                        },
                        devices: [
                            { name: 'Chrome Browser', ip: '192.168.1.100', last_seen: '2 minutes ago' },
                            { name: 'Mobile App', ip: '10.0.0.5', last_seen: '1 hour ago' }
                        ],
                        history: [
                            { action: 'Login', time: '2024-01-15 14:30', ip: '192.168.1.100' },
                            { action: 'API Access', time: '2024-01-15 14:25', ip: '192.168.1.100' },
                            { action: 'Mobile Login', time: '2024-01-15 13:15', ip: '10.0.0.5' }
                        ]
                    });
                } else {
                    this.showError('License key not found or invalid');
                }
            }, 1500);
        },

        // Show license data
        showLicenseData: function(data) {
            // Update license info
            $('#display-license').text(data.license);
            $('#display-status').text(data.status);
            $('#display-expires').text(data.expires);

            // Show license info section
            $('#license-info').slideDown();

            // Show credentials
            this.displayCredentials(data.credentials);

            // Show devices
            this.displayDevices(data.devices);

            // Show history
            this.displayHistory(data.history);

            console.log('VD Portal: License data displayed');
        },

        // Display credentials
        displayCredentials: function(credentials) {
            const $list = $('#credentials-list');
            $list.empty();

            Object.entries(credentials).forEach(([key, value]) => {
                const $item = $(`
                    <div class="vd-info-item">
                        <span class="label">${key}:</span>
                        <span class="value">${value}</span>
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
                $btn.html('<span class="vd-spinner"></span> Processing...');
            } else {
                $form.removeClass('vd-loading');
                $btn.html('🔓 Access License');
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