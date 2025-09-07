#!/usr/bin/env python3
"""
Comprehensive HAR file analyzer for performance analysis
"""

import json
import os
from collections import defaultdict
from urllib.parse import urlparse
from datetime import datetime

class HARAnalyzer:
    def __init__(self):
        self.blocked_domains = [
            'googletagmanager.com',
            'google-analytics.com',
            'facebook.com',
            'fbcdn.net',
            'doubleclick.net',
            'googlesyndication.com',
            'googleadservices.com',
            'zalo.me',
            'zaloapp.com',
            'twitter.com',
            'linkedin.com',
            'pinterest.com',
            'instagram.com',
            'youtube.com',
            'tiktok.com',
            'gstatic.com',
            'google.com/recaptcha',
            'recaptcha'
        ]
        
        self.analysis_results = {}
        
    def analyze_har_file(self, filepath):
        """Analyze a single HAR file"""
        with open(filepath, 'r', encoding='utf-8') as f:
            har_data = json.load(f)
            
        filename = os.path.basename(filepath)
        entries = har_data.get('log', {}).get('entries', [])
        pages = har_data.get('log', {}).get('pages', [])
        
        analysis = {
            'filename': filename,
            'total_requests': len(entries),
            'total_size': 0,
            'total_time': 0,
            'duplicate_requests': defaultdict(int),
            'error_responses': [],
            'slow_requests': [],
            'redirect_chains': [],
            'blocked_domain_requests': [],
            'console_errors': [],
            'resource_breakdown': defaultdict(lambda: {'count': 0, 'size': 0, 'time': 0}),
            'domain_breakdown': defaultdict(lambda: {'count': 0, 'size': 0, 'time': 0})
        }
        
        # Track all URLs
        url_counts = defaultdict(int)
        
        for entry in entries:
            request = entry.get('request', {})
            response = entry.get('response', {})
            
            url = request.get('url', '')
            method = request.get('method', '')
            status = response.get('status', 0)
            time = entry.get('time', 0)
            size = response.get('bodySize', 0)
            
            # Parse URL
            parsed_url = urlparse(url)
            domain = parsed_url.netloc
            
            # Update totals
            analysis['total_time'] += time
            analysis['total_size'] += size
            
            # Count URL occurrences
            url_counts[url] += 1
            
            # Track domain stats
            analysis['domain_breakdown'][domain]['count'] += 1
            analysis['domain_breakdown'][domain]['size'] += size
            analysis['domain_breakdown'][domain]['time'] += time
            
            # Check for errors (4xx and 5xx)
            if status >= 400:
                analysis['error_responses'].append({
                    'url': url,
                    'status': status,
                    'method': method,
                    'time': time
                })
            
            # Check for slow requests (>1000ms)
            if time > 1000:
                analysis['slow_requests'].append({
                    'url': url,
                    'time': time,
                    'size': size,
                    'status': status
                })
            
            # Check for redirects
            if 300 <= status < 400:
                location = None
                for header in response.get('headers', []):
                    if header.get('name', '').lower() == 'location':
                        location = header.get('value')
                        break
                        
                analysis['redirect_chains'].append({
                    'from': url,
                    'to': location,
                    'status': status
                })
            
            # Check for blocked domains
            for blocked in self.blocked_domains:
                if blocked in domain.lower() or blocked in url.lower():
                    analysis['blocked_domain_requests'].append({
                        'url': url,
                        'domain': domain,
                        'time': time,
                        'size': size,
                        'status': status
                    })
                    break
            
            # Categorize resource type
            content_type = ''
            for header in response.get('headers', []):
                if header.get('name', '').lower() == 'content-type':
                    content_type = header.get('value', '')
                    break
            
            resource_type = self._get_resource_type(content_type, url)
            analysis['resource_breakdown'][resource_type]['count'] += 1
            analysis['resource_breakdown'][resource_type]['size'] += size
            analysis['resource_breakdown'][resource_type]['time'] += time
        
        # Find duplicates
        for url, count in url_counts.items():
            if count > 1:
                analysis['duplicate_requests'][url] = count
        
        # Extract console messages if available
        browser_data = har_data.get('log', {}).get('browser', {})
        console_messages = browser_data.get('_consoleMessages', [])
        
        for msg in console_messages:
            level = msg.get('level', '')
            if level in ['error', 'severe', 'warning']:
                analysis['console_errors'].append({
                    'level': level,
                    'text': msg.get('text', ''),
                    'timestamp': msg.get('timestamp')
                })
        
        return analysis
    
    def _get_resource_type(self, content_type, url):
        """Determine resource type from content-type or URL"""
        content_type = content_type.lower()
        
        if 'javascript' in content_type or url.endswith('.js'):
            return 'JavaScript'
        elif 'css' in content_type or url.endswith('.css'):
            return 'CSS'
        elif 'html' in content_type:
            return 'HTML'
        elif 'image' in content_type or any(url.endswith(ext) for ext in ['.png', '.jpg', '.jpeg', '.gif', '.webp', '.svg', '.ico']):
            return 'Image'
        elif 'font' in content_type or any(url.endswith(ext) for ext in ['.woff', '.woff2', '.ttf', '.eot']):
            return 'Font'
        elif 'json' in content_type:
            return 'JSON'
        elif 'xml' in content_type:
            return 'XML'
        else:
            return 'Other'
    
    def analyze_directory(self, directory):
        """Analyze all HAR files in a directory"""
        har_files = [f for f in os.listdir(directory) if f.endswith('.har')]
        
        for har_file in har_files:
            filepath = os.path.join(directory, har_file)
            print(f"Analyzing {har_file}...")
            self.analysis_results[har_file] = self.analyze_har_file(filepath)
        
        return self.generate_comprehensive_report()
    
    def generate_comprehensive_report(self):
        """Generate categorized report"""
        report = {
            'summary': {},
            'fixed': [],
            'needs_review': [],
            'critical_issues': []
        }
        
        # Process each HAR file's analysis
        for filename, analysis in self.analysis_results.items():
            # Summary
            report['summary'][filename] = {
                'total_requests': analysis['total_requests'],
                'total_size_mb': round(analysis['total_size'] / 1024 / 1024, 2),
                'total_time_s': round(analysis['total_time'] / 1000, 2),
                'duplicate_count': len(analysis['duplicate_requests']),
                'error_count': len(analysis['error_responses']),
                'slow_request_count': len(analysis['slow_requests']),
                'blocked_domain_count': len(analysis['blocked_domain_requests']),
                'redirect_count': len(analysis['redirect_chains']),
                'console_error_count': len(analysis['console_errors'])
            }
            
            # Categorize issues
            
            # CRITICAL ISSUES
            # 500 errors
            for error in analysis['error_responses']:
                if error['status'] >= 500:
                    report['critical_issues'].append({
                        'file': filename,
                        'type': 'Server Error',
                        'severity': 'CRITICAL',
                        'details': f"500 Error: {error['url']} (Status: {error['status']})"
                    })
            
            # JavaScript errors
            for console_error in analysis['console_errors']:
                if console_error['level'] in ['error', 'severe']:
                    report['critical_issues'].append({
                        'file': filename,
                        'type': 'JavaScript Error',
                        'severity': 'CRITICAL',
                        'details': f"JS {console_error['level']}: {console_error['text'][:200]}"
                    })
            
            # NEEDS REVIEW
            # 404 errors
            for error in analysis['error_responses']:
                if error['status'] == 404:
                    report['needs_review'].append({
                        'file': filename,
                        'type': '404 Not Found',
                        'severity': 'MEDIUM',
                        'details': f"Missing resource: {error['url']}"
                    })
            
            # Duplicate requests
            for url, count in analysis['duplicate_requests'].items():
                report['needs_review'].append({
                    'file': filename,
                    'type': 'Duplicate Request',
                    'severity': 'LOW',
                    'details': f"Loaded {count} times: {url}"
                })
            
            # Slow requests
            for slow_req in analysis['slow_requests']:
                if slow_req['time'] > 3000:  # Very slow
                    severity = 'HIGH'
                elif slow_req['time'] > 2000:
                    severity = 'MEDIUM'
                else:
                    severity = 'LOW'
                    
                report['needs_review'].append({
                    'file': filename,
                    'type': 'Slow Request',
                    'severity': severity,
                    'details': f"Took {slow_req['time']}ms: {slow_req['url']}"
                })
            
            # Redirect chains
            for redirect in analysis['redirect_chains']:
                report['needs_review'].append({
                    'file': filename,
                    'type': 'Redirect',
                    'severity': 'LOW',
                    'details': f"Redirect {redirect['status']}: {redirect['from']} → {redirect['to']}"
                })
            
            # FIXED (Blocked domains that shouldn't be loading)
            for blocked in analysis['blocked_domain_requests']:
                report['fixed'].append({
                    'file': filename,
                    'type': 'Blocked Domain Still Loading',
                    'severity': 'MEDIUM',
                    'details': f"Should be blocked: {blocked['domain']} - {blocked['url']}"
                })
        
        return report
    
    def save_reports(self, report, output_dir):
        """Save analysis reports in multiple formats"""
        # Save JSON report
        json_path = os.path.join(output_dir, 'har-comprehensive-analysis.json')
        with open(json_path, 'w', encoding='utf-8') as f:
            json.dump(report, f, indent=2, ensure_ascii=False)
        
        # Generate Markdown report
        md_content = self._generate_markdown_report(report)
        md_path = os.path.join(output_dir, 'har-comprehensive-analysis.md')
        with open(md_path, 'w', encoding='utf-8') as f:
            f.write(md_content)
        
        return json_path, md_path
    
    def _generate_markdown_report(self, report):
        """Generate markdown report"""
        md = f"# Comprehensive HAR Analysis Report\n\n"
        md += f"Generated: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n\n"
        
        # Executive Summary
        md += "## Executive Summary\n\n"
        total_critical = len(report['critical_issues'])
        total_review = len(report['needs_review'])
        total_fixed = len(report['fixed'])
        
        md += f"- **Critical Issues**: {total_critical}\n"
        md += f"- **Needs Review**: {total_review}\n"
        md += f"- **Fixed/Blocked**: {total_fixed}\n\n"
        
        # Summary Table
        md += "## File Summary\n\n"
        md += "| File | Requests | Size (MB) | Time (s) | Errors | Duplicates | Slow | Blocked | Redirects |\n"
        md += "|------|----------|-----------|----------|--------|------------|------|---------|----------|\n"
        
        for filename, summary in report['summary'].items():
            md += f"| {filename} | {summary['total_requests']} | "
            md += f"{summary['total_size_mb']} | {summary['total_time_s']} | "
            md += f"{summary['error_count']} | {summary['duplicate_count']} | "
            md += f"{summary['slow_request_count']} | {summary['blocked_domain_count']} | "
            md += f"{summary['redirect_count']} |\n"
        
        # Critical Issues
        md += f"\n## Critical Issues ({total_critical})\n\n"
        if report['critical_issues']:
            # Group by type
            issues_by_type = defaultdict(list)
            for issue in report['critical_issues']:
                issues_by_type[issue['type']].append(issue)
            
            for issue_type, issues in issues_by_type.items():
                md += f"### {issue_type} ({len(issues)})\n\n"
                for issue in issues:
                    md += f"- **[{issue['file']}]** {issue['details']}\n"
                md += "\n"
        else:
            md += "*No critical issues found.*\n\n"
        
        # Needs Review
        md += f"## Needs Review ({total_review})\n\n"
        if report['needs_review']:
            # Group by type and severity
            issues_by_type = defaultdict(lambda: defaultdict(list))
            for issue in report['needs_review']:
                issues_by_type[issue['type']][issue['severity']].append(issue)
            
            for issue_type, severity_groups in issues_by_type.items():
                total_type = sum(len(issues) for issues in severity_groups.values())
                md += f"### {issue_type} ({total_type})\n\n"
                
                for severity in ['HIGH', 'MEDIUM', 'LOW']:
                    if severity in severity_groups:
                        md += f"#### {severity} Priority ({len(severity_groups[severity])})\n\n"
                        for issue in severity_groups[severity]:
                            md += f"- **[{issue['file']}]** {issue['details']}\n"
                        md += "\n"
        else:
            md += "*No issues need review.*\n\n"
        
        # Fixed/Blocked Resources
        md += f"## Fixed/Blocked Resources Still Loading ({total_fixed})\n\n"
        if report['fixed']:
            # Group by domain
            by_domain = defaultdict(list)
            for issue in report['fixed']:
                # Extract domain from details
                domain = issue['details'].split(': ')[1].split(' - ')[0]
                by_domain[domain].append(issue)
            
            for domain, issues in sorted(by_domain.items()):
                md += f"### {domain} ({len(issues)})\n\n"
                for issue in issues:
                    md += f"- **[{issue['file']}]** {issue['details']}\n"
                md += "\n"
        else:
            md += "*No blocked resources found still loading.*\n\n"
        
        # Recommendations
        md += "## Recommendations\n\n"
        md += "### Immediate Actions\n\n"
        if total_critical > 0:
            md += "1. **Fix server errors (500)** - These indicate serious backend issues\n"
            md += "2. **Address JavaScript errors** - These can break functionality\n"
        
        md += "\n### Short-term Optimizations\n\n"
        md += "1. **Fix 404 errors** - Missing resources waste bandwidth and time\n"
        md += "2. **Eliminate duplicate requests** - Especially for large files\n"
        md += "3. **Optimize slow requests** - Focus on requests > 2000ms\n"
        md += "4. **Block tracking domains properly** - Many blocked domains still loading\n"
        
        md += "\n### Long-term Improvements\n\n"
        md += "1. **Minimize redirect chains** - Each redirect adds latency\n"
        md += "2. **Implement resource bundling** - Reduce total request count\n"
        md += "3. **Add proper caching headers** - Prevent unnecessary reloads\n"
        
        return md


# Run the analysis
if __name__ == "__main__":
    analyzer = HARAnalyzer()
    input_dir = "/mnt/f/Obsidian/Dữ Liệu Của Tôi/Quản Lý Tài Chính/Quản Lý Kinh Doanh bán Tool/Vidieu.vn/public_html/wp-content/prerf/inputs"
    
    print("Starting comprehensive HAR analysis...")
    report = analyzer.analyze_directory(input_dir)
    
    json_path, md_path = analyzer.save_reports(report, input_dir)
    
    print(f"\nAnalysis complete!")
    print(f"JSON report: {json_path}")
    print(f"Markdown report: {md_path}")
    
    # Print summary
    print(f"\nSummary:")
    print(f"- Critical Issues: {len(report['critical_issues'])}")
    print(f"- Needs Review: {len(report['needs_review'])}")
    print(f"- Fixed/Blocked: {len(report['fixed'])}")