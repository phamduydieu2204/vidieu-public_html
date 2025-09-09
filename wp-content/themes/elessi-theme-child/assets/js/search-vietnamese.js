/**
 * Vietnamese Search Customization
 * Changes search suggestions to Vietnamese
 */
document.addEventListener('DOMContentLoaded', function() {
    // Update search input suggestions
    function updateSearchSuggestions() {
        const searchInputs = document.querySelectorAll('.search-field, .search-input');
        
        searchInputs.forEach(function(input) {
            if (input.getAttribute('data-suggestions')) {
                // Use localized string if available, otherwise fallback
                const suggestions = (typeof elessi_vietnamese !== 'undefined' && elessi_vietnamese.search_suggestions) 
                    ? elessi_vietnamese.search_suggestions 
                    : 'Áo thun, Áo khoác, Quần jean...';
                    
                input.setAttribute('data-suggestions', suggestions);
            }
        });
    }
    
    // Run on page load
    updateSearchSuggestions();
    
    // Run again after Ajax updates (for dynamic content)
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ajaxComplete(function() {
            setTimeout(updateSearchSuggestions, 100);
        });
    }
});