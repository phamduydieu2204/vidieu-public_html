/**
 * VD License Manager - Portal UI Interactions
 *
 * Handles dynamic content updates and user interactions
 * Vanilla JavaScript implementation with accessibility support
 *
 * @version 1.0.0
 */

(function(window) {
    'use strict';

    const VDPortalUI = {

        /**
         * Update account information section
         *
         * @param {object} data Account data from API
         */
        updateAccountInfo: function(data) {
            const container = document.getElementById('vd-account-data');
            if (!container) return;

            this.hideLoading(container);

            if (!data || Object.keys(data).length === 0) {
                container.innerHTML = '<div class="vd-no-account"><p>Chưa có thông tin tài khoản.</p></div>';
                return;
            }

            let html = '<div class="vd-account-section">';

            // Instructions
            if (data.instructions) {
                html += `<div class="vd-instructions"><p>${data.instructions}</p></div>`;
            }

            // Cookie field
            if (data.cookie) {
                html += this.renderCookieField(data.cookie);
            }

            // Login credentials
            if (data.login_email) {
                html += this.renderTextField('📧 Email đăng nhập', data.login_email, 'email');
            }

            if (data.login_password) {
                html += this.renderPasswordField('🔒 Mật khẩu', data.login_password);
            }

            // TOTP
            if (data.totp_secret) {
                html += this.renderTOTPField(data.totp_secret);
            }

            // Recovery info
            if (data.recovery_email) {
                html += this.renderTextField('📮 Email khôi phục', data.recovery_email, 'recovery', true);
            }

            if (data.recovery_phone) {
                html += this.renderTextField('📞 Số điện thoại', data.recovery_phone, 'phone', true);
            }

            html += '</div>';
            container.innerHTML = html;

            // Initialize TOTP countdown if present
            if (data.totp_secret) {
                this.initTOTPCountdown();
            }

            // Bind copy buttons
            this.bindCopyButtons();
        },

        /**
         * Update history timeline
         *
         * @param {array} timeline Timeline events
         * @param {boolean} append Whether to append or replace
         */
        updateHistory: function(timeline, append = false) {
            const container = document.getElementById('vd-history-timeline');
            if (!container) return;

            this.hideLoading(container);

            if (!timeline || timeline.length === 0) {
                if (!append) {
                    container.innerHTML = '<div class="timeline-empty"><p>Chưa có lịch sử truy cập.</p></div>';
                }
                return;
            }

            let html = '';
            timeline.forEach(event => {
                html += this.renderTimelineEvent(event);
            });

            if (append) {
                container.insertAdjacentHTML('beforeend', html);
            } else {
                container.innerHTML = `<div class="timeline">${html}</div>`;
            }
        },

        /**
         * Update devices section
         *
         * @param {array} devices Device list
         * @param {string} currentFp Current device fingerprint
         * @param {object} limitInfo Device limit information
         */
        updateDevices: function(devices, currentFp, limitInfo = {}) {
            const container = document.getElementById('vd-device-list');
            if (!container) return;

            this.hideLoading(container);

            if (!devices || devices.length === 0) {
                container.innerHTML = '<div class="no-devices"><p>Chưa có thiết bị nào được đăng ký.</p></div>';
                return;
            }

            let html = '';
            devices.forEach(device => {
                const isCurrent = device.fp === currentFp;
                html += this.renderDeviceCard(device, isCurrent);
            });

            container.innerHTML = html;

            // Update limit bar if provided
            if (limitInfo && Object.keys(limitInfo).length > 0) {
                this.updateLimitBar(limitInfo);
            }
        },

        /**
         * Copy text to clipboard
         *
         * @param {string} text Text to copy
         * @param {string} type Type of content (for user feedback)
         */
        copyToClipboard: function(text, type = 'text') {
            const typeLabels = {
                'email': 'Email',
                'password': 'Mật khẩu',
                'totp': 'Mã 2FA',
                'cookie': 'Cookie',
                'text': 'Văn bản'
            };

            const label = typeLabels[type] || typeLabels.text;

            // Try modern Clipboard API first
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text)
                    .then(() => {
                        this.showToast(`${label} đã được sao chép!`, 'success');
                    })
                    .catch(() => {
                        this.fallbackCopy(text, label);
                    });
            } else {
                this.fallbackCopy(text, label);
            }
        },

        /**
         * Fallback copy method for older browsers
         *
         * @param {string} text Text to copy
         * @param {string} label Content label
         */
        fallbackCopy: function(text, label) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                document.execCommand('copy');
                this.showToast(`${label} đã được sao chép!`, 'success');
            } catch (err) {
                this.showToast('Không thể sao chép. Vui lòng chọn và copy thủ công.', 'error');
            }

            document.body.removeChild(textArea);
        },

        /**
         * Download cookie file
         *
         * @param {string} cookie Cookie data
         * @param {string} format File format (json/netscape)
         */
        downloadCookie: function(cookie, format = 'json') {
            let content, mimeType, extension;

            if (format === 'netscape') {
                content = this.convertToNetscape(cookie);
                mimeType = 'text/plain';
                extension = 'txt';
            } else {
                content = JSON.stringify(JSON.parse(cookie), null, 2);
                mimeType = 'application/json';
                extension = 'json';
            }

            const blob = new Blob([content], { type: mimeType });
            const url = URL.createObjectURL(blob);
            const timestamp = new Date().toISOString().slice(0, 10);
            const filename = `cookie-${timestamp}.${extension}`;

            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.style.display = 'none';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            this.showToast(`Đã tải file ${filename}`, 'success');
        },

        /**
         * Show toast notification
         *
         * @param {string} message Toast message
         * @param {string} type Toast type (success/error/info/warning)
         */
        showToast: function(message, type = 'info') {
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };

            const toast = document.createElement('div');
            toast.className = `vd-toast vd-toast-${type}`;
            toast.innerHTML = `
                <span class="vd-toast-icon">${icons[type] || icons.info}</span>
                <span class="vd-toast-message">${message}</span>
            `;

            document.body.appendChild(toast);

            // Trigger animation
            setTimeout(() => toast.classList.add('vd-toast-show'), 10);

            // Auto-hide after 3 seconds
            setTimeout(() => {
                toast.classList.remove('vd-toast-show');
                setTimeout(() => {
                    if (toast.parentNode) {
                        document.body.removeChild(toast);
                    }
                }, 300);
            }, 3000);
        },

        /**
         * Update TOTP countdown
         *
         * @param {number} expiresIn Seconds until expiry
         */
        updateTOTPCountdown: function(expiresIn) {
            const countdownElement = document.querySelector('.countdown-timer');
            const progressBar = document.querySelector('.totp-progress .progress-bar');

            if (!countdownElement) return;

            countdownElement.textContent = `${expiresIn}s`;

            if (progressBar) {
                const percentage = (expiresIn / 30) * 100;
                progressBar.style.width = `${percentage}%`;
            }

            if (expiresIn <= 0) {
                this.refreshTOTPCode();
            }
        },

        /**
         * Show loading state
         *
         * @param {HTMLElement} element Element to show loading
         */
        showLoading: function(element) {
            if (!element) return;
            element.classList.add('vd-loading');
            element.setAttribute('aria-busy', 'true');
        },

        /**
         * Hide loading state
         *
         * @param {HTMLElement} element Element to hide loading
         */
        hideLoading: function(element) {
            if (!element) return;
            element.classList.remove('vd-loading');
            element.removeAttribute('aria-busy');
        },

        // Helper methods for rendering

        renderCookieField: function(cookie) {
            return `
                <div class="field-group cookie-field">
                    <label>🍪 Cookie (${cookie.format || 'JSON'})</label>
                    <textarea readonly data-field="cookie">${cookie.value || cookie}</textarea>
                    <div class="field-actions">
                        <button class="vd-copy" data-copy="cookie">📋 Copy</button>
                        <button class="vd-download" data-type="cookie">📥 Tải file</button>
                    </div>
                </div>
            `;
        },

        renderTextField: function(label, value, type, readonly = false) {
            return `
                <div class="field-group ${type}-field">
                    <label>${label}</label>
                    ${readonly ?
                        `<span class="field-value">${value}</span>` :
                        `<input type="text" readonly value="${value}" data-field="${type}">
                         <button class="vd-copy" data-copy="${type}">📋 Copy</button>`
                    }
                </div>
            `;
        },

        renderPasswordField: function(label, value) {
            return `
                <div class="field-group password-field">
                    <label>${label}</label>
                    <input type="password" readonly value="${value}" data-field="password" id="vd-password">
                    <div class="field-actions">
                        <button class="vd-toggle-password" data-target="vd-password">👁 Hiện</button>
                        <button class="vd-copy" data-copy="password">📋 Copy</button>
                    </div>
                </div>
            `;
        },

        renderTOTPField: function(secret) {
            return `
                <div class="field-group totp-field">
                    <label>🔢 Mã 2FA</label>
                    <input type="text" readonly value="${secret}" data-field="totp">
                    <div class="vd-totp-current">
                        <span class="current-code">------</span>
                        <span class="countdown-timer">30s</span>
                        <div class="totp-progress"><div class="progress-bar"></div></div>
                    </div>
                    <button class="vd-copy" data-copy="totp">📋 Copy Secret</button>
                </div>
            `;
        },

        renderTimelineEvent: function(event) {
            const statusClass = event.status || 'unknown';
            const eventIcon = this.getEventIcon(event.type, event.status);

            return `
                <div class="timeline-item ${event.type} ${statusClass}">
                    <div class="timeline-icon">${eventIcon}</div>
                    <div class="timeline-content">
                        <div class="event-time">${this.formatTime(event.timestamp)}</div>
                        <div class="event-details">${event.message || event.action || ''}</div>
                        <span class="status-badge status-${statusClass}">${this.getStatusText(event.status)}</span>
                    </div>
                </div>
            `;
        },

        renderDeviceCard: function(device, isCurrent) {
            const currentClass = isCurrent ? 'current' : '';
            const deviceIcon = this.getDeviceIcon(device.os || '');

            return `
                <div class="device-card ${device.status} ${currentClass}">
                    <div class="device-icon">${deviceIcon}</div>
                    <div class="device-info">
                        <strong>${device.os || 'Unknown OS'}</strong>
                        <span>${device.browser || 'Unknown Browser'}</span>
                        ${isCurrent ? '<span class="badge current">🔥 Đang dùng</span>' : ''}
                    </div>
                    <div class="device-meta">
                        <small>Lần cuối: ${this.formatTime(device.last_used_at)}</small>
                    </div>
                    <span class="status-badge status-${device.status}">${this.getStatusText(device.status)}</span>
                </div>
            `;
        },

        // Utility methods

        initTOTPCountdown: function() {
            // This would start a countdown timer for TOTP refresh
            // Implementation depends on TOTP library integration
        },

        refreshTOTPCode: function() {
            // This would generate a new TOTP code
            // Implementation depends on TOTP library integration
        },

        bindCopyButtons: function() {
            document.querySelectorAll('.vd-copy').forEach(button => {
                button.addEventListener('click', (e) => {
                    const type = e.target.dataset.copy;
                    const field = document.querySelector(`[data-field="${type}"]`);
                    if (field) {
                        this.copyToClipboard(field.value, type);
                    }
                });
            });
        },

        convertToNetscape: function(cookieJson) {
            // Convert JSON cookie to Netscape format
            // This is a simplified version
            return cookieJson; // Would need proper conversion logic
        },

        updateLimitBar: function(limitInfo) {
            const progressBar = document.querySelector('.limit-progress');
            if (progressBar && limitInfo.max > 0) {
                const percentage = (limitInfo.used / limitInfo.max) * 100;
                progressBar.style.width = `${percentage}%`;
            }
        },

        getEventIcon: function(type, status) {
            if (type === 'fetch' && status === 'success') return '✅';
            if (type === 'fetch' && status === 'failed') return '❌';
            if (type === 'access' && status === 'success') return '🔍';
            return '❓';
        },

        getDeviceIcon: function(os) {
            if (os.includes('Windows') || os.includes('Mac')) return '💻';
            if (os.includes('Android') || os.includes('iOS')) return '📱';
            return '🖥️';
        },

        getStatusText: function(status) {
            const texts = {
                'success': 'Thành công',
                'failed': 'Thất bại',
                'blocked': 'Bị chặn',
                'pending': 'Chờ duyệt'
            };
            return texts[status] || status;
        },

        formatTime: function(timestamp) {
            if (!timestamp) return '';
            return new Date(timestamp).toLocaleString('vi-VN');
        }
    };

    // Export to global scope
    window.VDPortalUI = VDPortalUI;

})(window);