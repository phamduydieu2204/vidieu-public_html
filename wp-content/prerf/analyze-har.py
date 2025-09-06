#!/usr/bin/env python3
"""
HAR File Analyzer
Analyzes HAR files for duplicate requests, errors, and resource optimization
"""

import json
import os
from urllib.parse import urlparse, parse_qs
from collections import defaultdict, Counter

class HarAnalyzer:
    def __init__(self, har_file):
        self.file_name = os.path.basename(har_file).replace('.har', '')
        with open(har_file, 'r', encoding='utf-8') as f:
            self.har_data = json.load(f)
        
        if 'log' not in self.har_data or 'entries' not in self.har_data['log']:
            raise ValueError("Invalid HAR file format")
    
    def analyze(self):
        entries = self.har_data['log']['entries']
        
        analysis = {
            'fileName': self.file_name,
            'totalRequests': len(entries),
            'duplicateRequests': [],
            'errorResponses': [],
            'preloadDuplicates': [],
            'resourcesByType': defaultdict(lambda: {'count': 0, 'totalSize': 0}),
            'wastedBytes': 0,
            'fontDuplicates': [],
            'queryStringDuplicates': []
        }
        
        # Group requests by URL and analyze
        url_groups = defaultdict(list)
        base_url_groups = defaultdict(list)
        preload_urls = set()
        font_urls = Counter()
        
        for entry in entries:
            request = entry['request']
            response = entry['response']
            url = request['url']
            method = request['method']
            
            # Parse URL
            parsed_url = urlparse(url)
            base_url = f"{parsed_url.scheme}://{parsed_url.netloc}{parsed_url.path}"
            query_string = parsed_url.query or ''
            
            # Check for preload/preconnect
            is_preload = False
            for header in request.get('headers', []):
                if header.get('name', '').lower() == 'purpose' and header.get('value') == 'prefetch':
                    is_preload = True
                    break
            
            # Determine resource type
            resource_type = self.get_resource_type(url, response)
            
            # Calculate size
            body_size = response.get('bodySize', 0)
            headers_size = response.get('headersSize', 0)
            total_size = body_size + headers_size
            
            # Track URLs
            url_groups[url].append({
                'method': method,
                'status': response['status'],
                'size': total_size,
                'mimeType': response.get('content', {}).get('mimeType', 'unknown'),
                'resourceType': resource_type,
                'isPreload': is_preload
            })
            
            # Track base URLs
            base_url_groups[base_url].append({
                'fullUrl': url,
                'queryString': query_string,
                'status': response['status'],
                'size': total_size
            })
            
            # Track preloads
            if is_preload:
                preload_urls.add(url)
            
            # Track fonts
            if resource_type == 'font':
                font_urls[url] += 1
            
            # Track errors
            if response['status'] >= 400:
                analysis['errorResponses'].append({
                    'url': url,
                    'status': response['status'],
                    'statusText': response.get('statusText', ''),
                    'resourceType': resource_type
                })
            
            # Count resources by type
            analysis['resourcesByType'][resource_type]['count'] += 1
            analysis['resourcesByType'][resource_type]['totalSize'] += total_size
        
        # Find duplicate requests
        for url, requests in url_groups.items():
            if len(requests) > 1:
                total_size = sum(req['size'] for req in requests)
                wasted_size = total_size - requests[0]['size']
                
                analysis['duplicateRequests'].append({
                    'url': url,
                    'count': len(requests),
                    'resourceType': requests[0]['resourceType'],
                    'totalSize': total_size,
                    'wastedBytes': wasted_size,
                    'requests': requests
                })
                
                analysis['wastedBytes'] += wasted_size
                
                # Check preload duplicates
                if url in preload_urls:
                    analysis['preloadDuplicates'].append({
                        'url': url,
                        'timesLoaded': len(requests),
                        'resourceType': requests[0]['resourceType']
                    })
        
        # Find query string duplicates
        for base_url, requests in base_url_groups.items():
            if len(requests) > 1:
                unique_query_strings = set(req['queryString'] for req in requests)
                
                if len(unique_query_strings) > 1:
                    analysis['queryStringDuplicates'].append({
                        'baseUrl': base_url,
                        'variants': [
                            {
                                'url': req['fullUrl'],
                                'queryString': req['queryString'],
                                'size': req['size']
                            }
                            for req in requests
                        ]
                    })
        
        # Find font duplicates
        for url, count in font_urls.items():
            if count > 1:
                analysis['fontDuplicates'].append({
                    'url': url,
                    'timesLoaded': count
                })
        
        return analysis
    
    def get_resource_type(self, url, response):
        mime_type = response.get('content', {}).get('mimeType', '')
        extension = os.path.splitext(urlparse(url).path)[1].lower()
        
        # Check by MIME type
        if 'javascript' in mime_type or 'ecmascript' in mime_type:
            return 'js'
        elif 'css' in mime_type:
            return 'css'
        elif 'image/' in mime_type:
            return 'image'
        elif 'font/' in mime_type or 'application/font' in mime_type:
            return 'font'
        elif 'text/html' in mime_type:
            return 'html'
        elif 'application/json' in mime_type:
            return 'json'
        
        # Check by extension
        ext_map = {
            '.js': 'js', '.mjs': 'js',
            '.css': 'css',
            '.jpg': 'image', '.jpeg': 'image', '.png': 'image', 
            '.gif': 'image', '.svg': 'image', '.webp': 'image', '.ico': 'image',
            '.woff': 'font', '.woff2': 'font', '.ttf': 'font', 
            '.otf': 'font', '.eot': 'font',
            '.html': 'html', '.htm': 'html',
            '.json': 'json',
            '.xml': 'xml'
        }
        
        return ext_map.get(extension, 'other')
    
    def format_report(self, analysis):
        report = f"\n=== HAR Analysis Report: {analysis['fileName']} ===\n\n"
        
        # Summary
        report += "SUMMARY:\n"
        report += f"- Total Requests: {analysis['totalRequests']}\n"
        report += f"- Duplicate Requests: {len(analysis['duplicateRequests'])}\n"
        report += f"- Error Responses (4xx/5xx): {len(analysis['errorResponses'])}\n"
        report += f"- Total Wasted Bytes: {analysis['wastedBytes']:,} bytes ({self.format_bytes(analysis['wastedBytes'])})\n\n"
        
        # Resources by Type
        report += "RESOURCES BY TYPE:\n"
        for res_type, data in sorted(analysis['resourcesByType'].items()):
            report += f"- {res_type.upper()}: {data['count']} requests, {self.format_bytes(data['totalSize'])}\n"
        report += "\n"
        
        # Duplicate Requests
        if analysis['duplicateRequests']:
            report += "DUPLICATE REQUESTS:\n"
            for dup in sorted(analysis['duplicateRequests'], key=lambda x: x['wastedBytes'], reverse=True):
                report += f"- {self.truncate_url(dup['url'])}\n"
                report += f"  Type: {dup['resourceType']}, Loaded: {dup['count']} times, Wasted: {self.format_bytes(dup['wastedBytes'])}\n"
            report += "\n"
        
        # Query String Duplicates
        if analysis['queryStringDuplicates']:
            report += "QUERY STRING DUPLICATES (same file, different parameters):\n"
            for dup in analysis['queryStringDuplicates']:
                report += f"- Base URL: {self.truncate_url(dup['baseUrl'])}\n"
                for variant in dup['variants']:
                    report += f"  * {variant['queryString'] or '[no query string]'} ({self.format_bytes(variant['size'])})\n"
            report += "\n"
        
        # Preload Duplicates
        if analysis['preloadDuplicates']:
            report += "PRELOAD DUPLICATES:\n"
            for dup in analysis['preloadDuplicates']:
                report += f"- {self.truncate_url(dup['url'])} (Type: {dup['resourceType']}, Loaded: {dup['timesLoaded']} times)\n"
            report += "\n"
        
        # Font Duplicates
        if analysis['fontDuplicates']:
            report += "FONT DUPLICATES:\n"
            for font in analysis['fontDuplicates']:
                report += f"- {self.truncate_url(font['url'])} (Loaded: {font['timesLoaded']} times)\n"
            report += "\n"
        
        # Error Responses
        if analysis['errorResponses']:
            report += "ERROR RESPONSES:\n"
            for error in analysis['errorResponses']:
                report += f"- {error['status']} {error['statusText']}: {self.truncate_url(error['url'])} (Type: {error['resourceType']})\n"
            report += "\n"
        
        return report
    
    @staticmethod
    def format_bytes(bytes_val):
        if bytes_val < 1024:
            return f"{bytes_val} B"
        elif bytes_val < 1048576:
            return f"{bytes_val / 1024:.2f} KB"
        else:
            return f"{bytes_val / 1048576:.2f} MB"
    
    @staticmethod
    def truncate_url(url, max_length=80):
        if len(url) <= max_length:
            return url
        
        parsed = urlparse(url)
        path = parsed.path
        filename = os.path.basename(path)
        
        if len(filename) < 40:
            return '...' + url[-(max_length - 3):]
        else:
            return '...' + filename[-(max_length - 3):]


def main():
    har_files = [
        'trang-chu.har',
        'trang-san-pham.har',
        'trang-bai-viet.har',
        'trang-contact.har'
    ]
    
    input_dir = os.path.join(os.path.dirname(__file__), 'inputs')
    all_reports = ''
    
    for har_file in har_files:
        file_path = os.path.join(input_dir, har_file)
        
        if not os.path.exists(file_path):
            print(f"Warning: HAR file not found: {file_path}")
            continue
        
        try:
            analyzer = HarAnalyzer(file_path)
            analysis = analyzer.analyze()
            report = analyzer.format_report(analysis)
            
            print(report)
            all_reports += report
            
        except Exception as e:
            print(f"Error analyzing {har_file}: {str(e)}")
    
    # Save combined report
    report_path = os.path.join(os.path.dirname(__file__), 'har-analysis-report.txt')
    with open(report_path, 'w', encoding='utf-8') as f:
        f.write(all_reports)
    
    print(f"\nFull report saved to: {report_path}")


if __name__ == '__main__':
    main()