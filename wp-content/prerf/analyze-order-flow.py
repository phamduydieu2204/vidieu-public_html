#!/usr/bin/env python3
import json
from datetime import datetime
from collections import defaultdict

def parse_har(file_path):
    with open(file_path, 'r') as f:
        har = json.load(f)
    return har

def analyze_checkout_flow(har_data):
    """Analyze the checkout flow from clicking order to order-received page"""
    
    pages = har_data['log']['pages']
    entries = har_data['log']['entries']
    
    print("=== ORDER FLOW ANALYSIS ===\n")
    
    # Page transitions
    print("📄 PAGE TRANSITIONS:")
    for i, page in enumerate(pages):
        start_time = datetime.fromisoformat(page['startedDateTime'].replace('Z', '+00:00'))
        print(f"\nPage {i+1}: {page['title']}")
        print(f"  Started: {start_time}")
        print(f"  DOMContentLoaded: {page['pageTimings']['onContentLoad']:.2f}ms")
        print(f"  Load Complete: {page['pageTimings']['onLoad']:.2f}ms")
    
    # Calculate time gaps between pages
    print("\n⏰ TIME GAPS BETWEEN PAGES:")
    for i in range(len(pages) - 1):
        start1 = datetime.fromisoformat(pages[i]['startedDateTime'].replace('Z', '+00:00'))
        start2 = datetime.fromisoformat(pages[i+1]['startedDateTime'].replace('Z', '+00:00'))
        gap = (start2 - start1).total_seconds()
        print(f"  Gap between Page {i+1} and Page {i+2}: {gap:.2f} seconds")
    
    # Find critical requests
    print("\n🔍 CRITICAL REQUESTS IN CHECKOUT FLOW:")
    
    # Group entries by page
    page_entries = defaultdict(list)
    for entry in entries:
        page_entries[entry.get('pageref', 'unknown')].append(entry)
    
    # Analyze checkout page (page_21)
    print("\n1️⃣ CHECKOUT PAGE (page_21) - Critical Requests:")
    checkout_entries = page_entries['page_21']
    
    # Find admin-ajax.php POST request
    for entry in checkout_entries:
        if 'admin-ajax.php' in entry['request']['url'] and entry['request']['method'] == 'POST':
            print(f"\n  ⚡ Admin AJAX Order Processing:")
            print(f"    URL: {entry['request']['url']}")
            if 'postData' in entry['request']:
                print(f"    Action: {entry['request']['postData'].get('text', '')[:100]}...")
            print(f"    Started: {entry['startedDateTime']}")
            print(f"    Total Time: {entry['time']:.2f}ms")
            print(f"    Server Wait Time: {entry['timings']['wait']:.2f}ms")
            print(f"    Status: {entry['response']['status']}")
    
    # Find WooCommerce Store API calls
    for entry in checkout_entries:
        if 'wp-json/wc/store' in entry['request']['url'] and entry['request']['method'] == 'POST':
            print(f"\n  🛒 WooCommerce Store API:")
            print(f"    URL: {entry['request']['url']}")
            print(f"    Total Time: {entry['time']:.2f}ms")
            print(f"    Server Wait Time: {entry['timings']['wait']:.2f}ms")
    
    # Analyze order-received page (page_22)
    print("\n\n2️⃣ ORDER-RECEIVED PAGE (page_22) - Initial Load:")
    order_entries = page_entries['page_22']
    
    # Find the main document request
    for entry in order_entries:
        if entry.get('_resourceType') == 'document' and 'order-received' in entry['request']['url']:
            print(f"\n  📄 Order Received Page Load:")
            print(f"    URL: {entry['request']['url']}")
            print(f"    Started: {entry['startedDateTime']}")
            print(f"    Total Time: {entry['time']:.2f}ms")
            print(f"    Server Wait Time: {entry['timings']['wait']:.2f}ms")
            print(f"    Status: {entry['response']['status']}")
            print(f"    Content Size: {entry['response']['content']['size']} bytes")
    
    # Find slow resources
    print("\n\n🐌 SLOWEST RESOURCES (>1000ms):")
    slow_requests = []
    
    for page_id, entries in page_entries.items():
        for entry in entries:
            if entry['time'] > 1000:
                slow_requests.append({
                    'url': entry['request']['url'],
                    'time': entry['time'],
                    'wait': entry['timings']['wait'],
                    'page': page_id,
                    'type': entry.get('_resourceType', 'unknown'),
                    'method': entry['request']['method']
                })
    
    slow_requests.sort(key=lambda x: x['time'], reverse=True)
    
    for req in slow_requests[:10]:
        print(f"\n  ⏱️  {req['time']:.0f}ms - {req['method']} {req['url'][:80]}...")
        print(f"     Page: {req['page']}, Type: {req['type']}, Server Wait: {req['wait']:.0f}ms")
    
    # Analyze external resources
    print("\n\n🌐 EXTERNAL RESOURCE IMPACT:")
    external_domains = defaultdict(lambda: {'count': 0, 'total_time': 0})
    
    for page_id, entries in page_entries.items():
        for entry in entries:
            url = entry['request']['url']
            if not url.startswith('https://vidieu.vn'):
                domain = url.split('/')[2]
                external_domains[domain]['count'] += 1
                external_domains[domain]['total_time'] += entry['time']
    
    print("\n  External domains by total time:")
    sorted_domains = sorted(external_domains.items(), key=lambda x: x[1]['total_time'], reverse=True)
    for domain, stats in sorted_domains[:5]:
        print(f"    {domain}: {stats['count']} requests, {stats['total_time']:.0f}ms total")
    
    # Calculate total flow time
    print("\n\n📊 TOTAL FLOW SUMMARY:")
    start_checkout = datetime.fromisoformat(pages[0]['startedDateTime'].replace('Z', '+00:00'))
    
    # Find when order-received page finished loading
    if len(pages) > 1:
        start_order_received = datetime.fromisoformat(pages[1]['startedDateTime'].replace('Z', '+00:00'))
        order_received_load_time = pages[1]['pageTimings']['onLoad'] / 1000  # Convert to seconds
        
        total_flow_time = (start_order_received - start_checkout).total_seconds() + order_received_load_time
        
        print(f"\n  ⏱️  Total checkout flow time: {total_flow_time:.2f} seconds")
        print(f"  ├─ Time to process order: {(start_order_received - start_checkout).total_seconds():.2f}s")
        print(f"  └─ Order-received page load: {order_received_load_time:.2f}s")
    
    # Find potential bottlenecks
    print("\n\n🔴 IDENTIFIED BOTTLENECKS:")
    print("\n  1. Admin AJAX Processing (~4 seconds)")
    print("     - The elessi_simple_checkout action takes too long")
    print("     - This is likely the main order processing bottleneck")
    
    print("\n  2. Order-Received Page Load (~7-8 seconds)")
    print("     - Very slow server response time")
    print("     - Large HTML response (446KB)")
    print("     - Multiple external resources loading")
    
    print("\n  3. External Resources")
    print("     - Google reCAPTCHA requests")
    print("     - Kaspersky antivirus scripts")
    print("     - Multiple tracking/analytics scripts")

if __name__ == "__main__":
    har_file = "inputs/trang-order-received.har"
    har_data = parse_har(har_file)
    analyze_checkout_flow(har_data)