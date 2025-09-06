#!/usr/bin/env python3
"""
Phân tích các preload tags trong file HAR và kiểm tra hiệu quả sử dụng
"""

import json
import re
from pathlib import Path
from urllib.parse import urlparse
from datetime import datetime

def extract_preload_tags_from_html(html_content):
    """Extract preload tags from HTML content"""
    preload_tags = []
    
    # Pattern to find preload tags
    pattern = r'<link[^>]*rel=["\']preload["\'][^>]*>'
    matches = re.finditer(pattern, html_content, re.IGNORECASE)
    
    for match in matches:
        tag = match.group(0)
        
        # Extract attributes
        href_match = re.search(r'href=["\']([^"\']+)["\']', tag)
        as_match = re.search(r'as=["\']([^"\']+)["\']', tag)
        type_match = re.search(r'type=["\']([^"\']+)["\']', tag)
        crossorigin_match = re.search(r'crossorigin', tag)
        
        if href_match:
            preload_tags.append({
                'href': href_match.group(1),
                'as': as_match.group(1) if as_match else None,
                'type': type_match.group(1) if type_match else None,
                'crossorigin': True if crossorigin_match else False,
                'full_tag': tag
            })
    
    return preload_tags

def analyze_har_file(har_path):
    """Analyze HAR file for preload usage"""
    print(f"Phân tích file: {har_path}")
    
    with open(har_path, 'r', encoding='utf-8') as f:
        har_data = json.load(f)
    
    # Get all entries
    entries = har_data['log']['entries']
    
    # Find the main HTML response
    main_html_response = None
    preload_tags = []
    
    for entry in entries:
        request = entry['request']
        response = entry['response']
        
        # Find the main HTML document
        if (request['method'] == 'GET' and 
            response['content'].get('mimeType', '').startswith('text/html') and
            response['status'] == 200):
            
            # Check if this is the main page
            url = request['url']
            parsed = urlparse(url)
            if parsed.path in ['/', ''] and not parsed.query:
                main_html_response = entry
                html_content = response['content'].get('text', '')
                preload_tags = extract_preload_tags_from_html(html_content)
                break
    
    print(f"\n=== TÌM THẤY {len(preload_tags)} PRELOAD TAGS ===\n")
    
    # Analyze each preload tag
    preload_analysis = []
    
    for idx, tag in enumerate(preload_tags, 1):
        print(f"{idx}. {tag['href']}")
        print(f"   As: {tag['as']}")
        print(f"   Type: {tag['type']}")
        print(f"   Crossorigin: {tag['crossorigin']}")
        
        # Check if resource was actually loaded
        resource_loaded = False
        load_timing = None
        
        for entry in entries:
            request_url = entry['request']['url']
            
            # Normalize URLs for comparison
            if (tag['href'] in request_url or 
                request_url.endswith(tag['href'].lstrip('/')) or
                tag['href'].replace('//', 'https://') == request_url):
                
                resource_loaded = True
                
                # Get timing info
                if 'time' in entry:
                    load_timing = entry['time']
                
                # Check response status
                status = entry['response']['status']
                print(f"   Loaded: Yes (Status: {status})")
                print(f"   Load time: {load_timing:.2f}ms" if load_timing else "   Load time: N/A")
                
                # Check if resource was used after preload
                if 'initiator' in entry['request']:
                    initiator = entry['request']['initiator']
                    print(f"   Initiator type: {initiator.get('type', 'unknown')}")
                
                break
        
        if not resource_loaded:
            print(f"   Loaded: NO - RESOURCE NOT USED!")
        
        print()
        
        preload_analysis.append({
            'href': tag['href'],
            'as': tag['as'],
            'type': tag['type'],
            'crossorigin': tag['crossorigin'],
            'loaded': resource_loaded,
            'load_timing': load_timing
        })
    
    # Summary
    print("\n=== TÓM TẮT PHÂN TÍCH PRELOAD ===\n")
    
    total_preloads = len(preload_analysis)
    used_preloads = sum(1 for p in preload_analysis if p['loaded'])
    unused_preloads = total_preloads - used_preloads
    
    print(f"Tổng số preload tags: {total_preloads}")
    print(f"Đã sử dụng: {used_preloads}")
    print(f"Không sử dụng: {unused_preloads}")
    
    if unused_preloads > 0:
        print(f"\n⚠️  CÓ {unused_preloads} PRELOAD RESOURCES KHÔNG ĐƯỢC SỬ DỤNG!")
        print("\nCác resources không được sử dụng:")
        for p in preload_analysis:
            if not p['loaded']:
                print(f"  - {p['href']}")
    
    # Analyze preload effectiveness by type
    print("\n=== PHÂN TÍCH THEO LOẠI RESOURCE ===\n")
    
    by_type = {}
    for p in preload_analysis:
        as_type = p['as'] or 'unknown'
        if as_type not in by_type:
            by_type[as_type] = {'total': 0, 'used': 0}
        by_type[as_type]['total'] += 1
        if p['loaded']:
            by_type[as_type]['used'] += 1
    
    for as_type, stats in by_type.items():
        effectiveness = (stats['used'] / stats['total'] * 100) if stats['total'] > 0 else 0
        print(f"{as_type}: {stats['used']}/{stats['total']} ({effectiveness:.0f}% hiệu quả)")
    
    # Check for common preload issues
    print("\n=== KIỂM TRA VẤN ĐỀ THƯỜNG GẶP ===\n")
    
    # Check for fonts without crossorigin
    font_issues = [p for p in preload_analysis if p['as'] == 'font' and not p['crossorigin']]
    if font_issues:
        print(f"⚠️  {len(font_issues)} font preloads thiếu crossorigin attribute:")
        for p in font_issues:
            print(f"  - {p['href']}")
    
    # Check for duplicate preloads
    seen_hrefs = {}
    duplicates = []
    for p in preload_tags:
        href = p['href']
        if href in seen_hrefs:
            duplicates.append(href)
        else:
            seen_hrefs[href] = True
    
    if duplicates:
        print(f"\n⚠️  Phát hiện preload trùng lặp:")
        for href in set(duplicates):
            print(f"  - {href}")
    
    # Recommendations
    print("\n=== KHUYẾN NGHỊ ===\n")
    
    if unused_preloads > 0:
        print("1. Xóa các preload tags không được sử dụng để tránh lãng phí băng thông")
    
    if font_issues:
        print("2. Thêm crossorigin attribute cho tất cả font preloads")
    
    if duplicates:
        print("3. Loại bỏ các preload tags trùng lặp")
    
    # Check for missing preloads for critical resources
    print("\n4. Xem xét thêm preload cho các resources quan trọng khác:")
    
    # Find CSS files loaded early but not preloaded
    early_css = []
    for entry in entries[:20]:  # Check first 20 requests
        request = entry['request']
        response = entry['response']
        
        if (request['method'] == 'GET' and 
            response['content'].get('mimeType', '').startswith('text/css') and
            response['status'] == 200):
            
            url = request['url']
            is_preloaded = any(url.endswith(p['href'].lstrip('/')) for p in preload_tags)
            
            if not is_preloaded:
                early_css.append(url)
    
    if early_css:
        print("   CSS files được load sớm nhưng chưa preload:")
        for css in early_css[:5]:  # Show max 5
            print(f"   - {css}")
    
    return preload_analysis

if __name__ == "__main__":
    har_path = Path("inputs/home.har")
    
    if not har_path.exists():
        print(f"Không tìm thấy file: {har_path}")
        exit(1)
    
    analyze_har_file(har_path)