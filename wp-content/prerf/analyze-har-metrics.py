#!/usr/bin/env python3
"""
Analyze HAR files for performance metrics
"""

import json
import glob
import os
from pathlib import Path

def analyze_har_file(file_path):
    """Analyze a single HAR file for metrics"""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            har_data = json.load(f)
    except Exception as e:
        print(f"Error reading {file_path}: {e}")
        return None
    
    if 'log' not in har_data or 'entries' not in har_data['log']:
        return None
    
    metrics = {
        'total_requests': 0,
        '404_errors': 0,
        'css_files': 0,
        'js_files': 0,
        'recaptcha_loads': 0,
        'blocked_domains': {},
        'file_path': os.path.basename(file_path),
        '404_urls': []
    }
    
    blocked_domains = ['elementor', 'yith', 'revslider', 'revolution', 'slider']
    
    for entry in har_data['log']['entries']:
        metrics['total_requests'] += 1
        
        url = entry.get('request', {}).get('url', '')
        status = entry.get('response', {}).get('status', 0)
        mime_type = entry.get('response', {}).get('content', {}).get('mimeType', '')
        
        # Check 404 errors
        if status == 404:
            metrics['404_errors'] += 1
            metrics['404_urls'].append(url)
        
        # Count CSS files
        if 'css' in mime_type or '.css' in url:
            metrics['css_files'] += 1
        
        # Count JS files
        if 'javascript' in mime_type or '.js' in url:
            metrics['js_files'] += 1
        
        # Check for reCAPTCHA
        if 'recaptcha' in url.lower() or 'grecaptcha' in url.lower():
            metrics['recaptcha_loads'] += 1
        
        # Check for blocked domains that are still loading
        for domain in blocked_domains:
            if domain in url.lower():
                metrics['blocked_domains'][domain] = metrics['blocked_domains'].get(domain, 0) + 1
    
    return metrics

def main():
    har_dir = '/mnt/f/Obsidian/Dữ Liệu Của Tôi/Quản Lý Tài Chính/Quản Lý Kinh Doanh bán Tool/Vidieu.vn/public_html/wp-content/prerf/inputs/'
    har_files = glob.glob(os.path.join(har_dir, '*.har'))
    
    results = {}
    focus_pages = ['trang-cart.har', 'trang-checkout.har', 'trang-order-received.har']
    
    # Analyze each HAR file
    for har_file in har_files:
        basename = os.path.basename(har_file)
        metrics = analyze_har_file(har_file)
        if metrics:
            results[basename] = metrics
    
    # Output results
    print("=== HAR Files Analysis Results ===\n")
    
    # Summary table for Cart, Checkout, and Order-received pages
    print("### Focus: Cart, Checkout, and Order-received Pages\n")
    print("| Page | Total Requests | 404 Errors | CSS Files | JS Files | reCAPTCHA | Blocked Domains Still Loading |")
    print("|------|----------------|------------|-----------|----------|-----------|------------------------------|")
    
    for page in focus_pages:
        if page in results:
            m = results[page]
            blocked_domains_str = ''
            if m['blocked_domains']:
                blocked_items = [f"{domain}({count})" for domain, count in m['blocked_domains'].items()]
                blocked_domains_str = ', '.join(blocked_items)
            
            page_name = page.replace('.har', '')
            print(f"| {page_name:<20} | {m['total_requests']:>14} | {m['404_errors']:>10} | {m['css_files']:>9} | {m['js_files']:>8} | {m['recaptcha_loads']:>9} | {blocked_domains_str or 'None':<28} |")
    
    print("\n### All Pages Summary\n")
    print("| Page | Total Requests | 404 Errors | CSS Files | JS Files | reCAPTCHA | Blocked Domains Still Loading |")
    print("|------|----------------|------------|-----------|----------|-----------|------------------------------|")
    
    for page, m in results.items():
        blocked_domains_str = ''
        if m['blocked_domains']:
            blocked_items = [f"{domain}({count})" for domain, count in m['blocked_domains'].items()]
            blocked_domains_str = ', '.join(blocked_items)
        
        page_name = page.replace('.har', '')
        print(f"| {page_name:<20} | {m['total_requests']:>14} | {m['404_errors']:>10} | {m['css_files']:>9} | {m['js_files']:>8} | {m['recaptcha_loads']:>9} | {blocked_domains_str or 'None':<28} |")
    
    # Detailed 404 errors for focus pages
    print("\n### 404 Errors Details for Focus Pages\n")
    for page in focus_pages:
        if page in results and results[page]['404_urls']:
            print(f"**{page.replace('.har', '')}:**")
            for url in results[page]['404_urls']:
                print(f"- {url}")
            print()

if __name__ == "__main__":
    main()