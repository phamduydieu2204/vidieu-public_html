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
                input.setAttribute('data-suggestions', 'Áo thun, Áo khoác, Quần jean...');
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