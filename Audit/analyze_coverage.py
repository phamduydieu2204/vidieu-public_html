#!/usr/bin/env python3
import json
import os
from urllib.parse import urlparse
from collections import defaultdict

def analyze_coverage(filepath):
    """Analyze Chrome DevTools Coverage report"""
    
    with open(filepath, 'r', encoding='utf-8') as f:
        coverage_data = json.load(f)
    
    stats = {
        'total_files': len(coverage_data),
        'total_bytes': 0,
        'total_used_bytes': 0,
        'total_unused_bytes': 0,
        'css_files': {'count': 0, 'total': 0, 'used': 0, 'unused': 0},
        'js_files': {'count': 0, 'total': 0, 'used': 0, 'unused': 0},
        'by_source': defaultdict(lambda: {'files': 0, 'total': 0, 'used': 0, 'unused': 0}),
        'worst_offenders': [],
        'plugin_analysis': defaultdict(lambda: {'files': 0, 'total': 0, 'used': 0, 'unused': 0}),
        'theme_analysis': {'files': 0, 'total': 0, 'used': 0, 'unused': 0},
        'third_party': defaultdict(lambda: {'files': 0, 'total': 0, 'used': 0, 'unused': 0})
    }
    
    for entry in coverage_data:
        url = entry['url']
        text = entry.get('text', '')
        ranges = entry.get('ranges', [])
        
        # Calculate bytes
        total_bytes = len(text.encode('utf-8'))
        used_bytes = sum(r['end'] - r['start'] for r in ranges)
        unused_bytes = total_bytes - used_bytes
        usage_percent = (used_bytes / total_bytes * 100) if total_bytes > 0 else 0
        
        # Update totals
        stats['total_bytes'] += total_bytes
        stats['total_used_bytes'] += used_bytes
        stats['total_unused_bytes'] += unused_bytes
        
        # Categorize by file type
        if url.endswith('.css') or 'css' in url:
            stats['css_files']['count'] += 1
            stats['css_files']['total'] += total_bytes
            stats['css_files']['used'] += used_bytes
            stats['css_files']['unused'] += unused_bytes
            file_type = 'CSS'
        elif url.endswith('.js') or 'js' in url:
            stats['js_files']['count'] += 1
            stats['js_files']['total'] += total_bytes
            stats['js_files']['used'] += used_bytes
            stats['js_files']['unused'] += unused_bytes
            file_type = 'JS'
        else:
            file_type = 'Other'
        
        # Parse URL for categorization
        parsed = urlparse(url)
        hostname = parsed.hostname or 'inline'
        path = parsed.path
        
        # Identify source category
        if 'vidieu.vn' in hostname or hostname == 'inline':
            source = 'first_party'
        else:
            source = 'third_party'
            # Track specific third-party domains
            stats['third_party'][hostname]['files'] += 1
            stats['third_party'][hostname]['total'] += total_bytes
            stats['third_party'][hostname]['used'] += used_bytes
            stats['third_party'][hostname]['unused'] += unused_bytes
        
        stats['by_source'][source]['files'] += 1
        stats['by_source'][source]['total'] += total_bytes
        stats['by_source'][source]['used'] += used_bytes
        stats['by_source'][source]['unused'] += unused_bytes
        
        # Plugin detection
        plugin_keywords = {
            'elementor': 'Elementor',
            'woocommerce': 'WooCommerce',
            'jetpack': 'Jetpack',
            'wp-rocket': 'WP Rocket',
            'yoast': 'Yoast SEO',
            'contact-form-7': 'Contact Form 7',
            'wpforms': 'WPForms',
            'wordfence': 'Wordfence',
            'akismet': 'Akismet',
            'jquery': 'jQuery',
            'bootstrap': 'Bootstrap',
            'font-awesome': 'Font Awesome',
            'swiper': 'Swiper',
            'slick': 'Slick Slider',
            'owl-carousel': 'Owl Carousel',
            'flatsome': 'Flatsome',
            'woo-variation': 'WooCommerce Variations',
            'wishlist': 'Wishlist',
            'popup': 'Popup/Modal',
            'lazyload': 'LazyLoad',
            'autoptimize': 'Autoptimize'
        }
        
        for keyword, plugin_name in plugin_keywords.items():
            if keyword in url.lower() or keyword in path.lower():
                stats['plugin_analysis'][plugin_name]['files'] += 1
                stats['plugin_analysis'][plugin_name]['total'] += total_bytes
                stats['plugin_analysis'][plugin_name]['used'] += used_bytes
                stats['plugin_analysis'][plugin_name]['unused'] += unused_bytes
                break
        
        # Theme detection
        theme_keywords = ['themes/', 'theme-', 'flatsome', 'style.css']
        if any(keyword in url.lower() for keyword in theme_keywords):
            stats['theme_analysis']['files'] += 1
            stats['theme_analysis']['total'] += total_bytes
            stats['theme_analysis']['used'] += used_bytes
            stats['theme_analysis']['unused'] += unused_bytes
        
        # Store for worst offenders analysis
        if total_bytes > 1000:  # Only consider files > 1KB
            stats['worst_offenders'].append({
                'url': url,
                'type': file_type,
                'total_bytes': total_bytes,
                'used_bytes': used_bytes,
                'unused_bytes': unused_bytes,
                'usage_percent': usage_percent,
                'source': source
            })
    
    # Sort worst offenders by usage percentage (ascending) and limit to top 20
    stats['worst_offenders'].sort(key=lambda x: x['usage_percent'])
    stats['worst_offenders'] = stats['worst_offenders'][:20]
    
    return stats

