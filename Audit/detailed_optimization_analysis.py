#!/usr/bin/env python3
import json
from urllib.parse import urlparse
from collections import defaultdict

def get_detailed_optimization_report(filepath):
    """Generate detailed optimization recommendations"""
    
    with open(filepath, 'r', encoding='utf-8') as f:
        coverage_data = json.load(f)
    
    # Categorize files for detailed analysis
    categories = {
        'completely_unused': [],  # 0% usage
        'mostly_unused': [],      # < 10% usage
        'low_usage': [],          # 10-30% usage
        'deferred_loading': [],   # Good candidates for lazy loading
        'bundle_splitting': [],   # Large files that could be split
        'remove_candidates': [],  # Safe to remove
        'inline_candidates': []   # Small critical CSS/JS to inline
    }
    
    # Detailed plugin tracking
    plugin_details = defaultdict(list)
    
    for entry in coverage_data:
        url = entry['url']
        text = entry.get('text', '')
        ranges = entry.get('ranges', [])
        
        total_bytes = len(text.encode('utf-8'))
        used_bytes = sum(r['end'] - r['start'] for r in ranges)
        unused_bytes = total_bytes - used_bytes
        usage_percent = (used_bytes / total_bytes * 100) if total_bytes > 0 else 0
        
        # Skip small files
        if total_bytes < 500:
            continue
        
        parsed = urlparse(url)
        filename = parsed.path.split('/')[-1] if parsed.path else url
        
        file_info = {
            'url': url,
            'filename': filename,
            'total_bytes': total_bytes,
            'used_bytes': used_bytes,
            'unused_bytes': unused_bytes,
            'usage_percent': usage_percent,
            'path': parsed.path
        }
        
        # Categorize by usage
        if usage_percent == 0:
            categories['completely_unused'].append(file_info)
            categories['remove_candidates'].append(file_info)
        elif usage_percent < 10:
            categories['mostly_unused'].append(file_info)
            if total_bytes > 10000:  # >10KB
                categories['remove_candidates'].append(file_info)
        elif usage_percent < 30:
            categories['low_usage'].append(file_info)
            if total_bytes > 50000:  # >50KB
                categories['deferred_loading'].append(file_info)
        
        # Check for bundle splitting opportunities
        if total_bytes > 100000 and usage_percent < 40:  # >100KB and <40% usage
            categories['bundle_splitting'].append(file_info)
        
        # Check for inline candidates (small, critical files)
        if total_bytes < 5000 and usage_percent > 80:  # <5KB and >80% usage
            categories['inline_candidates'].append(file_info)
        
        # Track plugin-specific files
        plugin_patterns = {
            'elementor': ['elementor', 'element-', 'pro-elements'],
            'woocommerce': ['woocommerce', 'wc-', 'cart', 'checkout', 'product'],
            'flatsome': ['flatsome', 'theme-'],
            'nasa': ['nasa-', 'elessi'],
            'revslider': ['revslider', 'rs6'],
            'contact-form': ['contact-form', 'wpcf7'],
            'fontawesome': ['fontawesome', 'font-awesome', 'fa-'],
            'bootstrap': ['bootstrap'],
            'swiper': ['swiper'],
            'jquery': ['jquery'],
            'wp-rocket': ['wp-rocket', 'rocket-'],
            'autoptimize': ['autoptimize'],
            'header-footer': ['header-footer-elementor', 'hfe-']
        }
        
        for plugin, patterns in plugin_patterns.items():
            if any(pattern in url.lower() for pattern in patterns):
                plugin_details[plugin].append(file_info)
                break
    
    return categories, plugin_details

