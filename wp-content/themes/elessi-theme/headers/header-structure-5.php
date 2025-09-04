<?php defined('ABSPATH') or die(); // Exit if accessed directly ?>

<div class="<?php echo esc_attr($header_classes); ?>">
    <div class="sticky-wrapper">
        <div id="masthead" class="site-header">
            <?php do_action('nasa_mobile_header'); ?>
            
            <div class="row nasa-hide-for-mobile">
                <div class="large-12 columns nasa-wrap-event-search">
                    <div class="nasa-header-flex nasa-elements-wrap">
                        <!-- Group icon header -->
                        <div class="nasa-flex-item-1-3 first-columns nasa-flex">
                            <a class="nasa-menu-off nasa-icon margin-right-10 rtl-margin-right-0 rtl-margin-left-10 nasa-flex" href="javascript:void(0);" rel="nofollow"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="28" height="28" fill="currentColor"><path d="M 4 7 L 4 9 L 28 9 L 28 7 Z M 4 15 L 4 17 L 28 17 L 28 15 Z M 4 23 L 4 25 L 28 25 L 28 23 Z"></path></svg></a>
                            <a class="search-icon desk-search nasa-icon nasa-search icon-nasa-if-search nasa-flex fs-26" href="javascript:void(0);" data-open="0" title="Search" rel="nofollow"></a>
                        </div>

                        <!-- Logo -->
                        <div class="nasa-flex-item-1-3 text-center">
                            <?php echo elessi_logo(); ?>
                        </div>

                        <!-- Group icon header -->
                        <div class="nasa-flex-item-1-3">
                            <?php echo $nasa_header_icons; ?>
                        </div>
                    </div>
                    
                    <div class="nasa-header-search-wrap">
                        <?php echo elessi_search('icon'); ?>
                    </div>
                </div>
            </div>
            
            <!-- Main menu -->
            <div class="nasa-off-canvas hidden-tag">
                <?php elessi_get_main_menu(); ?>
                <?php do_action('nasa_multi_lc'); ?>
            </div>
            
            <?php if (defined('NASA_TOP_FILTER_CATS') && NASA_TOP_FILTER_CATS) : ?>
                <div class="nasa-top-cat-filter-wrap">
                    <?php echo elessi_get_all_categories(false, true); ?>
                    <a href="javascript:void(0);" title="<?php esc_attr_e('Close', 'elessi-theme'); ?>" class="nasa-close-filter-cat nasa-stclose nasa-transition" rel="nofollow"></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
