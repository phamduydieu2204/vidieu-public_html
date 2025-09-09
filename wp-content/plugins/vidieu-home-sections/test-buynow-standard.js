/**
 * Buy Now Simple Standard Test Script
 * Run this in browser console on a page with Simple Products
 * 
 * Usage: Copy entire script and paste in console
 */

(function() {
    'use strict';
    
    console.clear();
    console.log('%c🔍 Buy Now Simple Standard Test Starting...', 'color: blue; font-size: 16px; font-weight: bold');
    
    // Test results storage
    var testResults = {
        requests: 0,
        handlers: [],
        loadingState: {},
        fragmentListeners: 0,
        setTimeoutCalls: 0,
        clickTime: null
    };
    
    // Console output storage
    var consoleOutput = [];
    var originalLog = console.log;
    var originalWarn = console.warn;
    var originalError = console.error;
    
    // Override console methods to capture output
    console.log = function() {
        var args = Array.prototype.slice.call(arguments);
        var message = args.map(arg => typeof arg === 'object' ? JSON.stringify(arg, null, 2) : arg).join(' ');
        consoleOutput.push('[LOG] ' + message);
        originalLog.apply(console, arguments);
    };
    
    console.warn = function() {
        var args = Array.prototype.slice.call(arguments);
        var message = args.map(arg => typeof arg === 'object' ? JSON.stringify(arg, null, 2) : arg).join(' ');
        consoleOutput.push('[WARN] ' + message);
        originalWarn.apply(console, arguments);
    };
    
    console.error = function() {
        var args = Array.prototype.slice.call(arguments);
        var message = args.map(arg => typeof arg === 'object' ? JSON.stringify(arg, null, 2) : arg).join(' ');
        consoleOutput.push('[ERROR] ' + message);
        originalError.apply(console, arguments);
    };
    
    // 1. Hook into XMLHttpRequest to count requests
    var originalOpen = XMLHttpRequest.prototype.open;
    var originalSetTimeout = window.setTimeout;
    var requestUrls = [];
    
    XMLHttpRequest.prototype.open = function(method, url) {
        if (testResults.clickTime && url.includes('admin-ajax.php')) {
            testResults.requests++;
            requestUrls.push({
                method: method,
                url: url,
                time: Date.now() - testResults.clickTime
            });
            console.log(`📡 BuyNow Test → Request ${testResults.requests}: ${method} ${url}`);
        }
        return originalOpen.apply(this, arguments);
    };
    
    // Hook setTimeout to count delays
    window.setTimeout = function(callback, delay) {
        if (testResults.clickTime && delay > 0) {
            testResults.setTimeoutCalls++;
            console.log(`⏱️ BuyNow Test → setTimeout called with delay: ${delay}ms`);
        }
        return originalSetTimeout.apply(this, arguments);
    };
    
    // 2. Check event handlers
    function checkEventHandlers() {
        console.log('\n📋 Checking Event Handlers...');
        
        if (typeof jQuery !== 'undefined') {
            var events = jQuery._data(document, 'events');
            if (events && events.click) {
                events.click.forEach(function(event, index) {
                    if (event.selector && event.selector.includes('.vd-buy-now-button')) {
                        var handlerInfo = {
                            selector: event.selector,
                            namespace: event.namespace || 'none',
                            index: index
                        };
                        testResults.handlers.push(handlerInfo);
                        console.log(`✓ Handler ${index}: selector="${event.selector}" namespace="${event.namespace}"`);
                    }
                });
            }
        } else {
            console.warn('jQuery not found, using native getEventListeners');
            try {
                var listeners = getEventListeners(document);
                if (listeners.click) {
                    testResults.handlers = listeners.click.length;
                }
            } catch(e) {
                console.error('getEventListeners not available');
            }
        }
        
        console.log(`📊 BuyNow Test → Handlers count: ${testResults.handlers.length} [${testResults.handlers.map(h => h.namespace).join(', ')}]`);
    }
    
    // 3. Check loading state & ARIA after click
    function checkLoadingState($button) {
        console.log('\n🔄 Checking Loading State...');
        
        // Use setTimeout to check state after processing
        setTimeout(function() {
            testResults.loadingState = {
                disabled: $button.prop('disabled'),
                ariaBusy: $button.attr('aria-busy'),
                dataProcessing: $button.attr('data-processing'),
                classes: $button.attr('class').split(' ').filter(c => 
                    c.includes('loading') || c.includes('processing') || c.includes('busy')
                ),
                text: $button.text().trim()
            };
            
            console.log('📌 BuyNow Test → State after click:');
            console.log(`   - disabled: ${testResults.loadingState.disabled}`);
            console.log(`   - aria-busy: ${testResults.loadingState.ariaBusy}`);
            console.log(`   - data-processing: ${testResults.loadingState.dataProcessing}`);
            console.log(`   - loading classes: [${testResults.loadingState.classes.join(', ')}]`);
            console.log(`   - button text: "${testResults.loadingState.text}"`);
        }, 50);
    }
    
    // 4. Check fragment listeners
    function checkFragmentListeners() {
        console.log('\n🔍 Checking Fragment Listeners...');
        
        if (typeof jQuery !== 'undefined') {
            var events = jQuery._data(document, 'events');
            if (events && events.wc_fragments_refreshed) {
                testResults.fragmentListeners = events.wc_fragments_refreshed.length;
                console.log(`✓ Found ${testResults.fragmentListeners} wc_fragments_refreshed listeners`);
                
                // Check if any are related to buy now
                events.wc_fragments_refreshed.forEach(function(event, i) {
                    var handlerStr = event.handler.toString();
                    if (handlerStr.includes('buy-now') || handlerStr.includes('buyNow')) {
                        console.log(`   - Listener ${i} appears to be Buy Now related`);
                    }
                });
            } else {
                console.log('✗ No wc_fragments_refreshed listeners found');
            }
        }
    }
    
    // 5. Find and test a button
    function runTest() {
        console.log('\n🎯 Finding Buy Now Simple button...');
        
        var $button = jQuery('.vd-buy-now-button.vd-buy-now-simple').first();
        
        if (!$button.length) {
            console.error('❌ No Buy Now Simple button found!');
            return;
        }
        
        console.log(`✓ Found button for product ID: ${$button.data('product-id')}`);
        console.log(`✓ Button classes: ${$button.attr('class')}`);
        
        // Initial checks
        checkEventHandlers();
        checkFragmentListeners();
        
        // Simulate click
        console.log('\n🖱️ Simulating button click...');
        testResults.clickTime = Date.now();
        
        // Monitor button state changes
        var stateCheckInterval = setInterval(function() {
            var currentState = {
                disabled: $button.prop('disabled'),
                ariaBusy: $button.attr('aria-busy'),
                processing: $button.attr('data-processing'),
                text: $button.text().trim()
            };
            console.log(`⚡ State: disabled=${currentState.disabled}, aria-busy=${currentState.ariaBusy}, processing=${currentState.processing}, text="${currentState.text}"`);
        }, 100);
        
        // Trigger click
        $button.trigger('click');
        
        // Check loading state
        checkLoadingState($button);
        
        // Stop monitoring and show results after 3 seconds
        setTimeout(function() {
            clearInterval(stateCheckInterval);
            showResults();
        }, 3000);
    }
    
    // 6. Show final results
    function showResults() {
        console.log('\n' + '='.repeat(50));
        console.log('===== BuyNow Simple Check Result =====');
        console.log('='.repeat(50));
        console.log(`Requests: ${testResults.requests}`);
        console.log(`Handlers: ${testResults.handlers.length} (${testResults.handlers.map(h => h.namespace).join(', ')})`);
        console.log(`Loading: ${JSON.stringify(testResults.loadingState, null, 2)}`);
        console.log(`Fragment listeners: ${testResults.fragmentListeners}`);
        console.log(`setTimeout calls: ${testResults.setTimeoutCalls}`);
        console.log('='.repeat(50));
        
        // Additional details
        if (requestUrls.length > 0) {
            console.log('\n📡 Request Details:');
            requestUrls.forEach((req, i) => {
                console.log(`   ${i + 1}. ${req.method} to ${req.url} at ${req.time}ms`);
            });
        }
        
        // Restore original functions
        XMLHttpRequest.prototype.open = originalOpen;
        window.setTimeout = originalSetTimeout;
        
        console.log('\n✅ Test completed. Original functions restored.');
        
        // Export console output to file
        exportConsoleToFile();
        
        // Also save to window for manual access
        window.BuyNowTestResults = {
            raw: testResults,
            log: consoleOutput.join('\n'),
            getResults: function() {
                return this.log;
            },
            download: function() {
                exportConsoleToFile();
            }
        };
        
        console.log('\n💡 TIP: You can access results with: window.BuyNowTestResults.getResults()');
        console.log('💡 Or download again with: window.BuyNowTestResults.download()');
    }
    
    // Export console output to text file
    function exportConsoleToFile() {
        // Restore original console methods
        console.log = originalLog;
        console.warn = originalWarn;
        console.error = originalError;
        
        try {
            var content = consoleOutput.join('\n');
            var blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
            var url = URL.createObjectURL(blob);
            
            // Create download link
            var link = document.createElement('a');
            link.href = url;
            link.download = 'buynow-test-console.txt';
            link.style.cssText = 'position:fixed;bottom:20px;right:20px;background:#0073aa;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;z-index:9999';
            link.textContent = '📥 Download Test Results';
            
            // Add to page for manual click if auto-download fails
            document.body.appendChild(link);
            
            // Try auto-download
            try {
                link.click();
                originalLog('📥 Auto-downloading console output...');
            } catch(e) {
                originalLog('⚠️ Auto-download failed. Please click the blue button at bottom-right to download results.');
            }
            
            // Keep button visible for 10 seconds
            setTimeout(function() {
                if (link.parentNode) {
                    document.body.removeChild(link);
                }
                URL.revokeObjectURL(url);
            }, 10000);
            
        } catch(error) {
            originalLog('❌ Error exporting console:', error);
            originalLog('📋 Manual copy - Select all text above and copy.');
        }
    }
    
    // Check jQuery availability
    if (typeof jQuery === 'undefined') {
        console.error('❌ jQuery not found! This test requires jQuery.');
        return;
    }
    
    // Run the test
    jQuery(document).ready(function() {
        runTest();
    });
    
})();