def print_detailed_report(categories, plugin_details):
    """Print detailed optimization report"""
    
    print("=" * 80)
    print("DETAILED OPTIMIZATION REPORT")
    print("=" * 80)
    
    # 1. Files that can be completely removed
    print("\n1. FILES THAT CAN BE SAFELY REMOVED (0% or very low usage):")
    print("-" * 60)
    remove_total = 0
    for file in sorted(categories['remove_candidates'], key=lambda x: x['unused_bytes'], reverse=True)[:15]:
        print(f"   • {file['filename']}")
        print(f"     Usage: {file['usage_percent']:.1f}% | Size: {file['unused_bytes']/1024:.1f}KB")
        print(f"     Path: {file['path']}")
        remove_total += file['unused_bytes']
    print(f"\n   TOTAL SAVINGS FROM REMOVAL: {remove_total/1024:.1f}KB")
    
    # 2. Defer loading candidates
    print("\n\n2. DEFER LOADING / LAZY LOAD CANDIDATES:")
    print("-" * 60)
    defer_total = 0
    for file in sorted(categories['deferred_loading'], key=lambda x: x['unused_bytes'], reverse=True)[:10]:
        print(f"   • {file['filename']}")
        print(f"     Usage: {file['usage_percent']:.1f}% | Unused: {file['unused_bytes']/1024:.1f}KB")
        print(f"     Strategy: Load on interaction/scroll/specific page")
        defer_total += file['unused_bytes']
    print(f"\n   POTENTIAL SAVINGS FROM DEFERRED LOADING: {defer_total/1024:.1f}KB")
    
    # 3. Bundle splitting opportunities
    print("\n\n3. BUNDLE SPLITTING OPPORTUNITIES (Large files with low usage):")
    print("-" * 60)
    split_total = 0
    for file in sorted(categories['bundle_splitting'], key=lambda x: x['total_bytes'], reverse=True)[:10]:
        print(f"   • {file['filename']}")
        print(f"     Total: {file['total_bytes']/1024:.1f}KB | Used: {file['usage_percent']:.1f}%")
        print(f"     Recommendation: Split into core + feature-specific bundles")
        split_total += file['unused_bytes']
    print(f"\n   POTENTIAL SAVINGS FROM SPLITTING: {split_total/1024:.1f}KB")
    
    # 4. Plugin-specific recommendations
    print("\n\n4. PLUGIN-SPECIFIC OPTIMIZATION STRATEGIES:")
    print("-" * 60)
    
    plugin_recommendations = {
        'elementor': {
            'name': 'Elementor Page Builder',
            'strategies': [
                "Disable unused widgets in Elementor settings",
                "Use Elementor's 'Optimized Asset Loading' feature",
                "Load Elementor assets only on pages using the builder",
                "Consider using Elementor Hello theme for lighter base"
            ]
        },
        'woocommerce': {
            'name': 'WooCommerce',
            'strategies': [
                "Disable WooCommerce scripts on non-shop pages",
                "Use conditional loading for cart/checkout scripts",
                "Remove unused payment gateway scripts",
                "Disable WooCommerce blocks if using classic editor"
            ]
        },
        'flatsome': {
            'name': 'Flatsome Theme',
            'strategies': [
                "Enable Flatsome's lazy loading options",
                "Disable unused theme features in Theme Options",
                "Use child theme to override and remove unused components",
                "Minify custom CSS in Theme Options > Custom CSS"
            ]
        },
        'revslider': {
            'name': 'Revolution Slider',
            'strategies': [
                "Load slider assets only on pages with sliders",
                "Use lazy loading for slider images",
                "Disable unused slider effects and transitions",
                "Consider lighter alternatives like Swiper.js"
            ]
        },
        'jquery': {
            'name': 'jQuery Library',
            'strategies': [
                "Migrate to vanilla JavaScript where possible",
                "Use jQuery slim build if full features not needed",
                "Defer jQuery loading if not critical",
                "Check for duplicate jQuery versions"
            ]
        }
    }
    
    for plugin, files in plugin_details.items():
        if plugin in plugin_recommendations and files:
            total_unused = sum(f['unused_bytes'] for f in files)
            avg_usage = sum(f['usage_percent'] for f in files) / len(files) if files else 0
            
            print(f"\n   {plugin_recommendations[plugin]['name']}:")
            print(f"   Files: {len(files)} | Avg Usage: {avg_usage:.1f}% | Wasted: {total_unused/1024:.1f}KB")
            print("   Optimization strategies:")
            for strategy in plugin_recommendations[plugin]['strategies']:
                print(f"   - {strategy}")
    
    # 5. Inline candidates
    print("\n\n5. INLINE CANDIDATES (Small, high-usage files):")
    print("-" * 60)
    if categories['inline_candidates']:
        for file in sorted(categories['inline_candidates'], key=lambda x: x['total_bytes'])[:5]:
            print(f"   • {file['filename']}")
            print(f"     Size: {file['total_bytes']/1024:.1f}KB | Usage: {file['usage_percent']:.1f}%")
            print(f"     Benefit: Reduce HTTP requests for critical resources")
    else:
        print("   No suitable inline candidates found.")
    
    # 6. Code splitting implementation guide
    print("\n\n6. IMPLEMENTATION GUIDE FOR CODE SPLITTING:")
    print("-" * 60)
    print("   A. WordPress/PHP Implementation:")
    print("      - Use wp_enqueue_script() with conditional logic")
    print("      - Example: if (is_page('contact')) { wp_enqueue_script('contact-form'); }")
    print("      - Use 'wp_dequeue_script' to remove unnecessary scripts")
    print()
    print("   B. Use Asset CleanUp or Perfmatters Plugin:")
    print("      - Visual interface for managing script/style loading")
    print("      - Per-page asset management")
    print("      - Regex-based URL matching for conditional loading")
    print()
    print("   C. Manual Optimization:")
    print("      - Create custom loading logic in functions.php")
    print("      - Use Intersection Observer for lazy loading")
    print("      - Implement dynamic imports for JavaScript modules")
    
    # 7. Priority action items
    print("\n\n7. PRIORITY ACTION ITEMS:")
    print("-" * 60)
    print("   1. Remove completely unused CSS files (0% usage)")
    print("   2. Implement lazy loading for Elementor widgets")
    print("   3. Defer non-critical JavaScript (jQuery, theme scripts)")
    print("   4. Split WooCommerce assets - load only on shop pages")
    print("   5. Optimize font loading - subset fonts, use font-display: swap")
    print("   6. Remove duplicate/redundant CSS rules")
    print("   7. Use critical CSS inline + defer main stylesheet")

def format_bytes(bytes_value):
    """Convert bytes to human readable format"""
    for unit in ['B', 'KB', 'MB']:
        if bytes_value < 1024.0:
            return f"{bytes_value:.2f} {unit}"
        bytes_value /= 1024.0
    return f"{bytes_value:.2f} GB"

if __name__ == "__main__":
    coverage_file = "/mnt/f/Obsidian/Dữ Liệu Của Tôi/Quản Lý Tài Chính/Quản Lý Kinh Doanh bán Tool/Vidieu.vn/public_html/Audit/Coverage-20250903T215159.json"
    
    try:
        categories, plugin_details = get_detailed_optimization_report(coverage_file)
        print_detailed_report(categories, plugin_details)
    except Exception as e:
        print(f"Error: {e}")
        import traceback
        traceback.print_exc()