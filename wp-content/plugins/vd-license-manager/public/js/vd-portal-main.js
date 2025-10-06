/**
 * VD License Manager - Portal Main Script
 *
 * Initializes portal and handles all user interactions
 * Vanilla JavaScript with event delegation and auto-refresh
 *
 * @version 1.0.0
 */

(function(window) {
    'use strict';

    // Global portal state
    const VDPortal = {
        licenseKey: null,
        deviceFp: null,
        accountInfo: null,
        devices: null,
        history: null,
        timers: {},
        debounceTimers: {},
        isInitialized: false
    };

    /**
     * Initialize portal
     */
    VDPortal.init = function() {
        if (this.isInitialized) return;

        try {
            // Get portal container
            const container = document.querySelector('.vd-portal-container');
            if (!container) {
                console.warn('VD Portal: Container not found');
                return;
            }

            // Get license key and device fingerprint from data attributes
            this.licenseKey = container.dataset.licenseKey;
            this.deviceFp = container.dataset.deviceFp;

            if (!this.licenseKey) {
                console.error('VD Portal: License key not found');
                return;
            }

            // Generate device fingerprint if not available
            if (!this.deviceFp && window.generateFingerprint) {
                window.generateFingerprint()
                    .then(fp => {
                        this.deviceFp = fp;
                        container.dataset.deviceFp = fp;
                        this.continueInit();
                    })
                    .catch(error => {
                        console.error('VD Portal: Fingerprint generation failed:', error);
                        this.continueInit(); // Continue without fingerprint
                    });
            } else {
                this.continueInit();
            }

        } catch (error) {
            console.error('VD Portal: Initialization failed:', error);
        }
    };

    /**
     * Continue initialization after fingerprint is ready
     */
    VDPortal.continueInit = function() {
        this.setupEventListeners();
        this.loadInitialData();
        this.startAutoRefresh();
        this.isInitialized = true;

        console.log('VD Portal: Initialized successfully');
    };

    /**
     * Load initial data for all sections
     */
    VDPortal.loadInitialData = function() {
        const promises = [];

        // Load account information
        if (window.VDPortalAjax && this.licenseKey && this.deviceFp) {
            this.showSectionLoading('account');

            const accountPromise = window.VDPortalAjax.fetchAccountInfo(this.licenseKey, this.deviceFp)
                .then(data => {
                    this.accountInfo = data;
                    if (window.VDPortalUI) {
                        window.VDPortalUI.updateAccountInfo(data);
                    }
                })
                .catch(error => {
                    console.error('VD Portal: Failed to load account info:', error);
                })
                .finally(() => {
                    this.hideSectionLoading('account');
                });

            promises.push(accountPromise);
        }

        // Load history
        if (window.VDPortalAjax && this.licenseKey) {
            this.showSectionLoading('history');

            const historyPromise = window.VDPortalAjax.fetchHistory(this.licenseKey, 20, 0)
                .then(data => {
                    this.history = data;
                    if (window.VDPortalUI) {
                        window.VDPortalUI.updateHistory(data.events || []);
                    }
                })
                .catch(error => {
                    console.error('VD Portal: Failed to load history:', error);
                })
                .finally(() => {
                    this.hideSectionLoading('history');
                });

            promises.push(historyPromise);
        }

        // Load devices
        if (window.VDPortalAjax && this.licenseKey) {
            this.showSectionLoading('devices');

            const devicesPromise = window.VDPortalAjax.fetchDevices(this.licenseKey)
                .then(data => {
                    this.devices = data;
                    if (window.VDPortalUI) {
                        window.VDPortalUI.updateDevices(data.devices || [], this.deviceFp, data.limits);
                    }
                })
                .catch(error => {
                    console.error('VD Portal: Failed to load devices:', error);
                })
                .finally(() => {
                    this.hideSectionLoading('devices');
                });

            promises.push(devicesPromise);
        }

        return Promise.allSettled(promises);
    };

    /**
     * Setup event listeners with delegation
     */
    VDPortal.setupEventListeners = function() {
        // Global click handler with delegation
        document.addEventListener('click', this.handleClick.bind(this));

        // Global form submission handler
        document.addEventListener('submit', this.handleSubmit.bind(this));

        // Window beforeunload for cleanup
        window.addEventListener('beforeunload', this.cleanup.bind(this));

        // History filter buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.filter-btn')) {
                this.handleHistoryFilter(e);
            }
        });

        // Password toggle
        document.addEventListener('click', (e) => {
            if (e.target.matches('.vd-toggle-password')) {
                this.handlePasswordToggle(e);
            }
        });
    };

    /**
     * Handle click events with delegation
     */
    VDPortal.handleClick = function(e) {
        const target = e.target;

        // Debounce rapid clicks
        if (target.matches('button')) {
            const buttonId = target.id || target.className;
            if (this.debounceTimers[buttonId]) {
                e.preventDefault();
                return;
            }
            this.debounceTimers[buttonId] = setTimeout(() => {
                delete this.debounceTimers[buttonId];
            }, 300);
        }

        // Copy buttons
        if (target.matches('.vd-copy') || target.closest('.vd-copy')) {
            this.handleCopy(e);
        }
        // Download buttons
        else if (target.matches('.vd-download') || target.closest('.vd-download')) {
            this.handleDownload(e);
        }
        // Refresh buttons
        else if (target.matches('.vd-refresh-all, .vd-refresh-info, .vd-refresh-column')) {
            this.handleRefresh(e);
        }
        // Rotation request
        else if (target.matches('.vd-request-rotation')) {
            this.handleRotationRequest(e);
        }
        // Get account info
        else if (target.matches('.vd-get-account')) {
            this.handleGetAccount(e);
        }
        // Logout
        else if (target.matches('.vd-logout')) {
            this.handleLogout(e);
        }
        // Load more
        else if (target.matches('.load-more')) {
            this.handleLoadMore(e);
        }
    };

    /**
     * Handle copy action
     */
    VDPortal.handleCopy = function(e) {
        e.preventDefault();
        const button = e.target.closest('.vd-copy');
        const type = button.dataset.copy;
        const field = document.querySelector(`[data-field="${type}"]`);

        if (field && window.VDPortalUI) {
            window.VDPortalUI.copyToClipboard(field.value, type);
        }
    };

    /**
     * Handle download action
     */
    VDPortal.handleDownload = function(e) {
        e.preventDefault();
        const button = e.target.closest('.vd-download');
        const type = button.dataset.type;
        const format = button.dataset.format || 'json';

        if (type === 'cookie' && this.accountInfo && this.accountInfo.cookie) {
            if (window.VDPortalUI) {
                window.VDPortalUI.downloadCookie(this.accountInfo.cookie.value || this.accountInfo.cookie, format);
            }
        }
    };

    /**
     * Handle refresh actions
     */
    VDPortal.handleRefresh = function(e) {
        e.preventDefault();
        const button = e.target.closest('button');
        const target = button.dataset.target;

        if (target === 'history') {
            this.refreshHistory();
        } else if (target === 'devices') {
            this.refreshDevices();
        } else {
            this.loadInitialData();
        }
    };

    /**
     * Handle rotation request
     */
    VDPortal.handleRotationRequest = function(e) {
        e.preventDefault();

        if (!confirm('Bạn có chắc muốn yêu cầu đổi thiết bị?')) {
            return;
        }

        if (window.VDPortalAjax && this.licenseKey && this.deviceFp) {
            window.VDPortalAjax.requestRotation(this.licenseKey, this.deviceFp, this.deviceFp, 'User requested rotation')
                .then(response => {
                    if (window.VDPortalUI) {
                        window.VDPortalUI.showToast('Yêu cầu đổi thiết bị đã được gửi!', 'success');
                    }
                    this.refreshDevices();
                })
                .catch(error => {
                    console.error('VD Portal: Rotation request failed:', error);
                });
        }
    };

    /**
     * Handle get account info
     */
    VDPortal.handleGetAccount = function(e) {
        e.preventDefault();

        if (window.VDPortalAjax && this.licenseKey && this.deviceFp) {
            this.showSectionLoading('account');

            window.VDPortalAjax.fetchAccountInfo(this.licenseKey, this.deviceFp)
                .then(data => {
                    this.accountInfo = data;
                    if (window.VDPortalUI) {
                        window.VDPortalUI.updateAccountInfo(data);
                        window.VDPortalUI.showToast('Thông tin tài khoản đã được cập nhật!', 'success');
                    }
                })
                .catch(error => {
                    console.error('VD Portal: Failed to fetch account info:', error);
                })
                .finally(() => {
                    this.hideSectionLoading('account');
                });
        }
    };

    /**
     * Handle logout
     */
    VDPortal.handleLogout = function(e) {
        e.preventDefault();

        if (!confirm('Bạn có chắc muốn đăng xuất?')) {
            return;
        }

        this.cleanup();
        window.location.reload();
    };

    /**
     * Handle history filter
     */
    VDPortal.handleHistoryFilter = function(e) {
        e.preventDefault();
        const button = e.target;
        const filter = button.dataset.filter;

        // Update active state
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');

        // Apply filter to timeline items
        const items = document.querySelectorAll('.timeline-item');
        items.forEach(item => {
            if (filter === 'all' || item.classList.contains(filter)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    };

    /**
     * Handle password toggle
     */
    VDPortal.handlePasswordToggle = function(e) {
        e.preventDefault();
        const button = e.target.closest('.vd-toggle-password');
        const targetId = button.dataset.target;
        const field = document.getElementById(targetId);

        if (field) {
            const isPassword = field.type === 'password';
            field.type = isPassword ? 'text' : 'password';

            const showIcon = button.querySelector('.show-icon');
            const hideIcon = button.querySelector('.hide-icon');
            const showText = button.querySelector('.show-text');
            const hideText = button.querySelector('.hide-text');

            if (isPassword) {
                showIcon.style.display = 'none';
                hideIcon.style.display = 'inline';
                showText.style.display = 'none';
                hideText.style.display = 'inline';
            } else {
                showIcon.style.display = 'inline';
                hideIcon.style.display = 'none';
                showText.style.display = 'inline';
                hideText.style.display = 'none';
            }
        }
    };

    /**
     * Start auto-refresh timers
     */
    VDPortal.startAutoRefresh = function() {
        // TOTP countdown (every second)
        this.timers.totp = setInterval(() => {
            this.updateTOTPCountdown();
        }, 1000);

        // Optional: Auto-refresh account info every 5 minutes
        // this.timers.account = setInterval(() => {
        //     this.refreshAccountInfo();
        // }, 5 * 60 * 1000);
    };

    /**
     * Stop auto-refresh timers
     */
    VDPortal.stopAutoRefresh = function() {
        Object.keys(this.timers).forEach(key => {
            clearInterval(this.timers[key]);
            delete this.timers[key];
        });
    };

    /**
     * Update TOTP countdown
     */
    VDPortal.updateTOTPCountdown = function() {
        const now = Math.floor(Date.now() / 1000);
        const timeLeft = 30 - (now % 30);

        if (window.VDPortalUI) {
            window.VDPortalUI.updateTOTPCountdown(timeLeft);
        }
    };

    /**
     * Show loading for specific section
     */
    VDPortal.showSectionLoading = function(section) {
        const element = document.getElementById(`vd-${section}-timeline`) ||
                       document.getElementById(`vd-${section}-data`) ||
                       document.getElementById(`vd-${section}-list`);

        if (element && window.VDPortalUI) {
            window.VDPortalUI.showLoading(element);
        }
    };

    /**
     * Hide loading for specific section
     */
    VDPortal.hideSectionLoading = function(section) {
        const element = document.getElementById(`vd-${section}-timeline`) ||
                       document.getElementById(`vd-${section}-data`) ||
                       document.getElementById(`vd-${section}-list`);

        if (element && window.VDPortalUI) {
            window.VDPortalUI.hideLoading(element);
        }
    };

    /**
     * Refresh specific sections
     */
    VDPortal.refreshHistory = function() {
        if (window.VDPortalAjax && this.licenseKey) {
            this.showSectionLoading('history');

            window.VDPortalAjax.fetchHistory(this.licenseKey, 20, 0)
                .then(data => {
                    this.history = data;
                    if (window.VDPortalUI) {
                        window.VDPortalUI.updateHistory(data.events || []);
                    }
                })
                .finally(() => {
                    this.hideSectionLoading('history');
                });
        }
    };

    VDPortal.refreshDevices = function() {
        if (window.VDPortalAjax && this.licenseKey) {
            this.showSectionLoading('devices');

            window.VDPortalAjax.fetchDevices(this.licenseKey)
                .then(data => {
                    this.devices = data;
                    if (window.VDPortalUI) {
                        window.VDPortalUI.updateDevices(data.devices || [], this.deviceFp, data.limits);
                    }
                })
                .finally(() => {
                    this.hideSectionLoading('devices');
                });
        }
    };

    /**
     * Cleanup function
     */
    VDPortal.cleanup = function() {
        this.stopAutoRefresh();

        // Clear debounce timers
        Object.keys(this.debounceTimers).forEach(key => {
            clearTimeout(this.debounceTimers[key]);
        });
        this.debounceTimers = {};

        // Clear fingerprint if needed
        if (window.VDFingerprint) {
            window.VDFingerprint.clearFingerprint();
        }
    };

    // Export to global scope
    window.VDPortal = VDPortal;

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', () => {
        VDPortal.init();
    });

})(window);