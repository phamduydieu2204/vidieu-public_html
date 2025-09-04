<?php
/**
 * Posts grid template - New Design with 5:6 Aspect Ratio
 *
 * @package VidieuHomeSections
 * @var WP_Query $posts
 * @var array $args
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Default values
$defaults = array(
    'columns' => 3,
    'columns_desktop' => 3,
    'columns_tablet' => 2,
    'columns_mobile' => 1,
    'show_author' => true,
    'show_category' => true,
    'paged' => 1
);
$args = wp_parse_args($args, $defaults);

// Get primary column count for CSS class
$columns = $args['columns_desktop'] ?? $args['columns'];

// Posts are now direct children of the outer .vd-posts-grid
// No wrapper div needed to prevent nested grid structure
?>

<?php if ($posts->have_posts()) : ?>
        <?php while ($posts->have_posts()) : $posts->the_post(); ?>
            <article class="vd-post-card">
                <!-- Post Thumbnail with 5:6 Aspect Ratio -->
                <?php if (has_post_thumbnail()) : ?>
                    <div class="vd-post-thumb">
                        <a href="<?php echo esc_url(get_permalink()); ?>" class="vd-post-link">
                            <?php
                            the_post_thumbnail('medium_large', array(
                                'alt' => esc_attr(get_the_title()),
                                'loading' => 'lazy'
                            ));
                            ?>
                        </a>
                    </div>
                <?php else : ?>
                    <div class="vd-post-thumb no-image">
                        <a href="<?php echo esc_url(get_permalink()); ?>" class="vd-post-link">
                            <!-- Icon will be added via CSS ::before -->
                        </a>
                    </div>
                <?php endif; ?>
                
                <!-- Post Body -->
                <div class="vd-post-body">
                    <!-- Post Meta -->
                    <div class="vd-post-meta">
                        <span class="vd-post-date">
                            <?php echo esc_html(get_the_date('M j, Y')); ?>
                        </span>
                        
                        <?php if ($args['show_category']) : ?>
                            <?php
                            $categories = get_the_category();
                            if (!empty($categories)) :
                                $primary_category = $categories[0]; // Get first category
                            ?>
                                <span class="vd-post-categories">
                                    <a href="<?php echo esc_url(get_category_link($primary_category->term_id)); ?>">
                                        <?php echo esc_html($primary_category->name); ?>
                                    </a>
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Post Title with Line Clamp (max 3 lines) -->
                    <h3 class="vd-post-title">
                        <a href="<?php echo esc_url(get_permalink()); ?>">
                            <?php echo esc_html(get_the_title()); ?>
                        </a>
                    </h3>
                    
                    <!-- Post Excerpt with Line Clamp (max 2 lines) -->
                    <div class="vd-post-excerpt">
                        <?php 
                        $excerpt = get_the_excerpt();
                        if (empty($excerpt)) {
                            $excerpt = wp_trim_words(get_the_content(), 25, '...');
                        }
                        echo wp_kses_post(wp_trim_words($excerpt, 20, '...'));
                        ?>
                    </div>
                    
                    <!-- Post Footer -->
                    <div class="vd-post-footer">
                        <a href="<?php echo esc_url(get_permalink()); ?>" class="vd-read-more">
                            <?php esc_html_e('Read More', VD_HOME_TEXT_DOMAIN); ?>
                        </a>
                        
                        <?php if ($args['show_author']) : ?>
                            <div class="vd-post-author">
                                <?php esc_html_e('by', VD_HOME_TEXT_DOMAIN); ?>
                                <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>">
                                    <?php echo esc_html(get_the_author()); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endwhile; ?>
        
        
    <?php else : ?>
        <!-- No Results -->
        <div class="vd-no-results">
            <p><?php esc_html_e('No posts found in this category.', VD_HOME_TEXT_DOMAIN); ?></p>
        </div>
    <?php endif; ?>

<?php wp_reset_postdata(); ?>