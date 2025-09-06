#!/usr/bin/env python3
"""
Phân tích file HAR để tìm các requests có status 404 hoặc failed
"""

import json
import sys
from pathlib import Path
from urllib.parse import urlparse

def analyze_har_for_errors(har_file_path):
    """Phân tích file HAR để tìm các requests lỗi"""
    
    # Đọc file HAR
    with open(har_file_path, 'r', encoding='utf-8') as f:
        har_data = json.load(f)
    
    # Lấy danh sách entries
    entries = har_data['log']['entries']
    
    # Danh sách lưu các requests lỗi
    error_requests = []
    
    for entry in entries:
        request = entry['request']
        response = entry['response']
        status = response['status']
        
        # Kiểm tra status code (404 hoặc các lỗi khác >= 400)
        if status >= 400:
            # Xác định loại tài nguyên
            resource_type = entry.get('_resourceType', 'unknown')
            url = request['url']
            
            # Phân tích URL để lấy extension
            parsed_url = urlparse(url)
            path = parsed_url.path
            extension = Path(path).suffix.lower()
            
            # Xác định loại tài nguyên chi tiết hơn
            if resource_type == 'unknown':
                if extension in ['.css']:
                    resource_type = 'CSS'
                elif extension in ['.js']:
                    resource_type = 'JavaScript'
                elif extension in ['.woff', '.woff2', '.ttf', '.otf', '.eot']:
                    resource_type = 'Font'
                elif extension in ['.jpg', '.jpeg', '.png', '.gif', '.svg', '.webp', '.ico']:
                    resource_type = 'Image'
                elif extension in ['.xml']:
                    resource_type = 'XML'
                else:
                    # Kiểm tra content-type
                    content_type = ''
                    for header in response['headers']:
                        if header['name'].lower() == 'content-type':
                            content_type = header['value'].lower()
                            break
                    
                    if 'css' in content_type:
                        resource_type = 'CSS'
                    elif 'javascript' in content_type or 'script' in content_type:
                        resource_type = 'JavaScript'
                    elif 'font' in content_type:
                        resource_type = 'Font'
                    elif 'image' in content_type:
                        resource_type = 'Image'
                    elif 'xml' in content_type:
                        resource_type = 'XML'
            
            # Lấy thông tin initiator (nguồn gọi)
            initiator = entry.get('_initiator', {})
            initiator_type = initiator.get('type', 'unknown')
            initiator_url = ''
            initiator_line = ''
            
            if initiator_type == 'parser':
                initiator_url = initiator.get('url', '')
                initiator_line = initiator.get('lineNumber', '')
            elif initiator_type == 'script':
                stack = initiator.get('stack', {})
                if stack and 'callFrames' in stack and stack['callFrames']:
                    first_frame = stack['callFrames'][0]
                    initiator_url = first_frame.get('url', '')
                    initiator_line = first_frame.get('lineNumber', '')
            
            error_requests.append({
                'url': url,
                'status': status,
                'resource_type': resource_type,
                'initiator_type': initiator_type,
                'initiator_url': initiator_url,
                'initiator_line': initiator_line,
                'status_text': response.get('statusText', ''),
                'size': response.get('_transferSize', 0)
            })
    
    return error_requests

def print_error_report(error_requests):
    """In báo cáo các requests lỗi"""
    
    if not error_requests:
        print("Không tìm thấy requests nào có status 404 hoặc lỗi khác.")
        return
    
    print(f"Tìm thấy {len(error_requests)} requests có lỗi:\n")
    print("=" * 120)
    
    # Nhóm theo status code
    status_groups = {}
    for req in error_requests:
        status = req['status']
        if status not in status_groups:
            status_groups[status] = []
        status_groups[status].append(req)
    
    # In theo từng nhóm status
    for status in sorted(status_groups.keys()):
        requests = status_groups[status]
        print(f"\n### Status {status} ({len(requests)} requests):\n")
        
        for i, req in enumerate(requests, 1):
            print(f"{i}. URL: {req['url']}")
            print(f"   - Loại tài nguyên: {req['resource_type']}")
            print(f"   - Status: {req['status']} {req['status_text']}")
            
            if req['initiator_url']:
                # Lấy tên file từ URL
                initiator_file = req['initiator_url'].split('/')[-1].split('?')[0] if '/' in req['initiator_url'] else req['initiator_url']
                print(f"   - Được gọi từ: {req['initiator_url']}")
                if req['initiator_line']:
                    print(f"   - Dòng: {req['initiator_line']}")
            else:
                print(f"   - Nguồn gọi: {req['initiator_type']}")
            
            # Phân tích path chi tiết
            url_path = urlparse(req['url']).path
            print(f"   - Path: {url_path}")
            
            print()
    
    # Thống kê theo loại tài nguyên
    print("\n" + "=" * 120)
    print("\n### Thống kê theo loại tài nguyên:\n")
    
    resource_stats = {}
    for req in error_requests:
        resource_type = req['resource_type']
        if resource_type not in resource_stats:
            resource_stats[resource_type] = 0
        resource_stats[resource_type] += 1
    
    for resource_type, count in sorted(resource_stats.items(), key=lambda x: x[1], reverse=True):
        print(f"- {resource_type}: {count} requests")
    
    # Danh sách chi tiết các file lỗi
    print("\n" + "=" * 120)
    print("\n### Chi tiết các file lỗi:\n")
    
    for req in error_requests:
        url_parts = urlparse(req['url'])
        filename = url_parts.path.split('/')[-1] if url_parts.path else 'unknown'
        print(f"- {filename} ({req['resource_type']})")
        print(f"  Full URL: {req['url']}")
        print(f"  Status: {req['status']}")
        if req['initiator_url']:
            print(f"  Gọi từ: {req['initiator_url']} (dòng {req['initiator_line']})")
        print()

if __name__ == "__main__":
    har_file = "inputs/home.har"
    
    if len(sys.argv) > 1:
        har_file = sys.argv[1]
    
    try:
        error_requests = analyze_har_for_errors(har_file)
        print_error_report(error_requests)
    except Exception as e:
        print(f"Lỗi khi phân tích file HAR: {e}")
        sys.exit(1)