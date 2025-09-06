<?php
/**
 * Helper script to extract critical CSS
 * 
 * This script helps analyze which CSS rules are needed
 * for above-the-fold content on each route
 * 
 * Usage: php extract-helper.php
 */

echo "=== Critical CSS Extraction Helper ===\n\n";

echo "Instructions for extracting critical CSS:\n\n";

echo "1. AUTOMATED TOOLS:\n";
echo "   - Use Chrome DevTools Coverage tab to identify used CSS\n";
echo "   - Use online tools like criticalCSS.com or Penthouse\n";
echo "   - Use npm packages: critical, penthouse, or criticalcss\n\n";

echo "2. MANUAL EXTRACTION PROCESS:\n";
echo "   a) Open the target page in Chrome\n";
echo "   b) Open DevTools > Coverage tab\n";
echo "   c) Reload the page and stop recording after above-the-fold loads\n";
echo "   d) Click on CSS files to see which rules are used (green)\n";
echo "   e) Copy only the green (used) rules for above-the-fold\n\n";

echo "3. IMPORTANT SELECTORS TO INCLUDE:\n";
$critical_selectors = [
    'Layout' => ['body', 'html', '.container', '.row', '.col-*'],
    'Header' => ['header', '.header-*', '.logo', '.main-menu', 'nav'],
    'Hero/Banner' => ['.hero-*', '.banner-*', '.slider-*'],
    'Breadcrumb' => ['.breadcrumb', '.woocommerce-breadcrumb'],
    'Page Title' => ['.page-title', '.entry-title', 'h1'],
    'Product Grid' => ['.products', '.product', '.woocommerce-loop-*'],
    'Forms' => ['.form-row', 'input', 'select', 'button', '.button'],
    'Cart/Checkout' => ['.shop_table', '.cart_totals', '#order_review'],
];

foreach ($critical_selectors as $section => $selectors) {
    echo "\n   {$section}:\n";
    foreach ($selectors as $selector) {
        echo "   - {$selector}\n";
    }
}

echo "\n4. OPTIMIZATION TIPS:\n";
echo "   - Remove @font-face declarations (handle in Phase 4)\n";
echo "   - Remove :hover, :focus states (not critical)\n";
echo "   - Remove animation keyframes (defer to full CSS)\n";
echo "   - Keep only styles visible in initial viewport\n";
echo "   - Target max 10-12KB per template\n\n";

echo "5. TESTING EACH TEMPLATE:\n";
$routes = [
    'home.css' => 'https://vidieu.vn/',
    'archive-product.css' => 'https://vidieu.vn/san-pham/',
    'single-product.css' => 'https://vidieu.vn/product/[example]',
    'single-post.css' => 'https://vidieu.vn/[blog-post]',
    'page-contact.css' => 'https://vidieu.vn/contact/',
    'cart.css' => 'https://vidieu.vn/cart/',
    'checkout.css' => 'https://vidieu.vn/checkout/',
    'my-account.css' => 'https://vidieu.vn/my-account/',
];

foreach ($routes as $file => $url) {
    echo "   {$file} -> {$url}\n";
}

echo "\n6. VALIDATION CHECKLIST:\n";
echo "   [ ] No layout shift when full CSS loads\n";
echo "   [ ] All above-the-fold content styled correctly\n";
echo "   [ ] File size under 12KB\n";
echo "   [ ] No console errors\n";
echo "   [ ] Mobile view works correctly\n";
echo "   [ ] No duplicate styles causing conflicts\n\n";

echo "7. COMMON ISSUES TO AVOID:\n";
echo "   - Missing responsive breakpoints\n";
echo "   - Forgetting RTL styles if needed\n";
echo "   - Including too many below-the-fold styles\n";
echo "   - Missing critical layout/grid styles\n";
echo "   - Specificity conflicts with full CSS\n\n";

echo "Remember: Start minimal and add only what's necessary!\n";