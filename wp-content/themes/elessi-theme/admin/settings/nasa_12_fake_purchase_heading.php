<?php
add_action('init', 'elessi_fake_purchase_heading');
if (!function_exists('elessi_fake_purchase_heading')) {
    function elessi_fake_purchase_heading() {
        // Set the Options Array
        global $of_options;
        if (empty($of_options)) {
            $of_options = array();
        }
        
        /* Fake Purchased */
        $of_options[] = array(
            "name" => esc_html__("Fake Purchased", 'elessi-theme'),
            "target" => "fake-purchase",
            "type" => "heading"
        );

        $of_options[] = array(
            "name" => esc_html__("Enable", 'nasa-core'),
            "id" => "fake_purchase",
            "std" => 0,
            "type" => "switch"
        );

        $of_options[] = array(
            "name" => esc_html__("Products Fake Purchase", 'elessi-theme'),
            "id" => "fake_purchase_ct",
            "std" => '',
            "type" => "fake_purchases"
        );
    }
}
