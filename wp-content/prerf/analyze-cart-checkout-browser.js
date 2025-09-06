// Cart/Checkout Browser Analysis Tool
// Run this in browser console on Cart or Checkout page

(function() {
    console.log('===== VIDIEU CART/CHECKOUT ANALYSIS =====');
    console.log('Page: ' + window.location.pathname);
    console.log('Time: ' + new Date().toISOString());
    
    // Analyze all loaded scripts
    const scripts = Array.from(document.querySelectorAll('script[src]'));
    const scriptHandles = new Map();
    
    console.log('\n=== SCRIPTS ANALYSIS ===');
    console.log('Total script tags: ' + scripts.length);
    
    // Group by source
    const scriptSources = {
        wordpress: [],
        woocommerce: [],
        elessi: [],
        elementor: [],
        plugins: [],
        external: [],
        other: []
    };
    
    scripts.forEach(script => {
        const src = script.src;
        let category = 'other';
        
        if (src.includes('/wp-includes/')) {
            category = 'wordpress';
        } else if (src.includes('/woocommerce/')) {
            category = 'woocommerce';
        } else if (src.includes('/elessi')) {
            category = 'elessi';
        } else if (src.includes('/elementor')) {
            category = 'elementor';
        } else if (src.includes('/plugins/')) {
            category = 'plugins';
        } else if (!src.includes(window.location.hostname)) {
            category = 'external';
        }
        
        scriptSources[category].push(src);
    });
    
    // Print categorized scripts
    Object.entries(scriptSources).forEach(([category, srcs]) => {
        if (srcs.length > 0) {
            console.log('\n' + category.toUpperCase() + ' (' + srcs.length + '):');
            srcs.forEach((src, i) => {
                // Extract filename
                const filename = src.split('/').pop().split('?')[0];
                console.log((i+1) + '. ' + filename);
            });
        }
    });
    
    // Analyze styles
    const styles = Array.from(document.querySelectorAll('link[rel="stylesheet"]'));
    console.log('\n\n=== STYLES ANALYSIS ===');
    console.log('Total stylesheets: ' + styles.length);
    
    const styleSources = {
        wordpress: [],
        woocommerce: [],
        elessi: [],
        elementor: [],
        plugins: [],
        external: [],
        other: []
    };
    
    styles.forEach(link => {
        const href = link.href;
        let category = 'other';
        
        if (href.includes('/wp-includes/')) {
            category = 'wordpress';
        } else if (href.includes('/woocommerce/')) {
            category = 'woocommerce';
        } else if (href.includes('/elessi')) {
            category = 'elessi';
        } else if (href.includes('/elementor')) {
            category = 'elementor';
        } else if (href.includes('/plugins/')) {
            category = 'plugins';
        } else if (!href.includes(window.location.hostname)) {
            category = 'external';
        }
        
        styleSources[category].push(href);
    });
    
    // Print categorized styles
    Object.entries(styleSources).forEach(([category, hrefs]) => {
        if (hrefs.length > 0) {
            console.log('\n' + category.toUpperCase() + ' (' + hrefs.length + '):');
            hrefs.forEach((href, i) => {
                const filename = href.split('/').pop().split('?')[0];
                console.log((i+1) + '. ' + filename);
            });
        }
    });
    
    // Domain analysis
    console.log('\n\n=== DOMAIN ANALYSIS ===');
    const resources = performance.getEntriesByType('resource');
    const domains = {};
    
    resources.forEach(resource => {
        try {
            const url = new URL(resource.name);
            const domain = url.hostname;
            
            if (!domains[domain]) {
                domains[domain] = {
                    count: 0,
                    js: 0,
                    css: 0,
                    image: 0,
                    font: 0,
                    other: 0
                };
            }
            
            domains[domain].count++;
            
            // Categorize by type
            if (resource.name.match(/\.js(\?|$)/i)) {
                domains[domain].js++;
            } else if (resource.name.match(/\.css(\?|$)/i)) {
                domains[domain].css++;
            } else if (resource.name.match(/\.(jpg|jpeg|png|gif|webp|svg)(\?|$)/i)) {
                domains[domain].image++;
            } else if (resource.name.match(/\.(woff|woff2|ttf|eot)(\?|$)/i)) {
                domains[domain].font++;
            } else {
                domains[domain].other++;
            }
        } catch(e) {}
    });
    
    // Sort by request count
    const sortedDomains = Object.entries(domains)
        .sort((a, b) => b[1].count - a[1].count);
    
    console.log('Total resources: ' + resources.length);
    console.log('Unique domains: ' + sortedDomains.length);
    
    // Create table data
    const tableData = sortedDomains.map(([domain, stats]) => ({
        Domain: domain,
        Total: stats.count,
        JS: stats.js,
        CSS: stats.css,
        Images: stats.image,
        Fonts: stats.font,
        Other: stats.other
    }));
    
    console.table(tableData);
    
    // Generate whitelist recommendations
    console.log('\n\n=== RECOMMENDED WHITELIST ===');
    
    // Extract essential scripts
    const essentialScripts = scripts.filter(s => {
        const src = s.src;
        return src.includes('jquery') ||
               src.includes('woocommerce') ||
               src.includes('wc-') ||
               src.includes('selectWoo') ||
               src.includes('js-cookie') ||
               src.includes('wp-i18n') ||
               (src.includes('elessi') && src.includes('theme'));
    }).map(s => {
        const filename = s.src.split('/').pop().split('?')[0];
        // Try to extract handle from id or filename
        const id = s.id ? s.id.replace('-js', '') : filename.replace('.min.js', '').replace('.js', '');
        return id;
    });
    
    console.log('\nEssential Scripts for ' + (window.location.pathname.includes('cart') ? 'CART' : 'CHECKOUT') + ':');
    console.log(JSON.stringify(essentialScripts, null, 2));
    
    // Extract essential styles
    const essentialStyles = styles.filter(s => {
        const href = s.href;
        return href.includes('woocommerce') ||
               (href.includes('elessi') && href.includes('style'));
    }).map(s => {
        const id = s.id ? s.id.replace('-css', '') : '';
        return id;
    }).filter(id => id);
    
    console.log('\nEssential Styles:');
    console.log(JSON.stringify(essentialStyles, null, 2));
    
    // Find problematic domains
    console.log('\n\n=== DOMAINS TO BLOCK ===');
    const blockDomains = sortedDomains.filter(([domain, stats]) => {
        return domain.includes('elementor') ||
               domain.includes('instagram') ||
               domain.includes('facebook') ||
               domain.includes('google') ||
               domain.includes('yith') ||
               domain.includes('revslider') ||
               domain.includes('uael');
    });
    
    blockDomains.forEach(([domain, stats]) => {
        console.log('- ' + domain + ' (' + stats.count + ' requests)');
    });
    
    // Summary
    console.log('\n\n=== SUMMARY ===');
    console.log('Page: ' + window.location.pathname);
    console.log('Total requests: ' + resources.length);
    console.log('Scripts: ' + scripts.length);
    console.log('Styles: ' + styles.length);
    console.log('Essential scripts found: ' + essentialScripts.length);
    console.log('Essential styles found: ' + essentialStyles.length);
    
    // Copy helper
    console.log('\n\n=== COPY THIS FOR WHITELIST ===');
    const whitelistCode = `
// ${window.location.pathname.includes('cart') ? 'CART' : 'CHECKOUT'} WHITELIST
$allowed_scripts = array(
    // Core
    'jquery',
    'jquery-core', 
    'jquery-migrate',
    'js-cookie',
    
    // WooCommerce
    'woocommerce',
    'wc-add-to-cart',
    'wc-cart-fragments',
    'selectWoo',
    'wc-country-select',
    'wc-address-i18n',
    ${window.location.pathname.includes('checkout') ? "'wc-checkout',\n    'wc-password-strength-meter'," : ''}
    
    // i18n
    'wp-i18n',
    
    // Theme
    'elessi-theme-js'
);

$allowed_styles = array(
    'woocommerce-general',
    'woocommerce-layout',
    'woocommerce-smallscreen',
    'elessi-style',
    'elessi-style-child'
);`;
    
    console.log(whitelistCode);
    
    console.log('\n===== END ANALYSIS =====');
    
    // Store results
    window.vidieuAnalysisResults = {
        page: window.location.pathname,
        totalRequests: resources.length,
        scripts: scripts.length,
        styles: styles.length,
        domains: sortedDomains,
        essentialScripts,
        essentialStyles
    };
    
    console.log('\nResults stored in: window.vidieuAnalysisResults');
})();