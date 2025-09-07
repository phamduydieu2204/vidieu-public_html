#!/usr/bin/env python3
import json
import os
from urllib.parse import urlparse, parse_qs

def check_mobile_issues(har_path):
    """Check for mobile-specific issues in HAR file."""
    with open(har_path, 'r', encoding='utf-8') as f:
        har_data = json.load(f)
    
    entries = har_data['log']['entries']
    
    # Check user agent to determine if mobile
    is_mobile = False
    user_agent = ""
    mobile_specific_issues = []
    qr_related = []
    vcb_plugin_calls = []
    
    for entry in entries:
        # Get user agent
        headers = entry['request']['headers']
        for header in headers:
            if header['name'].lower() == 'user-agent':
                user_agent = header['value']
                if any(device in user_agent.lower() for device in ['mobile', 'android', 'iphone', 'ipad']):
                    is_mobile = True
                break
        
        url = entry['request']['url']
        
        # Check for QR code related requests
        qr_keywords = ['qr', 'qrcode', 'barcode', 'payment-qr']
        if any(keyword in url.lower() for keyword in qr_keywords):
            qr_related.append({
                'url': url,
                'status': entry['response']['status'],
                'size': entry['response']['bodySize'],
                'time': entry['time']
            })
        
        # Check VCB plugin JS/CSS calls
        if 'vcb-mh' in url:
            vcb_plugin_calls.append({
                'url': url,
                'type': 'JS' if '.js' in url else 'CSS' if '.css' in url else 'Other',
                'status': entry['response']['status'],
                'size': entry['response']['bodySize']
            })
        
        # Check for viewport meta tag requests
        if entry['response']['status'] == 200 and 'text/html' in entry['response']['content'].get('mimeType', ''):
            # This would be the main HTML - can't easily check content in HAR
            pass
    
    # Look for duplicate resource loads
    resource_loads = {}
    duplicates = []
    
    for entry in entries:
        url = entry['request']['url']
        # Remove query params for comparison
        clean_url = url.split('?')[0]
        
        if clean_url in resource_loads:
            resource_loads[clean_url].append({
                'full_url': url,
                'time': entry['startedDateTime'],
                'status': entry['response']['status']
            })
        else:
            resource_loads[clean_url] = [{
                'full_url': url,
                'time': entry['startedDateTime'],
                'status': entry['response']['status']
            }]
    
    # Find duplicates
    for url, loads in resource_loads.items():
        if len(loads) > 1:
            duplicates.append({
                'url': url,
                'count': len(loads),
                'loads': loads
            })
    
    return {
        'is_mobile': is_mobile,
        'user_agent': user_agent,
        'qr_related': qr_related,
        'vcb_plugin_calls': vcb_plugin_calls,
        'duplicates': duplicates
    }

def analyze_payment_confirmation_flow(har_path):
    """Analyze the payment confirmation flow in detail."""
    with open(har_path, 'r', encoding='utf-8') as f:
        har_data = json.load(f)
    
    entries = har_data['log']['entries']
    
    # Timeline of payment-related events
    timeline = []
    
    for entry in entries:
        url = entry['request']['url']
        
        # Key events to track
        if any(keyword in url.lower() for keyword in ['order', 'payment', 'vcb', 'confirm', 'status', 'check']):
            
            # Extract POST data if available
            post_data = None
            if entry['request']['method'] == 'POST' and entry['request'].get('postData'):
                post_data = entry['request']['postData'].get('text', '')
            
            timeline.append({
                'time': entry['startedDateTime'],
                'method': entry['request']['method'],
                'url': url,
                'status': entry['response']['status'],
                'duration': entry['time'],
                'post_data': post_data[:100] if post_data else None  # First 100 chars
            })
    
    # Sort by time
    timeline.sort(key=lambda x: x['time'])
    
    return timeline

def main():
    har_path = '/mnt/f/Obsidian/Dữ Liệu Của Tôi/Quản Lý Tài Chính/Quản Lý Kinh Doanh bán Tool/Vidieu.vn/public_html/wp-content/prerf/inputs/trang-order-received.har'
    
    print("# Order-Received Page Detailed Analysis\n")
    
    # Check mobile issues
    mobile_analysis = check_mobile_issues(har_path)
    
    print("## Device Information")
    print(f"- Mobile Device: {'Yes' if mobile_analysis['is_mobile'] else 'No'}")
    print(f"- User Agent: {mobile_analysis['user_agent'][:100]}...\n")
    
    print("## QR Code Related Requests")
    if mobile_analysis['qr_related']:
        for qr in mobile_analysis['qr_related']:
            print(f"- {qr['url']}")
            print(f"  - Status: {qr['status']}, Size: {qr['size']} bytes, Time: {qr['time']:.0f}ms")
    else:
        print("- No QR code specific requests found")
    
    print("\n## VCB Plugin Resources")
    for call in mobile_analysis['vcb_plugin_calls']:
        print(f"- {call['type']}: {call['url']}")
        print(f"  - Status: {call['status']}, Size: {call['size']} bytes")
    
    print("\n## Duplicate Resource Loads")
    significant_dups = [d for d in mobile_analysis['duplicates'] if d['count'] > 2]
    if significant_dups:
        for dup in significant_dups[:5]:  # Show top 5
            print(f"- {dup['url']} loaded {dup['count']} times")
    else:
        print("- No significant duplicates (>2 loads)")
    
    print("\n## Payment Confirmation Flow Timeline")
    timeline = analyze_payment_confirmation_flow(har_path)
    
    if timeline:
        print("\n### Chronological Order of Payment Events:")
        for i, event in enumerate(timeline[:20]):  # Show first 20 events
            print(f"\n{i+1}. {event['time'].split('T')[1].split('.')[0]} - {event['method']} {event['url'][:80]}...")
            print(f"   Status: {event['status']}, Duration: {event['duration']:.0f}ms")
            if event['post_data']:
                print(f"   POST data: {event['post_data']}...")
    
    # Specific checks for known issues
    print("\n## Known Issue Checks")
    
    # Check for AJAX loops
    ajax_calls = [t for t in timeline if 'admin-ajax.php' in t['url']]
    if len(ajax_calls) > 5:
        print(f"- ⚠️  Potential AJAX loop detected: {len(ajax_calls)} calls to admin-ajax.php")
    
    # Check for missing vcb-mh JS
    vcb_js = [c for c in mobile_analysis['vcb_plugin_calls'] if c['type'] == 'JS']
    if not vcb_js:
        print("- ⚠️  No VCB plugin JavaScript loaded - QR functionality may be broken")
    elif any(c['status'] != 200 for c in vcb_js):
        print("- ⚠️  VCB plugin JavaScript failed to load properly")
    
    # Check console logs for errors
    print("\n## Console Analysis")
    print("- Note: HAR file doesn't contain console logs. Need browser console export for JS errors.")

if __name__ == "__main__":
    main()