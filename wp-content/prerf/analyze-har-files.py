#!/usr/bin/env python3
"""
HAR File Analysis Script
Analyzes HAR files for performance metrics
"""

import json
import os
from collections import defaultdict
from urllib.parse import urlparse

# HAR files to analyze
har_files = {
    'home': 'trang-chu.har',
    'products': 'trang-san-pham.har',
    'post': 'trang-bai-viet.har',
    'contact': 'trang-contact.har',
    'cart': 'trang-cart.har',
    'checkout': 'trang-checkout.har'
}

results = {}
base_dir = os.path.dirname(os.path.abspath(__file__))
inputs_dir = os.path.join(base_dir, 'inputs')

for route, filename in har_files.items():
    file_path = os.path.join(inputs_dir, filename)
    
    if not os.path.exists(file_path):
        print(f"HAR file not found: {file_path}")
        continue
    
    with open(file_path, 'r', encoding='utf-8') as f:
        try:
            har_data = json.load(f)
        except json.JSONDecodeError as e:
            print(f"Invalid JSON in {filename}: {e}")
            continue
    
    if 'log' not in har_data or 'entries' not in har_data['log']:
        print(f"Invalid HAR format for: {filename}")
        continue
    
    entries = har_data['log']['entries']
    total_requests = len(entries)
    
    # Initialize counters
    errors_404 = []
    recaptcha_count = 0
    css_count = 0
    js_count = 0
    url_counts = defaultdict(int)
    
    # Analyze each request
    for entry in entries:
        url = entry['request']['url']
        status = entry['response']['status']
        mime_type = entry['response']['content'].get('mimeType', '')
        
        # Count URLs for duplicate detection
        url_counts[url] += 1
        
        # Check for 404 errors
        if status == 404:
            parsed_url = urlparse(url)
            errors_404.append(parsed_url.path if parsed_url.path else url)
        
        # Check for reCAPTCHA and gstatic
        url_lower = url.lower()
        if 'recaptcha' in url_lower or 'gstatic.com' in url_lower:
            recaptcha_count += 1
        
        # Count CSS files
        if 'css' in mime_type.lower() or '.css' in url:
            css_count += 1
        
        # Count JS files
        mime_lower = mime_type.lower()
        if 'javascript' in mime_lower or 'ecmascript' in mime_lower or '.js' in url:
            js_count += 1
    
    # Find duplicates
    duplicates = {url: count for url, count in url_counts.items() if count > 1}
    
    results[route] = {
        'total_requests': total_requests,
        '404_errors': {
            'count': len(errors_404),
            'files': errors_404
        },
        'recaptcha_loads': recaptcha_count,
        'css_files': css_count,
        'js_files': js_count,
        'duplicate_requests': {
            'count': len(duplicates),
            'details': duplicates
        }
    }

# Display results
print("\n=== HAR FILE ANALYSIS RESULTS ===\n")

for route, metrics in results.items():
    print(f"--- {route} ---")
    print(f"Total Requests: {metrics['total_requests']}")
    print(f"404 Errors: {metrics['404_errors']['count']}", end='')
    if metrics['404_errors']['files']:
        files_preview = metrics['404_errors']['files'][:3]
        print(f" (Files: {', '.join(files_preview)}", end='')
        if len(metrics['404_errors']['files']) > 3:
            print(f", +{len(metrics['404_errors']['files']) - 3} more", end='')
        print(")", end='')
    print()
    print(f"reCAPTCHA Loads: {metrics['recaptcha_loads']}")
    print(f"CSS Files: {metrics['css_files']}")
    print(f"JS Files: {metrics['js_files']}")
    print(f"Duplicate Requests: {metrics['duplicate_requests']['count']}")
    print()

# Comparison Summary
print("=== COMPARISON WITH TARGETS ===\n")
print("Previous V2 Baseline:")
print("- 404 Errors: 2-4")
print("- reCAPTCHA: 14-27")
print("- Cart: 251 requests")
print("- Checkout: 242 requests\n")

print("Current Results:")
all_404s = sum(metrics['404_errors']['count'] for metrics in results.values())
all_recaptcha = max(metrics['recaptcha_loads'] for metrics in results.values())

print(f"- 404 Errors: {all_404s} (Target: 0) {'✓ MET' if all_404s == 0 else '✗ NOT MET'}")
print(f"- reCAPTCHA: {all_recaptcha} (Target: 1) {'✓ MET' if all_recaptcha <= 1 else '✗ NOT MET'}")

if 'cart' in results:
    cart_total = results['cart']['total_requests']
    print(f"- Cart: {cart_total} requests (Target: <150) {'✓ MET' if cart_total < 150 else '✗ NOT MET'}")

if 'checkout' in results:
    checkout_total = results['checkout']['total_requests']
    print(f"- Checkout: {checkout_total} requests (Target: <180) {'✓ MET' if checkout_total < 180 else '✗ NOT MET'}")

# Save results to JSON
output_data = {
    'analysis_date': str(__import__('datetime').datetime.now()),
    'results': results,
    'comparison': {
        'previous_v2': {
            '404_errors': '2-4',
            'recaptcha_loads': '14-27',
            'cart_requests': 251,
            'checkout_requests': 242
        },
        'targets': {
            '404_errors': 0,
            'recaptcha_loads': 1,
            'cart_requests': '<150',
            'checkout_requests': '<180'
        },
        'target_met': {
            '404_errors': all_404s == 0,
            'recaptcha_loads': all_recaptcha <= 1,
            'cart_requests': results['cart']['total_requests'] < 150 if 'cart' in results else None,
            'checkout_requests': results['checkout']['total_requests'] < 180 if 'checkout' in results else None
        }
    }
}

with open(os.path.join(base_dir, 'har-analysis-output.json'), 'w') as f:
    json.dump(output_data, f, indent=2)

print("\nDetailed results saved to: har-analysis-output.json")