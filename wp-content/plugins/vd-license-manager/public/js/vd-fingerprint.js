/**
 * VD License Manager - Device Fingerprinting
 *
 * Collects device characteristics for identification purposes
 * Vanilla JavaScript implementation with privacy considerations
 *
 * @version 1.0.0
 */

(function(window) {
    'use strict';

    const VDFingerprint = {

        /**
         * Collect device fingerprint data
         *
         * @returns {Object} Fingerprint data object
         */
        collectFingerprint: function() {
            const data = {};

            try {
                // User Agent
                data.ua = navigator.userAgent || '';

                // Screen information
                data.screen = (screen.width && screen.height) ?
                    screen.width + 'x' + screen.height : 'unknown';
                data.colorDepth = screen.colorDepth || 0;
                data.pixelRatio = window.devicePixelRatio || 1;

                // Language and platform
                data.language = navigator.language || navigator.userLanguage || '';
                data.languages = navigator.languages ? navigator.languages.join(',') : '';
                data.platform = navigator.platform || '';

                // Timezone
                data.timezone = new Date().getTimezoneOffset();

                // Hardware concurrency (CPU cores)
                data.cores = navigator.hardwareConcurrency || 0;

                // Memory (if available)
                data.memory = navigator.deviceMemory || 0;

                // Touch support
                data.touch = 'ontouchstart' in window ? 1 : 0;

                // Canvas fingerprint (simple version)
                data.canvas = this.getCanvasFingerprint();

                // WebGL information
                data.webgl = this.getWebGLFingerprint();

                // Audio context (basic)
                data.audio = this.getAudioFingerprint();

                // Local storage availability
                data.localStorage = this.checkLocalStorage();
                data.sessionStorage = this.checkSessionStorage();

                // Cookie support
                data.cookies = navigator.cookieEnabled ? 1 : 0;

                // Do Not Track
                data.dnt = navigator.doNotTrack || navigator.msDoNotTrack || '';

            } catch (error) {
                console.warn('VD Fingerprint: Error collecting data:', error);
            }

            return data;
        },

        /**
         * Generate canvas fingerprint
         *
         * @returns {string} Canvas fingerprint
         */
        getCanvasFingerprint: function() {
            try {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');

                if (!ctx) return 'no-canvas';

                canvas.width = 200;
                canvas.height = 50;

                // Set drawing properties
                ctx.textBaseline = 'top';
                ctx.font = '14px Arial';
                ctx.fillStyle = '#f60';
                ctx.fillRect(125, 1, 62, 20);

                // Add text
                ctx.fillStyle = '#069';
                ctx.fillText('VD License Manager 🔑', 2, 2);

                // Add more complex shapes
                ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
                ctx.fillText('Canvas FP Test', 4, 17);

                // Add gradient
                ctx.globalCompositeOperation = 'multiply';
                const gradient = ctx.createLinearGradient(0, 0, 150, 0);
                gradient.addColorStop(0, 'red');
                gradient.addColorStop(1, 'blue');
                ctx.fillStyle = gradient;
                ctx.fillRect(0, 0, 150, 50);

                return canvas.toDataURL();

            } catch (error) {
                return 'canvas-error';
            }
        },

        /**
         * Generate WebGL fingerprint
         *
         * @returns {string} WebGL fingerprint
         */
        getWebGLFingerprint: function() {
            try {
                const canvas = document.createElement('canvas');
                const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');

                if (!gl) return 'no-webgl';

                const vendor = gl.getParameter(gl.VENDOR) || '';
                const renderer = gl.getParameter(gl.RENDERER) || '';
                const version = gl.getParameter(gl.VERSION) || '';
                const shadingLanguage = gl.getParameter(gl.SHADING_LANGUAGE_VERSION) || '';

                return [vendor, renderer, version, shadingLanguage].join('|');

            } catch (error) {
                return 'webgl-error';
            }
        },

        /**
         * Generate audio fingerprint
         *
         * @returns {string} Audio fingerprint
         */
        getAudioFingerprint: function() {
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return 'no-audio';

                const context = new AudioContext();
                const sampleRate = context.sampleRate || 0;
                const maxChannels = context.destination.maxChannelCount || 0;

                context.close();

                return sampleRate + '|' + maxChannels;

            } catch (error) {
                return 'audio-error';
            }
        },

        /**
         * Check local storage availability
         *
         * @returns {number} 1 if available, 0 if not
         */
        checkLocalStorage: function() {
            try {
                const test = 'vd-test';
                localStorage.setItem(test, test);
                localStorage.removeItem(test);
                return 1;
            } catch (error) {
                return 0;
            }
        },

        /**
         * Check session storage availability
         *
         * @returns {number} 1 if available, 0 if not
         */
        checkSessionStorage: function() {
            try {
                const test = 'vd-test';
                sessionStorage.setItem(test, test);
                sessionStorage.removeItem(test);
                return 1;
            } catch (error) {
                return 0;
            }
        },

        /**
         * Hash fingerprint data using crypto API or fallback
         *
         * @param {Object} data Fingerprint data
         * @returns {Promise<string>} Hash string
         */
        hashFingerprint: function(data) {
            const dataString = JSON.stringify(data);

            // Try SubtleCrypto API first
            if (window.crypto && window.crypto.subtle) {
                return crypto.subtle.digest('SHA-256', new TextEncoder().encode(dataString))
                    .then(hashBuffer => {
                        const hashArray = Array.from(new Uint8Array(hashBuffer));
                        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
                    })
                    .catch(error => {
                        console.warn('VD Fingerprint: Crypto API failed, using fallback:', error);
                        return this.simpleHash(dataString);
                    });
            } else {
                // Fallback to simple hash
                return Promise.resolve(this.simpleHash(dataString));
            }
        },

        /**
         * Simple hash function (djb2 algorithm)
         *
         * @param {string} str String to hash
         * @returns {string} Hash string
         */
        simpleHash: function(str) {
            let hash = 5381;
            for (let i = 0; i < str.length; i++) {
                hash = ((hash << 5) + hash) + str.charCodeAt(i);
                hash = hash & hash; // Convert to 32-bit integer
            }
            return Math.abs(hash).toString(16).padStart(8, '0');
        },

        /**
         * Generate device fingerprint
         *
         * @returns {Promise<string>} Fingerprint hash
         */
        generateFingerprint: function() {
            // Check cache first
            const cached = this.getStoredFingerprint();
            if (cached) {
                return Promise.resolve(cached);
            }

            // Collect and hash fingerprint
            const data = this.collectFingerprint();

            return this.hashFingerprint(data)
                .then(hash => {
                    // Cache the result
                    this.storeFingerprint(hash);
                    return hash;
                })
                .catch(error => {
                    console.error('VD Fingerprint: Generation failed:', error);
                    // Return a fallback fingerprint
                    const fallback = 'fallback_' + Date.now().toString(36);
                    this.storeFingerprint(fallback);
                    return fallback;
                });
        },

        /**
         * Get stored fingerprint from session storage
         *
         * @returns {string|null} Stored fingerprint or null
         */
        getStoredFingerprint: function() {
            try {
                return sessionStorage.getItem('vd_device_fp');
            } catch (error) {
                return null;
            }
        },

        /**
         * Store fingerprint in session storage
         *
         * @param {string} fingerprint Fingerprint hash
         */
        storeFingerprint: function(fingerprint) {
            try {
                sessionStorage.setItem('vd_device_fp', fingerprint);
            } catch (error) {
                console.warn('VD Fingerprint: Could not store fingerprint:', error);
            }
        },

        /**
         * Clear stored fingerprint
         */
        clearFingerprint: function() {
            try {
                sessionStorage.removeItem('vd_device_fp');
            } catch (error) {
                console.warn('VD Fingerprint: Could not clear fingerprint:', error);
            }
        },

        /**
         * Get device information for display
         *
         * @returns {Object} Device info
         */
        getDeviceInfo: function() {
            const data = this.collectFingerprint();

            // Parse user agent for OS and browser
            const ua = data.ua;
            let os = 'Unknown OS';
            let browser = 'Unknown Browser';

            // OS detection
            if (ua.includes('Windows NT')) {
                os = 'Windows';
                if (ua.includes('Windows NT 10.0')) os = 'Windows 10';
                else if (ua.includes('Windows NT 6.3')) os = 'Windows 8.1';
                else if (ua.includes('Windows NT 6.2')) os = 'Windows 8';
                else if (ua.includes('Windows NT 6.1')) os = 'Windows 7';
            } else if (ua.includes('Mac OS X')) {
                os = 'macOS';
            } else if (ua.includes('Linux')) {
                os = 'Linux';
            } else if (ua.includes('Android')) {
                os = 'Android';
            } else if (ua.includes('iPhone') || ua.includes('iPad')) {
                os = 'iOS';
            }

            // Browser detection
            if (ua.includes('Chrome') && !ua.includes('Chromium')) {
                browser = 'Chrome';
            } else if (ua.includes('Firefox')) {
                browser = 'Firefox';
            } else if (ua.includes('Safari') && !ua.includes('Chrome')) {
                browser = 'Safari';
            } else if (ua.includes('Edge')) {
                browser = 'Edge';
            } else if (ua.includes('Opera')) {
                browser = 'Opera';
            }

            return {
                os: os,
                browser: browser,
                screen: data.screen,
                language: data.language,
                platform: data.platform,
                user_agent: ua
            };
        }
    };

    // Export to global scope
    window.VDFingerprint = VDFingerprint;

    // Also create convenience functions
    window.generateFingerprint = function() {
        return VDFingerprint.generateFingerprint();
    };

    window.getDeviceInfo = function() {
        return VDFingerprint.getDeviceInfo();
    };

})(window);