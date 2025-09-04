# Page Sidebar Mappings

## Overview

The Page Sidebar Mappings feature allows you to apply homepage-like layouts with sidebars to any WordPress page. This enables you to create landing pages with category navigation and product/post grids similar to the homepage.

## Features

- Map any WordPress page to display a sidebar layout
- Choose from multiple sidebar types:
  - Product Categories Tree
  - Post Categories Tree
  - Homepage Preset (both products and posts)
  - Custom Taxonomy Tree
- Automatic product/post grid display based on sidebar type
- AJAX-powered category filtering
- Responsive design with sticky sidebar

## Admin Interface

### Accessing the Settings

Navigate to **Settings > VD Page Mappings** in the WordPress admin.

### Adding a Mapping

1. Select a page from the dropdown
2. Choose a sidebar type:
   - **Product Categories Tree**: Shows WooCommerce product categories
   - **Post Categories Tree**: Shows blog post categories
   - **Homepage Preset**: Shows both products and posts sections
   - **Custom Taxonomy**: Shows any custom taxonomy (requires additional selection)
3. Click "Save Mapping"

### Managing Mappings

- View all current mappings in the table
- Delete mappings using the "Delete" button
- Each page can only have one mapping

## Frontend Behavior

When a page has a mapping configured:

1. The page displays with a sidebar on the left
2. The sidebar shows the configured category tree
3. The main content area shows:
   - The page's original content (if any)
   - A product/post grid below the content
4. Clicking categories in the sidebar filters the grid via AJAX

## Technical Implementation

### Database Structure

Creates a custom table `wp_vd_page_sidebar_mappings`:
- `id`: Primary key
- `page_id`: WordPress page ID
- `sidebar_type`: Type of sidebar to display
- `sidebar_config`: Additional configuration (e.g., taxonomy name)
- `created_at`, `updated_at`: Timestamps

### Hooks Used

- `the_content`: Injects the sidebar layout
- `body_class`: Adds CSS classes for styling
- `template_redirect`: Detects mapped pages
- `wp_enqueue_scripts`: Loads necessary assets

### CSS Classes

- `.vd-has-sidebar-mapping`: Added to body tag
- `.vd-sidebar-{type}`: Specific sidebar type class
- `.vd-layout-wrapper`: Main layout container
- `.vd-sidebar.vd-sticky`: Sidebar container
- `.vd-main`: Main content area

## Styling

The feature reuses existing homepage styles from `vidieu-home.css`. Additional styles are injected inline for page-specific adjustments.

## JavaScript Functionality

Uses the existing `vidieu-home.js` for:
- Category menu interactions
- AJAX filtering
- Sticky sidebar behavior
- Mobile menu toggle

## Performance Considerations

- Database queries are optimized with proper indexes
- Assets are only loaded on mapped pages
- AJAX requests use existing optimized handlers
- No impact on non-mapped pages

## Troubleshooting

### Mapping not showing
- Ensure the page is published
- Clear any caching plugins
- Check JavaScript console for errors

### Categories not filtering
- Verify AJAX handlers are working
- Check that products/posts exist in categories
- Ensure JavaScript is not blocked

### Layout issues
- Check for theme conflicts
- Verify CSS is loading correctly
- Test in different browsers

## Future Enhancements

Potential improvements:
- Custom sidebar templates
- Per-mapping configuration options
- Import/export mappings
- Shortcode support for manual placement