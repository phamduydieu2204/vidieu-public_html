/**
 * Hide Empty Top Bar
 * Checks if #top-bar contains login/register content
 * If not, hides the entire top bar across all devices
 */

(function() {
    'use strict';
    
    /**
     * Check if element contains login/register content
     */
    function hasLoginRegisterContent(element) {
        if (!element) return false;
        
        var text = element.textContent || element.innerText || '';
        text = text.toLowerCase();
        
        // Check for login/register keywords
        var keywords = [
            'login', 'log in', 'signin', 'sign in',
            'register', 'signup', 'sign up', 'create account',
            'my account', 'account', 'logout', 'log out',
            'đăng nhập', 'đăng ký', 'tài khoản' // Vietnamese
        ];
        
        // Check text content
        for (var i = 0; i < keywords.length; i++) {
            if (text.includes(keywords[i])) {
                return true;
            }
        }
        
        // Check for login/register links
        var links = element.querySelectorAll('a');
        for (var j = 0; j < links.length; j++) {
            var href = links[j].href || '';
            var linkText = links[j].textContent || links[j].innerText || '';
            
            // Check href
            if (href.includes('login') || href.includes('register') || 
                href.includes('account') || href.includes('signin') ||
                href.includes('wp-login')) {
                return true;
            }
            
            // Check link text
            for (var k = 0; k < keywords.length; k++) {
                if (linkText.toLowerCase().includes(keywords[k])) {
                    return true;
                }
            }
        }
        
        // Check for login forms
        if (element.querySelector('form[name*="login"], form[id*="login"], form.login-form')) {
            return true;
        }
        
        // Check for specific login/register classes
        var loginClasses = [
            '.login', '.register', '.account', '.signin',
            '.nasa-login', '.topbar-account', '.account-element'
        ];
        
        for (var l = 0; l < loginClasses.length; l++) {
            if (element.querySelector(loginClasses[l])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Process top bar visibility
     */
    function processTopBar() {
        var topBar = document.getElementById('top-bar');
        if (!topBar) {
            topBar = document.querySelector('.top-bar');
        }
        
        if (topBar) {
            // Check if has login/register content
            if (!hasLoginRegisterContent(topBar)) {
                // Hide the top bar
                topBar.style.display = 'none';
                topBar.style.visibility = 'hidden';
                topBar.style.height = '0';
                topBar.style.overflow = 'hidden';
                topBar.setAttribute('aria-hidden', 'true');
                
                // Add class for CSS targeting
                topBar.classList.add('hidden-empty-topbar');
                
                // Adjust body padding if needed
                var body = document.body;
                var computedStyle = window.getComputedStyle(body);
                var currentPaddingTop = parseInt(computedStyle.paddingTop) || 0;
                
                // If body has padding-top that matches topbar height, remove it
                if (currentPaddingTop > 0 && currentPaddingTop <= 50) {
                    body.style.paddingTop = '0';
                }
                
                // Also check for margin-top on common wrapper elements
                var wrappers = ['.wrapper', '#wrapper', '.site-wrapper', 'header'];
                wrappers.forEach(function(selector) {
                    var element = document.querySelector(selector);
                    if (element) {
                        var style = window.getComputedStyle(element);
                        var marginTop = parseInt(style.marginTop) || 0;
                        if (marginTop > 0 && marginTop <= 50) {
                            element.style.marginTop = '0';
                        }
                    }
                });
                
            } else {
            }
        }
    }
    
    /**
     * Initialize on various events
     */
    function init() {
        // Process immediately
        processTopBar();
        
        // Process after delay for dynamic content
        setTimeout(processTopBar, 500);
        setTimeout(processTopBar, 1000);
        
        // Watch for changes
        var observer = new MutationObserver(function(mutations) {
            var shouldRecheck = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    // Check if top bar was added or modified
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            if (node.id === 'top-bar' || node.classList && node.classList.contains('top-bar')) {
                                shouldRecheck = true;
                            }
                        }
                    });
                }
            });
            
            if (shouldRecheck) {
                processTopBar();
            }
        });
        
        // Start observing
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Also run on window load
    window.addEventListener('load', processTopBar);
    
    // Re-check on resize (responsive changes)
    var resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(processTopBar, 250);
    });
    
})();