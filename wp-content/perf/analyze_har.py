#!/usr/bin/env python3
import json
import sys
from urllib.parse import urlparse
from collections import defaultdict
import os

def analyze_har(har_file):
    with open(har_file, 'r', encoding='utf-8') as f:
        har_data = json.load(f)
    
    log = har_data['log']
    entries = log['entries']
    page = log['pages'][0] if log['pages'] else {}
    
    print("="*80)
    print(f"HAR Analysis: {os.path.basename(har_file)}")
    print("="*80)
    
    # 1. Tổng số requests
    print(f"\n1. TỔNG SỐ REQUESTS: {len(entries)}")
    
    # 2. Tổng kích thước trang
    total_size = 0
    total_compressed_size = 0
    
    for entry in entries:
        response = entry.get('response', {})
        content = response.get('content', {})
        size = content.get('size', 0)
        total_size += size
        
        # Calculate compressed size (use transferSize if available)
        transfer_size = response.get('_transferSize', 0)
        if transfer_size > 0:
            total_compressed_size += transfer_size
        else:
            # Estimate compression ratio
            encoding = content.get('encoding', '')
            if encoding in ['gzip', 'br', 'deflate']:
                total_compressed_size += int(size * 0.3)  # Assume 70% compression
            else:
                total_compressed_size += size
    
    print(f"\n2. TỔNG KÍCH THƯỚC TRANG:")
    print(f"   - Uncompressed: {total_size:,} bytes ({total_size/1024/1024:.2f} MB)")
    print(f"   - Compressed (estimated): {total_compressed_size:,} bytes ({total_compressed_size/1024/1024:.2f} MB)")
    
    # 3. Thời gian tải
    print(f"\n3. THỜI GIAN TẢI:")
    
    # TTFB của document chính
    main_doc = None
    for entry in entries:
        if entry.get('_resourceType') == 'document':
            main_doc = entry
            break
    
    if main_doc:
        ttfb = main_doc['timings'].get('wait', 0)
        print(f"   - TTFB (Time to First Byte): {ttfb:.2f} ms")
    
    # Page timings
    if page:
        dom_content_loaded = page['pageTimings'].get('onContentLoad', 0)
        on_load = page['pageTimings'].get('onLoad', 0)
        print(f"   - DOMContentLoaded: {dom_content_loaded:.2f} ms")
        print(f"   - Load: {on_load:.2f} ms")
    
    # 4. Render-blocking resources
    print(f"\n4. RENDER-BLOCKING RESOURCES:")
    render_blocking = []
    
    for entry in entries:
        resource_type = entry.get('_resourceType', '')
        url = entry['request']['url']
        
        # Check for render-blocking CSS
        if resource_type == 'stylesheet':
            priority = entry.get('_priority', '')
            if priority in ['VeryHigh', 'High']:
                render_blocking.append({
                    'url': url,
                    'type': 'CSS',
                    'size': entry['response'].get('content', {}).get('size', 0),
                    'time': entry['time']
                })
        
        # Check for render-blocking JS (in head, no async/defer)
        elif resource_type == 'script':
            initiator = entry.get('_initiator', {})
            if initiator.get('type') == 'parser' and entry.get('_priority') in ['High', 'Medium']:
                # Likely a render-blocking script
                render_blocking.append({
                    'url': url,
                    'type': 'JS',
                    'size': entry['response'].get('content', {}).get('size', 0),
                    'time': entry['time']
                })
    
    print(f"   Found {len(render_blocking)} render-blocking resources:")
    for rb in sorted(render_blocking, key=lambda x: x['time'], reverse=True)[:10]:
        print(f"   - [{rb['type']}] {rb['url'][:80]}...")
        print(f"     Size: {rb['size']:,} bytes, Load time: {rb['time']:.2f} ms")
    
    # 5. Largest resources
    print(f"\n5. LARGEST RESOURCES (Top 10):")
    sorted_by_size = sorted(entries, key=lambda x: x['response'].get('bodySize', 0), reverse=True)[:10]
    
    for i, entry in enumerate(sorted_by_size, 1):
        url = entry['request']['url']
        size = entry['response'].get('content', {}).get('size', 0)
        resource_type = entry.get('_resourceType', 'unknown')
        print(f"   {i}. [{resource_type}] {url[:70]}...")
        print(f"      Size: {size:,} bytes ({size/1024:.2f} KB)")
    
    # 6. Third-party resources
    print(f"\n6. THIRD-PARTY RESOURCES:")
    main_domain = urlparse(entries[0]['request']['url']).netloc if entries else ''
    third_party_stats = defaultdict(lambda: {'count': 0, 'size': 0})
    
    for entry in entries:
        url = entry['request']['url']
        domain = urlparse(url).netloc
        
        if domain and domain != main_domain and not domain.endswith(main_domain):
            third_party_stats[domain]['count'] += 1
            third_party_stats[domain]['size'] += entry['response'].get('content', {}).get('size', 0)
    
    sorted_third_parties = sorted(third_party_stats.items(), 
                                 key=lambda x: x[1]['size'], reverse=True)
    
    print(f"   Total third-party domains: {len(third_party_stats)}")
    print(f"   Top third-party domains by size:")
    for domain, stats in sorted_third_parties[:10]:
        print(f"   - {domain}: {stats['count']} requests, {stats['size']:,} bytes")
    
    # 7. Font loading
    print(f"\n7. FONT LOADING:")
    font_requests = []
    for entry in entries:
        url = entry['request']['url']
        if any(ext in url.lower() for ext in ['.woff', '.woff2', '.ttf', '.eot', '.otf']):
            font_requests.append({
                'url': url,
                'size': entry['response'].get('content', {}).get('size', 0),
                'time': entry['time']
            })
    
    print(f"   Total font files: {len(font_requests)}")
    total_font_size = sum(f['size'] for f in font_requests)
    print(f"   Total font size: {total_font_size:,} bytes ({total_font_size/1024:.2f} KB)")
    
    for font in font_requests[:5]:
        print(f"   - {os.path.basename(font['url'])}: {font['size']:,} bytes, {font['time']:.2f} ms")
    
    # 8. Image loading patterns
    print(f"\n8. IMAGE LOADING PATTERNS:")
    image_stats = {
        'total': 0,
        'lazy_loaded': 0,
        'formats': defaultdict(int),
        'total_size': 0
    }
    
    for entry in entries:
        resource_type = entry.get('_resourceType', '')
        if resource_type == 'image':
            url = entry['request']['url']
            image_stats['total'] += 1
            image_stats['total_size'] += entry['response'].get('content', {}).get('size', 0)
            
            # Detect format
            ext = os.path.splitext(urlparse(url).path)[1].lower()
            if ext:
                image_stats['formats'][ext] += 1
            
            # Check if lazy loaded (heuristic: loaded after DOMContentLoaded)
            if page and 'startedDateTime' in entry:
                # This is a simplified check
                if entry.get('time', 0) > page['pageTimings'].get('onContentLoad', 0):
                    image_stats['lazy_loaded'] += 1
    
    print(f"   Total images: {image_stats['total']}")
    print(f"   Total size: {image_stats['total_size']:,} bytes ({image_stats['total_size']/1024/1024:.2f} MB)")
    print(f"   Possibly lazy-loaded: {image_stats['lazy_loaded']}")
    print(f"   Image formats:")
    for fmt, count in sorted(image_stats['formats'].items(), key=lambda x: x[1], reverse=True):
        print(f"   - {fmt}: {count} images")
    
    # Additional stats
    print(f"\n9. ADDITIONAL STATISTICS:")
    
    # Resource types breakdown
    resource_types = defaultdict(lambda: {'count': 0, 'size': 0})
    for entry in entries:
        rtype = entry.get('_resourceType', 'other')
        resource_types[rtype]['count'] += 1
        resource_types[rtype]['size'] += entry['response'].get('content', {}).get('size', 0)
    
    print(f"   Resource types breakdown:")
    for rtype, stats in sorted(resource_types.items(), key=lambda x: x[1]['size'], reverse=True):
        print(f"   - {rtype}: {stats['count']} requests, {stats['size']:,} bytes")
    
    # HTTP version stats
    http_versions = defaultdict(int)
    for entry in entries:
        version = entry['request'].get('httpVersion', 'unknown')
        http_versions[version] += 1
    
    print(f"\n   HTTP versions:")
    for version, count in sorted(http_versions.items()):
        print(f"   - {version}: {count} requests")
    
    # Cache stats
    cache_stats = {'hit': 0, 'miss': 0, 'unknown': 0}
    for entry in entries:
        if entry.get('cache', {}).get('beforeRequest'):
            cache_stats['hit'] += 1
        elif entry['response'].get('status') == 304:
            cache_stats['hit'] += 1
        elif entry['response'].get('status') in [200, 201]:
            cache_stats['miss'] += 1
        else:
            cache_stats['unknown'] += 1
    
    print(f"\n   Cache statistics:")
    print(f"   - Cache hits: {cache_stats['hit']}")
    print(f"   - Cache misses: {cache_stats['miss']}")
    print(f"   - Unknown: {cache_stats['unknown']}")

if __name__ == '__main__':
    har_file = sys.argv[1] if len(sys.argv) > 1 else 'home.har'
    analyze_har(har_file)