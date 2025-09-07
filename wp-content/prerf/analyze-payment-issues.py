#!/usr/bin/env python3
import json
import os
import sys
from collections import defaultdict
from urllib.parse import urlparse

def analyze_har_file(file_path):
    """Analyze a single HAR file for various metrics."""
    with open(file_path, 'r', encoding='utf-8') as f:
        har_data = json.load(f)
    
    entries = har_data['log']['entries']
    total_requests = len(entries)
    
    # Categorize requests
    request_types = defaultdict(int)
    domains = defaultdict(int)
    ajax_requests = []
    payment_related = []
    console_errors = []
    pusher_requests = []
    
    for entry in entries:
        request = entry['request']
        url = request['url']
        parsed = urlparse(url)
        domain = parsed.netloc
        
        domains[domain] += 1
        
        # Identify request type
        mime_type = entry['response']['content'].get('mimeType', '')
        if 'javascript' in mime_type or url.endswith('.js'):
            request_types['JavaScript'] += 1
        elif 'css' in mime_type or url.endswith('.css'):
            request_types['CSS'] += 1
        elif 'image' in mime_type or any(url.endswith(ext) for ext in ['.jpg', '.png', '.gif', '.webp', '.svg']):
            request_types['Images'] += 1
        elif 'font' in mime_type or any(url.endswith(ext) for ext in ['.woff', '.woff2', '.ttf', '.eot']):
            request_types['Fonts'] += 1
        elif 'admin-ajax.php' in url or 'ajax' in url.lower():
            request_types['AJAX'] += 1
            ajax_requests.append({
                'url': url,
                'method': request['method'],
                'time': entry['time'],
                'response_status': entry['response']['status']
            })
        else:
            request_types['Other'] += 1
        
        # Check for payment-related requests
        payment_keywords = ['vcb', 'payment', 'order', 'checkout', 'qr', 'bank', 'transaction']
        if any(keyword in url.lower() for keyword in payment_keywords):
            payment_related.append({
                'url': url,
                'method': request['method'],
                'status': entry['response']['status'],
                'time': entry['time']
            })
        
        # Check for Pusher/WebSocket connections
        if 'pusher' in url.lower() or 'websocket' in url.lower() or 'ws.' in domain:
            pusher_requests.append({
                'url': url,
                'status': entry['response']['status']
            })
    
    # Check for console logs/errors if available
    if 'pages' in har_data['log']:
        for page in har_data['log']['pages']:
            if '_console' in page:
                for log in page['_console']:
                    if log['level'] in ['error', 'warning']:
                        console_errors.append({
                            'level': log['level'],
                            'text': log['text']
                        })
    
    return {
        'total_requests': total_requests,
        'request_types': dict(request_types),
        'domains': dict(domains),
        'ajax_requests': ajax_requests,
        'payment_related': payment_related,
        'console_errors': console_errors,
        'pusher_requests': pusher_requests
    }

def analyze_order_received_details(file_path):
    """Detailed analysis of order-received page for payment issues."""
    with open(file_path, 'r', encoding='utf-8') as f:
        har_data = json.load(f)
    
    entries = har_data['log']['entries']
    
    # Look for polling patterns
    ajax_calls = defaultdict(list)
    vcb_calls = []
    
    for entry in entries:
        url = entry['request']['url']
        
        # Group AJAX calls by endpoint
        if 'admin-ajax.php' in url:
            # Extract action parameter if available
            action = None
            if entry['request'].get('postData'):
                post_data = entry['request']['postData'].get('text', '')
                if 'action=' in post_data:
                    action = post_data.split('action=')[1].split('&')[0]
            
            ajax_calls[action or 'unknown'].append({
                'time': entry['startedDateTime'],
                'duration': entry['time'],
                'status': entry['response']['status']
            })
        
        # VCB specific calls
        if 'vcb' in url.lower():
            vcb_calls.append({
                'url': url,
                'time': entry['startedDateTime'],
                'status': entry['response']['status'],
                'duration': entry['time']
            })
    
    # Check for loops (repeated calls to same endpoint)
    loops_detected = {}
    for action, calls in ajax_calls.items():
        if len(calls) > 5:  # More than 5 calls might indicate a loop
            loops_detected[action] = {
                'count': len(calls),
                'total_duration': sum(call['duration'] for call in calls),
                'statuses': [call['status'] for call in calls]
            }
    
    return {
        'ajax_endpoints': {k: len(v) for k, v in ajax_calls.items()},
        'loops_detected': loops_detected,
        'vcb_calls': vcb_calls
    }

