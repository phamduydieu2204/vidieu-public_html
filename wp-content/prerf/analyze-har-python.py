#!/usr/bin/env python3
"""
Comprehensive HAR Analysis for Vidieu.vn
Analyzes all 7 routes: home, product, post, contact, cart, checkout, order-received
"""

import json
import os
from collections import defaultdict
from urllib.parse import urlparse

# Route configurations
routes = {
    'Home': 'trang-chu.har',
    'Product': 'trang-san-pham.har',
    'Post': 'trang-bai-viet.har',
    'Contact': 'trang-contact.har',
    'Cart': 'trang-cart.har',
    'Checkout': 'trang-checkout.har',
    'Order-received': 'trang-order-received.har'
}

# Blocked domains to check
blocked_domains = [
    'elementor', 'uael', 'revslider', 'instagram',
    'yith', 'facebook', 'twitter', 'analytics',
    'googletagmanager', 'hotjar', 'mixpanel', 'pinterest',
    'tiktok', 'snapchat', 'linkedin'
]

def analyze_har(har_path):
    """Analyze a single HAR file"""
    with open(har_path, 'r', encoding='utf-8') as f:
        har_data = json.load(f)
    
    entries = har_data['log']['entries']
    
    stats = {
        'total_requests': len(entries),
        'total_size': 0,
        'total_time': 0,
        'errors_404': 0,
        'errors_other': 0,
        'ajax_requests': 0,
        'css_files': 0,
        'js_files': 0,
        'image_files': 0,
        'font_files': 0,
        'recaptcha_loads': 0,
        'domains': defaultdict(int),
        'scripts_detail': [],
        'styles_detail': [],
        '404_urls': [],
        'recaptcha_urls': [],
        'blocked_domains_found': set()
    }
    
    for entry in entries:
        request = entry['request']
        response = entry['response']
        url = request['url']
        status = response['status']
        size = response.get('content', {}).get('size', 0)
        time = entry.get('time', 0)
        
        stats['total_size'] += size
        stats['total_time'] += time
        
        # Check status
        if status == 404:
            stats['errors_404'] += 1
            path = urlparse(url).path
            stats['404_urls'].append(path)
        elif status >= 400:
            stats['errors_other'] += 1
        
        # Check AJAX
        headers = {h['name']: h['value'] for h in request.get('headers', [])}
        if headers.get('X-Requested-With') == 'XMLHttpRequest':
            stats['ajax_requests'] += 1
        
        # Categorize by type
        path = urlparse(url).path
        query = urlparse(url).query
        
        if path.endswith('.css') or '.css?' in url:
            stats['css_files'] += 1
            stats['styles_detail'].append(os.path.basename(path))
        elif path.endswith('.js') or '.js?' in url:
            stats['js_files'] += 1
            stats['scripts_detail'].append(os.path.basename(path))
            
            # Check reCAPTCHA
            if 'recaptcha' in url or 'grecaptcha' in url:
                stats['recaptcha_loads'] += 1
                stats['recaptcha_urls'].append(url)
        elif any(path.endswith(ext) for ext in ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg']):
            stats['image_files'] += 1
        elif any(path.endswith(ext) for ext in ['.woff', '.woff2', '.ttf', '.eot']):
            stats['font_files'] += 1
        
        # Track domains
        host = urlparse(url).hostname
        if host:
            stats['domains'][host] += 1
            
            # Check blocked domains
            for blocked in blocked_domains:
                if blocked in host or blocked in url:
                    stats['blocked_domains_found'].add(blocked)
    
    # Convert to list and sort
    stats['domains'] = dict(sorted(stats['domains'].items(), key=lambda x: x[1], reverse=True))
    stats['blocked_domains_found'] = list(stats['blocked_domains_found'])
    stats['404_urls'] = list(set(stats['404_urls']))  # Unique URLs
    stats['scripts_detail'] = list(set(stats['scripts_detail']))
    stats['styles_detail'] = list(set(stats['styles_detail']))
    
    # Calculate KB and ms
    stats['total_size_kb'] = round(stats['total_size'] / 1024, 2)
    stats['total_time_ms'] = round(stats['total_time'], 2)
    
    return stats

def main():
    results = {}
    
    print("=== VIDIEU.VN HAR ANALYSIS ===\n")
    print("Analyzing 7 routes...\n")
    
    # Analyze each route
    for route, har_file in routes.items():
        har_path = os.path.join('inputs', har_file)
        if os.path.exists(har_path):
            print(f"Analyzing {route}...")
            results[route] = analyze_har(har_path)
        else:
            print(f"Warning: HAR file not found for {route}")
    
    # Print summary table
    print("\n=== ROUTE × TOTALS ANALYSIS ===\n")
    print(f"{'Route':<15} | {'Requests':>8} | {'Size (KB)':>10} | {'Time (ms)':>9} | {'404s':>5} | {'AJAX':>5} | {'CSS':>5} | {'JS':>5} | {'reCAPTCHA':>10}")
    print("-" * 100)
    
    for route, stats in results.items():
        print(f"{route:<15} | {stats['total_requests']:>8} | {stats['total_size_kb']:>10.2f} | {stats['total_time_ms']:>9.2f} | {stats['errors_404']:>5} | {stats['ajax_requests']:>5} | {stats['css_files']:>5} | {stats['js_files']:>5} | {stats['recaptcha_loads']:>10}")
    
    # Focus routes detail
    print("\n\n=== FOCUS ROUTES DETAIL (Cart, Checkout, Order-received) ===")
    
    for route in ['Cart', 'Checkout', 'Order-received']:
        if route not in results:
            continue
            
        stats = results[route]
        target = 150 if route == 'Cart' else (180 if route == 'Checkout' else 160)
        status = 'PASS' if stats['total_requests'] < target else 'FAIL'
        
        print(f"\n--- {route} Page ---")
        print(f"Total Requests: {stats['total_requests']}")
        print(f"Target: <{target}")
        print(f"Status: {status}")
        
        if stats['404_urls']:
            print("\n404 Errors:")
            for url in stats['404_urls']:
                print(f"  - {url}")
        
        if stats['blocked_domains_found']:
            print("\nBlocked Domains Still Loading:")
            for domain in stats['blocked_domains_found']:
                print(f"  - {domain}")
        
        print("\nTop 10 Domains by Request Count:")
        for i, (domain, count) in enumerate(list(stats['domains'].items())[:10]):
            print(f"  {domain:<40} : {count} requests")
        
        # Script analysis
        print(f"\nScripts Found ({stats['js_files']} total):")
        script_categories = defaultdict(list)
        
        for script in stats['scripts_detail']:
            handle = script.replace('.min.js', '').replace('.js', '')
            
            if 'jquery' in script:
                script_categories['jquery'].append(handle)
            elif 'wc-' in script or 'woocommerce' in script:
                script_categories['woocommerce'].append(handle)
            elif 'select' in script:
                script_categories['woocommerce'].append(handle)
            elif 'elessi' in script:
                script_categories['theme'].append(handle)
            elif 'wp-' in script:
                script_categories['wordpress'].append(handle)
            elif 'elementor' in script:
                script_categories['elementor'].append(handle)
            else:
                script_categories['other'].append(handle)
        
        for category, handles in script_categories.items():
            if handles:
                print(f"  [{category}]: {', '.join(sorted(set(handles)))}")
    
    # Save results
    output = {
        'analysis_date': '2025-09-06',
        'routes': results,
        'targets': {
            'Cart': 150,
            'Checkout': 180,
            'Order-received': 160
        }
    }
    
    os.makedirs('outputs', exist_ok=True)
    with open('outputs/har-analysis-comprehensive.json', 'w') as f:
        json.dump(output, f, indent=2)
    
    print("\n\n=== WHITELIST RECOMMENDATIONS ===")
    print("Based on actual scripts found in Cart/Checkout/Order-received:")
    print("\nEssential Scripts:")
    print("- jQuery core (jquery, jquery-core, jquery-migrate, jquery-blockui)")
    print("- WooCommerce core (woocommerce, wc-cart-fragments, wc-add-to-cart)")
    print("- WooCommerce cart (wc-cart, wc-country-select, wc-address-i18n)")
    print("- WooCommerce checkout (wc-checkout, wc-password-strength-meter)")
    print("- SelectWoo (selectWoo, select2)")
    print("- WordPress (wp-i18n, wp-hooks, wp-util, js-cookie, underscore)")
    print("- Theme (elessi-theme-js, elessi-functions-js)")
    
    print("\n=== ANALYSIS COMPLETE ===")
    print("Results saved to: outputs/har-analysis-comprehensive.json")

if __name__ == "__main__":
    main()