def format_bytes(bytes_value):
    """Convert bytes to human readable format"""
    for unit in ['B', 'KB', 'MB', 'GB']:
        if bytes_value < 1024.0:
            return f"{bytes_value:.2f} {unit}"
        bytes_value /= 1024.0
    return f"{bytes_value:.2f} TB"

def print_analysis(stats):
    """Print analysis results"""
    
    print("=" * 80)
    print("COVERAGE ANALYSIS REPORT")
    print("=" * 80)
    
    # Overall statistics
    total_percent = (stats['total_used_bytes'] / stats['total_bytes'] * 100) if stats['total_bytes'] > 0 else 0
    print(f"\n1. OVERALL STATISTICS:")
    print(f"   Total files analyzed: {stats['total_files']}")
    print(f"   Total size: {format_bytes(stats['total_bytes'])}")
    print(f"   Used: {format_bytes(stats['total_used_bytes'])} ({total_percent:.1f}%)")
    print(f"   Unused: {format_bytes(stats['total_unused_bytes'])} ({100-total_percent:.1f}%)")
    
    # CSS/JS breakdown
    print(f"\n2. CSS/JS BREAKDOWN:")
    
    if stats['css_files']['total'] > 0:
        css_percent = (stats['css_files']['used'] / stats['css_files']['total'] * 100)
        print(f"   CSS Files: {stats['css_files']['count']}")
        print(f"   - Total: {format_bytes(stats['css_files']['total'])}")
        print(f"   - Used: {format_bytes(stats['css_files']['used'])} ({css_percent:.1f}%)")
        print(f"   - Unused: {format_bytes(stats['css_files']['unused'])} ({100-css_percent:.1f}%)")
    
    if stats['js_files']['total'] > 0:
        js_percent = (stats['js_files']['used'] / stats['js_files']['total'] * 100)
        print(f"   \n   JS Files: {stats['js_files']['count']}")
        print(f"   - Total: {format_bytes(stats['js_files']['total'])}")
        print(f"   - Used: {format_bytes(stats['js_files']['used'])} ({js_percent:.1f}%)")
        print(f"   - Unused: {format_bytes(stats['js_files']['unused'])} ({100-js_percent:.1f}%)")
    
    # Worst offenders
    print(f"\n3. TOP 10 WORST OFFENDERS (Lowest Usage %):")
    for i, file in enumerate(stats['worst_offenders'][:10], 1):
        filename = os.path.basename(urlparse(file['url']).path) or file['url'][-50:]
        print(f"   {i}. {filename}")
        print(f"      Type: {file['type']} | Source: {file['source']}")
        print(f"      Usage: {file['usage_percent']:.1f}% | Wasted: {format_bytes(file['unused_bytes'])}")
        print(f"      URL: {file['url'][:100]}{'...' if len(file['url']) > 100 else ''}")
        print()
    
    # Plugin analysis
    print(f"\n4. PLUGIN-SPECIFIC ANALYSIS:")
    plugin_items = sorted(stats['plugin_analysis'].items(), 
                         key=lambda x: x[1]['unused'], reverse=True)
    
    for plugin, data in plugin_items[:10]:
        if data['total'] > 0:
            usage_percent = (data['used'] / data['total'] * 100)
            print(f"   {plugin}:")
            print(f"   - Files: {data['files']}")
            print(f"   - Total: {format_bytes(data['total'])}")
            print(f"   - Used: {format_bytes(data['used'])} ({usage_percent:.1f}%)")
            print(f"   - Wasted: {format_bytes(data['unused'])}")
            print()
    
    # Theme analysis
    if stats['theme_analysis']['total'] > 0:
        print(f"\n5. THEME-RELATED CODE:")
        theme_percent = (stats['theme_analysis']['used'] / stats['theme_analysis']['total'] * 100)
        print(f"   Files: {stats['theme_analysis']['files']}")
        print(f"   Total: {format_bytes(stats['theme_analysis']['total'])}")
        print(f"   Used: {format_bytes(stats['theme_analysis']['used'])} ({theme_percent:.1f}%)")
        print(f"   Wasted: {format_bytes(stats['theme_analysis']['unused'])}")
    
    # Third-party analysis
    print(f"\n6. THIRD-PARTY LIBRARIES:")
    third_party_items = sorted(stats['third_party'].items(), 
                              key=lambda x: x[1]['unused'], reverse=True)
    
    for domain, data in third_party_items[:10]:
        if data['total'] > 0:
            usage_percent = (data['used'] / data['total'] * 100)
            print(f"   {domain}:")
            print(f"   - Files: {data['files']}")
            print(f"   - Total: {format_bytes(data['total'])}")
            print(f"   - Used: {format_bytes(data['used'])} ({usage_percent:.1f}%)")
            print(f"   - Wasted: {format_bytes(data['unused'])}")
            print()
    
    # Optimization recommendations
    print(f"\n7. OPTIMIZATION OPPORTUNITIES:")
    print(f"   Total potential savings: {format_bytes(stats['total_unused_bytes'])}")
    
    # Calculate specific recommendations
    recommendations = []
    
    # Check for large unused plugin code
    for plugin, data in stats['plugin_analysis'].items():
        if data['unused'] > 50000 and data['total'] > 0:  # > 50KB unused
            usage = (data['used'] / data['total'] * 100)
            if usage < 30:  # Less than 30% used
                recommendations.append(
                    f"   - {plugin}: Only {usage:.1f}% used. Consider removing or lazy-loading. "
                    f"Potential savings: {format_bytes(data['unused'])}"
                )
    
    # Check theme optimization
    if stats['theme_analysis']['total'] > 0:
        theme_usage = (stats['theme_analysis']['used'] / stats['theme_analysis']['total'] * 100)
        if theme_usage < 50:
            recommendations.append(
                f"   - Theme CSS/JS: Only {theme_usage:.1f}% used. "
                f"Consider splitting theme assets. Potential savings: {format_bytes(stats['theme_analysis']['unused'])}"
            )
    
    # Check third-party libraries
    for domain, data in stats['third_party'].items():
        if data['unused'] > 100000 and data['total'] > 0:  # > 100KB unused
            usage = (data['used'] / data['total'] * 100)
            if usage < 20:  # Less than 20% used
                recommendations.append(
                    f"   - {domain}: Only {usage:.1f}% used. "
                    f"Consider using CDN with specific modules or tree-shaking. "
                    f"Potential savings: {format_bytes(data['unused'])}"
                )
    
    # Print recommendations
    print("\n   Recommendations for code splitting/lazy loading:")
    for rec in recommendations[:10]:
        print(rec)
    
    # Source breakdown
    print(f"\n8. SOURCE BREAKDOWN:")
    for source, data in stats['by_source'].items():
        if data['total'] > 0:
            usage_percent = (data['used'] / data['total'] * 100)
            print(f"   {source.replace('_', ' ').title()}:")
            print(f"   - Files: {data['files']}")
            print(f"   - Total: {format_bytes(data['total'])}")
            print(f"   - Used: {format_bytes(data['used'])} ({usage_percent:.1f}%)")
            print(f"   - Wasted: {format_bytes(data['unused'])}")

if __name__ == "__main__":
    coverage_file = "/mnt/f/Obsidian/Dữ Liệu Của Tôi/Quản Lý Tài Chính/Quản Lý Kinh Doanh bán Tool/Vidieu.vn/public_html/Audit/Coverage-20250903T215159.json"
    
    print(f"Analyzing coverage report: {coverage_file}")
    print(f"File size: {format_bytes(os.path.getsize(coverage_file))}")
    print()
    
    try:
        stats = analyze_coverage(coverage_file)
        print_analysis(stats)
    except Exception as e:
        print(f"Error analyzing coverage report: {e}")
        import traceback
        traceback.print_exc()