def main():
    har_dir = '/mnt/f/Obsidian/Dữ Liệu Của Tôi/Quản Lý Tài Chính/Quản Lý Kinh Doanh bán Tool/Vidieu.vn/public_html/wp-content/prerf/inputs'
    
    results = {}
    
    # Analyze all HAR files
    har_files = [
        'trang-chu.har',
        'trang-san-pham.har',
        'trang-bai-viet.har',
        'trang-contact.har',
        'trang-cart.har',
        'trang-checkout.har',
        'trang-order-received.har'
    ]
    
    print("# HAR Files Analysis Report\n")
    print("## Summary Table\n")
    print("| Page | Total Requests | JS | CSS | Images | Fonts | AJAX | Other |")
    print("|------|----------------|----|----|--------|-------|------|-------|")
    
    for har_file in har_files:
        file_path = os.path.join(har_dir, har_file)
        if os.path.exists(file_path):
            analysis = analyze_har_file(file_path)
            results[har_file] = analysis
            
            types = analysis['request_types']
            print(f"| {har_file.replace('.har', '')} | {analysis['total_requests']} | "
                  f"{types.get('JavaScript', 0)} | {types.get('CSS', 0)} | "
                  f"{types.get('Images', 0)} | {types.get('Fonts', 0)} | "
                  f"{types.get('AJAX', 0)} | {types.get('Other', 0)} |")
    
    # Detailed analysis of order-received page
    print("\n## Order-Received Page Detailed Analysis\n")
    order_analysis = analyze_order_received_details(os.path.join(har_dir, 'trang-order-received.har'))
    
    print("### AJAX Endpoints Called:")
    for endpoint, count in order_analysis['ajax_endpoints'].items():
        print(f"- {endpoint}: {count} calls")
    
    print("\n### Potential Loops Detected:")
    if order_analysis['loops_detected']:
        for action, info in order_analysis['loops_detected'].items():
            print(f"- **{action}**: {info['count']} calls, total duration: {info['total_duration']:.0f}ms")
            print(f"  - Status codes: {set(info['statuses'])}")
    else:
        print("No loops detected")
    
    print("\n### VCB Payment Related Calls:")
    if order_analysis['vcb_calls']:
        for call in order_analysis['vcb_calls'][:5]:  # Show first 5
            print(f"- {call['url']}")
            print(f"  - Status: {call['status']}, Duration: {call['duration']:.0f}ms")
    else:
        print("No VCB-specific calls found")
    
    # Check for payment-related issues across all pages
    print("\n## Payment-Related Requests Across All Pages\n")
    for har_file, analysis in results.items():
        if analysis['payment_related']:
            print(f"\n### {har_file}:")
            for req in analysis['payment_related'][:3]:  # Show first 3
                print(f"- {req['method']} {req['url']}")
                print(f"  - Status: {req['status']}, Duration: {req['time']:.0f}ms")
    
    # Console errors
    print("\n## JavaScript Console Errors\n")
    for har_file, analysis in results.items():
        if analysis['console_errors']:
            print(f"\n### {har_file}:")
            for error in analysis['console_errors']:
                print(f"- [{error['level']}] {error['text'][:100]}...")
    
    # Pusher/WebSocket connections
    print("\n## Pusher/WebSocket Connections\n")
    for har_file, analysis in results.items():
        if analysis['pusher_requests']:
            print(f"\n### {har_file}:")
            for req in analysis['pusher_requests']:
                print(f"- {req['url']} (Status: {req['status']})")

if __name__ == "__main__":
    main()