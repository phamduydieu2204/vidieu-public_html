// Test Buy Now Simple handler registration
console.log('=== TESTING BUY NOW SIMPLE HANDLER ===');

// Check if VDBuyNowSimple exists
console.log('VDBuyNowSimple exists:', typeof window.VDBuyNowSimple !== 'undefined');

// Check jQuery
console.log('jQuery version:', jQuery.fn.jquery);
console.log('$ === jQuery:', $ === jQuery);

// Get all click events on document
var events = jQuery._data(document, 'events');
console.log('\nAll click events on document:');
if (events && events.click) {
    events.click.forEach(function(e, i) {
        console.log(`Event ${i}:`, {
            selector: e.selector,
            namespace: e.namespace,
            handler: e.handler.toString().substring(0, 100) + '...'
        });
    });
}

// Look specifically for .vdBuyNow namespace
console.log('\nLooking for .vdBuyNow namespace:');
var foundVdBuyNow = false;
if (events && events.click) {
    events.click.forEach(function(e) {
        if (e.namespace && e.namespace.includes('vdBuyNow')) {
            foundVdBuyNow = true;
            console.log('Found vdBuyNow event:', {
                selector: e.selector,
                namespace: e.namespace
            });
        }
    });
}

if (!foundVdBuyNow) {
    console.log('WARNING: No .vdBuyNow namespace found!');
    console.log('This means buynow-simple.js handler is NOT registered.');
    
    // Try to manually re-initialize
    console.log('\nAttempting manual initialization...');
    if (window.VDBuyNowSimple) {
        window.VDBuyNowSimple.init();
        console.log('Manual init complete. Check events again.');
    }
}

// Test manual click
console.log('\nTo test click, run:');
console.log('jQuery(".vd-buy-now-button.vd-buy-now-simple").first().click()');