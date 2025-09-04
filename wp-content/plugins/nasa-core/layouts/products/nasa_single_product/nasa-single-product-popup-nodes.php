<?php
$content = '';

/**
 * Request a Call Back
 */
if ($request_a_callback) :
    $product_image = isset($product_image) ? $product_image : $single_product->get_image('thumbnail');
    $product_title = isset($product_title) ? $product_title : $single_product->get_name();
    $product_link = isset($product_link) ? $product_link : $single_product->get_permalink();
    $product_price = isset($product_price) ? $product_price : $single_product->get_price_html();
    
    $content .= '<li class="nasa-popup-node-item hidden-tag nasa-request-a-callback">';
    
    /**
     * Content Popup
     */
    $content .= '<div id="nasa-content-request-a-callback" class="zoom-anim-dialog nasa-node-content nasa-popup-content-contact hidden-tag">';
    
        /**
         * Product Info
         */
        $content .= '<div class="nasa-flex flex-column nasa-product">';

            $content .= '<div class="nasa-product-img">' . $product_image . '</div>';

            $content .= '<div class="nasa-product-info text-center">';
                /**
                 * Product Name
                 */
                $content .= '<p class="name">' . $product_title . '</p>';

                /**
                 * Product Price
                 */
                if ($product_price) :
                    $content .= '<div class="price">' . $product_price . '</div>';
                endif;
                
                $content .= '<div class="hidden-tag nasa-info-add-form">';
                $content .= '<input type="hidden" name="product-name" value="' . esc_attr($product_title) . '" />';
                $content .= '<input type="hidden" name="product-url" value="' . esc_url($product_link) . '" />';
                $content .= '</div>';
            $content .= '</div>';

        $content .= '</div>';
    
        /**
         * Contact form 7
         */
        $content .= '<div class="nasa-wrap">';

            $content .= '<h3 class="nasa-headling-popup text-center nasa-bold-800 fs-28 mobile-fs-25">';
                $content .= esc_html__('Request a Call Back', 'nasa-core');
            $content .= '</h3>';

            $content .= $request_a_callback;

        $content .= '</div>';
    
    $content .= '</div>';
    
    $content .= '</li>';
endif;

/**
 * Size Guide Block
 */
if ($size_guide) :
    $content .= '<li class="nasa-popup-node-item nasa-size-guide first">';
    
    /**
     * Dom click
     */
    $content .= '<a class="nasa-node-popup" href="javascript:void(0);" data-target="#nasa-content-size-guide" rel="nofollow"><i class="nasa-icon pe-7s-note2 pe-icon"></i>&nbsp;' . esc_html__('Size Guide', 'nasa-core') . '</a>';
    
    /**
     * Content Popup
     */
    $content .= '<div id="nasa-content-size-guide" class="zoom-anim-dialog nasa-node-content hidden-tag">' . $size_guide . '</div>';
    
    $content .= '</li>';
endif;

/**
 * Delivery & Return
 */
if ($delivery_return) :
    $content .= '<li class="nasa-popup-node-item nasa-delivery-return">';
    
    /**
     * Dom click
     */
    $content .= '<a class="nasa-node-popup" href="javascript:void(0);" data-target="#nasa-content-delivery-return" rel="nofollow"><i class="nasa-icon pe-7s-next-2 pe-icon"></i>&nbsp;' . esc_html__('Delivery &#38; Return', 'nasa-core') . '</a>';
    
    /**
     * Content Popup
     */
    $content .= '<div id="nasa-content-delivery-return" class="zoom-anim-dialog nasa-node-content hidden-tag">' . $delivery_return . '</div>';
    
    $content .= '</li>';
endif;

/**
 * Ask a Question
 */
if ($ask_a_question) :
    $product_image = isset($product_image) ? $product_image : $single_product->get_image('thumbnail');
    $product_title = isset($product_title) ? $product_title : $single_product->get_name();
    $product_link = isset($product_link) ? $product_link : $single_product->get_permalink();
    $product_price = isset($product_price) ? $product_price : $single_product->get_price_html();
    
    $content .= '<li class="nasa-popup-node-item last nasa-ask-a-quetion">';
    
    /**
     * Dom click
     */
    $content .= '<a class="nasa-node-popup" href="javascript:void(0);" data-target="#nasa-content-ask-a-quetion" rel="nofollow"><i class="nasa-icon pe-7s-help1 pe-icon"></i>&nbsp;' . esc_html__('Ask a Question', 'nasa-core') . '</a>';
    
    /**
     * Content Popup
     */
    $content .= '<div id="nasa-content-ask-a-quetion" class="zoom-anim-dialog nasa-node-content nasa-popup-content-contact hidden-tag">';
    
        /**
         * Product Info
         */
        $content .= '<div class="nasa-flex flex-column nasa-product">';

            $content .= '<div class="nasa-product-img">' . $product_image . '</div>';

            $content .= '<div class="nasa-product-info text-center">';
                
                /**
                 * Product Name
                 */
                $content .= '<p class="name">' . $product_title . '</p>';

                /**
                 * Product Price
                 */
                if ($product_price) :
                    $content .= '<div class="price">' . $product_price . '</div>';
                endif;
                
                $content .= '<div class="hidden-tag nasa-info-add-form">';
                $content .= '<input type="hidden" name="product-name" value="' . esc_attr($product_title) . '" />';
                $content .= '<input type="hidden" name="product-url" value="' . esc_url($product_link) . '" />';
                $content .= '</div>';
            $content .= '</div>';

        $content .= '</div>';
    
        /**
         * Contact form 7
         */
        $content .= '<div class="nasa-wrap">';

            $content .= '<h3 class="nasa-headling-popup text-center nasa-bold-800 fs-28 mobile-fs-25">';
                $content .= esc_html__('Ask a Question', 'nasa-core');
            $content .= '</h3>';

            $content .= $ask_a_question;

        $content .= '</div>';
    
    $content .= '</div>';
    
    $content .= '</li>';
endif;

/**
 * Output
 */
$output = apply_filters('nasa_single_product_popup_nodes', $content);

/**
 * Echo Content
 */
if ($output) :
    echo '<ul class="nasa-wrap-popup-nodes">';
    echo $output;
    echo '</ul>';
endif;
