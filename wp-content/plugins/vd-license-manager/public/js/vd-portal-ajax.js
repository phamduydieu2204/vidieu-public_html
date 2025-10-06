/**
 * VD License Manager - Portal AJAX Functions
 *
 * Handles API communication for the customer portal
 * Uses Fetch API with promise-based error handling
 *
 * @version 1.0.0
 */

(function(window) {
    'use strict';

    const VDPortalAjax = {

        // API base URL (will be set from localized script)
        apiBase: window.vdPortal ? window.vdPortal.rest_url : '/wp-json/vd/v1/',

        /**
         * Fetch account information
         *
         * @param {string} licenseKey License key
         * @param {string} deviceFp Device fingerprint
         * @returns {Promise<object>} Account information
         */
        fetchAccountInfo: function(licenseKey, deviceFp) {
            const endpoint = 'account/info';
            const headers = {
                'Authorization': 'Bearer ' + licenseKey,
                'X-Device-Fingerprint': deviceFp,
                'X-WP-Nonce': window.vdPortal ? window.vdPortal.nonce : ''
            };

            return this.apiRequest(endpoint, 'GET', null, headers)
                .then(response => {
                    if (response.success) {
                        return response.data;
                    } else {
                        throw new Error(response.message || 'Failed to fetch account info');
                    }
                })
                .catch(error => {
                    this.handleApiError(error, 'Could not load account information');
                    throw error;
                });
        },

        /**
         * Fetch access history
         *
         * @param {string} licenseKey License key
         * @param {number} limit Number of items to fetch
         * @param {number} offset Starting offset
         * @returns {Promise<object>} History data
         */
        fetchHistory: function(licenseKey, limit = 20, offset = 0) {
            const params = new URLSearchParams({
                license_key: licenseKey,
                limit: limit.toString(),
                offset: offset.toString()
            });

            const endpoint = 'history?' + params.toString();
            const headers = {
                'X-WP-Nonce': window.vdPortal ? window.vdPortal.nonce : ''
            };

            return this.apiRequest(endpoint, 'GET', null, headers)
                .then(response => {
                    if (response.success) {
                        return response.data;
                    } else {
                        throw new Error(response.message || 'Failed to fetch history');
                    }
                })
                .catch(error => {
                    this.handleApiError(error, 'Could not load access history');
                    throw error;
                });
        },

        /**
         * Verify device
         *
         * @param {string} licenseKey License key
         * @param {object} fpData Fingerprint data
         * @returns {Promise<object>} Verification result
         */
        verifyDevice: function(licenseKey, fpData) {
            const endpoint = 'device/verify';
            const deviceInfo = window.getDeviceInfo ? window.getDeviceInfo() : {};

            const data = {
                license_key: licenseKey,
                fp: fpData,
                ua: deviceInfo.user_agent || navigator.userAgent,
                os: deviceInfo.os || 'Unknown',
                screen: deviceInfo.screen || 'Unknown'
            };

            const headers = {
                'Content-Type': 'application/json',
                'X-WP-Nonce': window.vdPortal ? window.vdPortal.nonce : ''
            };

            return this.apiRequest(endpoint, 'POST', data, headers)
                .then(response => {
                    if (response.success) {
                        return response.data;
                    } else {
                        throw new Error(response.message || 'Device verification failed');
                    }
                })
                .catch(error => {
                    this.handleApiError(error, 'Device verification failed');
                    throw error;
                });
        },

        /**
         * Request device rotation
         *
         * @param {string} licenseKey License key
         * @param {string} oldFp Old device fingerprint
         * @param {string} newFp New device fingerprint
         * @param {string} reason Rotation reason
         * @returns {Promise<object>} Rotation result
         */
        requestRotation: function(licenseKey, oldFp, newFp, reason = '') {
            const endpoint = 'device/rotate';

            const data = {
                license_key: licenseKey,
                old_device_fp: oldFp,
                new_device_fp: newFp,
                reason: reason || 'User requested rotation'
            };

            const headers = {
                'Content-Type': 'application/json',
                'X-WP-Nonce': window.vdPortal ? window.vdPortal.nonce : ''
            };

            return this.apiRequest(endpoint, 'POST', data, headers)
                .then(response => {
                    if (response.success) {
                        return response.data;
                    } else {
                        throw new Error(response.message || 'Rotation request failed');
                    }
                })
                .catch(error => {
                    this.handleApiError(error, 'Could not rotate device');
                    throw error;
                });
        },

        /**
         * Fetch device list
         *
         * @param {string} licenseKey License key
         * @returns {Promise<object>} Device list
         */
        fetchDevices: function(licenseKey) {
            const params = new URLSearchParams({
                license_key: licenseKey
            });

            const endpoint = 'devices?' + params.toString();
            const headers = {
                'X-WP-Nonce': window.vdPortal ? window.vdPortal.nonce : ''
            };

            return this.apiRequest(endpoint, 'GET', null, headers)
                .then(response => {
                    if (response.success) {
                        return response.data;
                    } else {
                        throw new Error(response.message || 'Failed to fetch devices');
                    }
                })
                .catch(error => {
                    this.handleApiError(error, 'Could not load device list');
                    throw error;
                });
        },

        /**
         * Generic API request helper
         *
         * @param {string} endpoint API endpoint
         * @param {string} method HTTP method
         * @param {object|null} data Request data
         * @param {object} headers Request headers
         * @returns {Promise<object>} API response
         */
        apiRequest: function(endpoint, method = 'GET', data = null, headers = {}) {
            const url = this.apiBase + endpoint.replace(/^\//, '');

            const config = {
                method: method.toUpperCase(),
                headers: {
                    'Accept': 'application/json',
                    ...headers
                }
            };

            // Add request body for POST/PUT requests
            if (data && ['POST', 'PUT', 'PATCH'].includes(config.method)) {
                if (headers['Content-Type'] === 'application/json') {
                    config.body = JSON.stringify(data);
                } else {
                    // Form data
                    const formData = new FormData();
                    Object.keys(data).forEach(key => {
                        formData.append(key, data[key]);
                    });
                    config.body = formData;
                }
            }

            return fetch(url, config)
                .then(response => {
                    // Check if response is OK
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }

                    // Check content type
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    } else {
                        throw new Error('Invalid response format (expected JSON)');
                    }
                })
                .catch(error => {
                    // Handle network errors
                    if (error.name === 'TypeError' && error.message.includes('fetch')) {
                        throw new Error('Network error - please check your connection');
                    }
                    throw error;
                });
        },

        /**
         * Handle API errors
         *
         * @param {Error} error Error object
         * @param {string} userMessage User-friendly message
         */
        handleApiError: function(error, userMessage = 'An error occurred') {
            // Log detailed error for debugging
            if (window.vdPortal && window.vdPortal.debug) {
                console.error('VD Portal API Error:', error);
            }

            // Determine user-friendly message
            let displayMessage = userMessage;

            if (error.message) {
                if (error.message.includes('Network error')) {
                    displayMessage = 'Kết nối mạng bị lỗi. Vui lòng thử lại.';
                } else if (error.message.includes('HTTP 401')) {
                    displayMessage = 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.';
                } else if (error.message.includes('HTTP 403')) {
                    displayMessage = 'Bạn không có quyền thực hiện thao tác này.';
                } else if (error.message.includes('HTTP 404')) {
                    displayMessage = 'Không tìm thấy dữ liệu yêu cầu.';
                } else if (error.message.includes('HTTP 429')) {
                    displayMessage = 'Quá nhiều yêu cầu. Vui lòng chờ và thử lại.';
                } else if (error.message.includes('HTTP 5')) {
                    displayMessage = 'Lỗi máy chủ. Vui lòng thử lại sau.';
                }
            }

            // Show error notification
            this.showNotification(displayMessage, 'error');
        },

        /**
         * Show notification to user
         *
         * @param {string} message Notification message
         * @param {string} type Notification type (success, error, warning, info)
         */
        showNotification: function(message, type = 'info') {
            // Try to use existing notification system
            if (window.VDPortalUI && typeof window.VDPortalUI.showNotification === 'function') {
                window.VDPortalUI.showNotification(message, type);
                return;
            }

            // Fallback to simple alert or console
            if (type === 'error') {
                console.error('VD Portal:', message);
                // Could show a simple modal or toast here
            } else {
                console.log('VD Portal:', message);
            }

            // Try to update portal message area if it exists
            const messageArea = document.getElementById('vd-portal-messages');
            if (messageArea) {
                messageArea.innerHTML = `<div class="vd-message vd-${type}">${message}</div>`;
                messageArea.style.display = 'block';

                // Auto-hide after 5 seconds
                setTimeout(() => {
                    if (messageArea.innerHTML.includes(message)) {
                        messageArea.style.display = 'none';
                    }
                }, 5000);
            }
        },

        /**
         * Check if user is authenticated
         *
         * @returns {boolean} Authentication status
         */
        isAuthenticated: function() {
            return !!(window.vdPortal && window.vdPortal.nonce);
        },

        /**
         * Get authentication headers
         *
         * @returns {object} Headers object
         */
        getAuthHeaders: function() {
            const headers = {};

            if (window.vdPortal && window.vdPortal.nonce) {
                headers['X-WP-Nonce'] = window.vdPortal.nonce;
            }

            return headers;
        }
    };

    // Export to global scope
    window.VDPortalAjax = VDPortalAjax;

    // Create convenience functions
    window.fetchAccountInfo = function(licenseKey, deviceFp) {
        return VDPortalAjax.fetchAccountInfo(licenseKey, deviceFp);
    };

    window.fetchHistory = function(licenseKey, limit, offset) {
        return VDPortalAjax.fetchHistory(licenseKey, limit, offset);
    };

    window.verifyDevice = function(licenseKey, fpData) {
        return VDPortalAjax.verifyDevice(licenseKey, fpData);
    };

    window.requestRotation = function(licenseKey, oldFp, newFp, reason) {
        return VDPortalAjax.requestRotation(licenseKey, oldFp, newFp, reason);
    };

})(window);