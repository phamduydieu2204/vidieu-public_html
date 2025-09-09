/**
 * Debug script to check all click handlers on Buy Now buttons
 * Run this in browser console to see what's blocking
 */

(function() {
    console.log('=== DEBUGGING BUY NOW BUTTONS ===');
    
    // Find all buy now buttons
    var buttons = document.querySelectorAll('.vd-buy-now-button.vd-buy-now-simple');
    console.log('Found buttons:', buttons.length);
    
    buttons.forEach(function(btn, index) {
        console.log(`\nButton ${index + 1}:`);
        console.log('- HTML:', btn.outerHTML);
        console.log('- Classes:', btn.className);
        console.log('- Product ID:', btn.getAttribute('data-product-id'));
        console.log('- Product Type:', btn.getAttribute('data-product-type'));
        
        // Check jQuery data and events
        if (typeof jQuery !== 'undefined') {
            var $btn = jQuery(btn);
            console.log('- jQuery data:', $btn.data());
            
            // Get all events bound to document
            var docEvents = jQuery._data(document, 'events');
            console.log('\n--- Document delegated events ---');
            if (docEvents && docEvents.click) {
                docEvents.click.forEach(function(event, i) {
                    if (event.selector && event.selector.includes('vd-buy-now')) {
                        console.log(`Event ${i}:`, {
                            selector: event.selector,
                            namespace: event.namespace,
                            handler: event.handler.toString().substring(0, 200) + '...'
                        });
                    }
                });
            }
            
            // Check direct events on button
            var btnEvents = jQuery._data(btn, 'events');
            if (btnEvents && btnEvents.click) {
                console.log('\n--- Direct button events ---');
                btnEvents.click.forEach(function(event, i) {
                    console.log(`Event ${i}:`, {
                        namespace: event.namespace,
                        handler: event.handler.toString().substring(0, 200) + '...'
                    });
                });
            }
        }
    });
    
    // Test click handler
    console.log('\n=== TESTING CLICK ===');
    console.log('To test a button, run: jQuery(".vd-buy-now-button.vd-buy-now-simple").first().trigger("click")');
    
    // Check if other scripts might interfere
    console.log('\n=== CHECKING FOR CONFLICTS ===');
    
    // Check for stopPropagation or preventDefault in parent elements
    if (buttons.length > 0) {
        var parent = buttons[0].parentElement;
        var depth = 0;
        while (parent && depth < 10) {
            if (parent.onclick) {
                console.log(`Parent at depth ${depth} has onclick:`, parent.onclick.toString().substring(0, 100) + '...');
            }
            parent = parent.parentElement;
            depth++;
        }
    }
    
    // Check global handlers
    console.log('\n=== GLOBAL HANDLERS ===');
    if (window.onclick) console.log('window.onclick:', window.onclick.toString().substring(0, 100) + '...');
    if (document.onclick) console.log('document.onclick:', document.onclick.toString().substring(0, 100) + '...');
    if (document.body.onclick) console.log('body.onclick:', document.body.onclick.toString().substring(0, 100) + '...');
    
})();