#!/usr/bin/env python3
"""
Detailed HAR analysis for specific insights
"""

import json
import os
from collections import defaultdict

def analyze_recaptcha_details(har_file):
    """Get detailed reCAPTCHA information"""
    try:
        with open(har_file, 'r', encoding='utf-8') as f:
            har_data = json.load(f)
    except:
        return []
    
    recaptcha_urls = []
    for entry in har_data['log']['entries']:
        url = entry.get('request', {}).get('url', '')
        if 'recaptcha' in url.lower() or 'grecaptcha' in url.lower():
            recaptcha_urls.append(url)
    return recaptcha_urls

def analyze_blocked_domains_details(har_file):
    """Get detailed blocked domains information"""
    try:
        with open(har_file, 'r', encoding='utf-8') as f:
            har_data = json.load(f)
    except:
        return {}
    
    blocked_patterns = {
        'elementor': [],
        'yith': [],
        'revslider': [],
        'revolution': [],
        'slider': []
    }
    
    for entry in har_data['log']['entries']:
        url = entry.get('request', {}).get('url', '')
        for pattern in blocked_patterns.keys():
            if pattern in url.lower():
                blocked_patterns[pattern].append(url)
    
    return blocked_patterns

def main():
    har_dir = '/mnt/f/Obsidian/Dữ Liệu Của Tôi/Quản Lý Tài Chính/Quản Lý Kinh Doanh bán Tool/Vidieu.vn/public_html/wp-content/prerf/inputs/'
    focus_pages = ['trang-cart.har', 'trang-checkout.har', 'trang-order-received.har']
    
    print("=== Detailed Analysis for Cart, Checkout, and Order-received Pages ===\n")
    
    for page in focus_pages:
        har_file = os.path.join(har_dir, page)
        if os.path.exists(har_file):
            print(f"\n## {page.replace('.har', '').upper()}\n")
            
            # reCAPTCHA details
            recaptcha_urls = analyze_recaptcha_details(har_file)
            print(f"### reCAPTCHA Loads ({len(recaptcha_urls)} total):")
            if recaptcha_urls:
                # Group by domain
                recaptcha_domains = defaultdict(int)
                for url in recaptcha_urls:
                    if 'google.com/recaptcha' in url:
                        recaptcha_domains['google.com/recaptcha'] += 1
                    elif 'gstatic.com/recaptcha' in url:
                        recaptcha_domains['gstatic.com/recaptcha'] += 1
                    else:
                        recaptcha_domains['other'] += 1
                
                for domain, count in recaptcha_domains.items():
                    print(f"- {domain}: {count} requests")
            else:
                print("- None found")
            
            # Blocked domains details
            blocked_details = analyze_blocked_domains_details(har_file)
            print(f"\n### Blocked Domains Still Loading:")
            
            for domain, urls in blocked_details.items():
                if urls:
                    print(f"\n**{domain.upper()} ({len(urls)} requests):**")
                    # Show unique file patterns
                    unique_patterns = set()
                    for url in urls:
                        if '.css' in url:
                            unique_patterns.add('CSS files')
                        elif '.js' in url:
                            unique_patterns.add('JS files')
                        elif '/images/' in url or '.jpg' in url or '.png' in url:
                            unique_patterns.add('Image files')
                        else:
                            unique_patterns.add('Other resources')
                    
                    for pattern in unique_patterns:
                        print(f"  - {pattern}")

if __name__ == "__main__":
    main()