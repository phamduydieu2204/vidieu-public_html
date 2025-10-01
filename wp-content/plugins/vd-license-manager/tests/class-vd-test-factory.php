<?php
/**
 * VD License Manager Test Factory
 *
 * Factory class for creating test data and mock objects
 * Extends WordPress test factory for license-specific data generation
 *
 * @since 1.5.0-rc.2
 * @package VD_License_Manager
 */

/**
 * Test factory for VD License Manager
 */
class VD_Test_Factory extends WP_UnitTest_Factory {

    /**
     * License factory
     *
     * @var VD_Test_License_Factory
     */
    public $license;

    /**
     * Product factory
     *
     * @var VD_Test_Product_Factory
     */
    public $product;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        $this->license = new VD_Test_License_Factory($this);
        $this->product = new VD_Test_Product_Factory($this);
    }
}

/**
 * License factory for creating test licenses
 */
class VD_Test_License_Factory extends WP_UnitTest_Factory_For_Thing {

    /**
     * Generate license data
     *
     * @param array $args License arguments
     * @return array License data
     */
    public function generate($args = []) {
        $defaults = [
            'license_key' => $this->generate_license_key(),
            'status' => 'active',
            'product_id' => 1,
            'user_id' => 1,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'activations_limit' => 5,
            'times_activated' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];

        return array_merge($defaults, $args);
    }

    /**
     * Create license in database
     *
     * @param array $args License arguments
     * @return int License ID
     */
    public function create_object($args) {
        global $wpdb;

        $license_data = $this->generate($args);

        $wpdb->insert($wpdb->prefix . 'vd_test_licenses', $license_data);

        return $wpdb->insert_id;
    }

    /**
     * Update license in database
     *
     * @param int $license_id License ID
     * @param array $fields Fields to update
     * @return bool Update result
     */
    public function update_object($license_id, $fields) {
        global $wpdb;

        return $wpdb->update(
            $wpdb->prefix . 'vd_test_licenses',
            $fields,
            ['id' => $license_id]
        );
    }

    /**
     * Get license from database
     *
     * @param int $license_id License ID
     * @return array|null License data
     */
    public function get_object_by_id($license_id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}vd_test_licenses WHERE id = %d", $license_id),
            ARRAY_A
        );
    }

    /**
     * Generate unique license key
     *
     * @param string $prefix Key prefix
     * @return string License key
     */
    public function generate_license_key($prefix = 'TEST') {
        return sprintf(
            '%s-%s-%s-%s',
            $prefix,
            strtoupper(substr(md5(uniqid()), 0, 4)),
            strtoupper(substr(md5(uniqid()), 0, 4)),
            strtoupper(substr(md5(uniqid()), 0, 4))
        );
    }

    /**
     * Create expired license
     *
     * @param array $args Additional arguments
     * @return int License ID
     */
    public function create_expired($args = []) {
        $defaults = [
            'status' => 'active',
            'expires_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ];

        return $this->create(array_merge($defaults, $args));
    }

    /**
     * Create license batch
     *
     * @param int $count Number of licenses
     * @param array $args Common arguments
     * @return array License IDs
     */
    public function create_batch($count = 5, $args = []) {
        $license_ids = [];

        for ($i = 0; $i < $count; $i++) {
            $license_args = array_merge($args, [
                'license_key' => $this->generate_license_key('BATCH' . $i)
            ]);
            $license_ids[] = $this->create($license_args);
        }

        return $license_ids;
    }

    /**
     * Create license with specific status
     *
     * @param string $status License status
     * @param array $args Additional arguments
     * @return int License ID
     */
    public function create_with_status($status, $args = []) {
        $args['status'] = $status;

        // Adjust expiry based on status
        switch ($status) {
            case 'expired':
                $args['expires_at'] = date('Y-m-d H:i:s', strtotime('-1 day'));
                break;
            case 'active':
                $args['expires_at'] = date('Y-m-d H:i:s', strtotime('+1 year'));
                break;
            case 'inactive':
                $args['expires_at'] = date('Y-m-d H:i:s', strtotime('+1 year'));
                break;
        }

        return $this->create($args);
    }

    /**
     * Create license for testing constraints
     *
     * @param array $constraints Constraint configuration
     * @param array $args Additional arguments
     * @return int License ID
     */
    public function create_with_constraints($constraints = [], $args = []) {
        $license_id = $this->create($args);

        // Add constraint metadata
        if (!empty($constraints)) {
            update_post_meta($license_id, 'vd_license_constraints', $constraints);
        }

        return $license_id;
    }
}

/**
 * Product factory for creating test products
 */
class VD_Test_Product_Factory extends WP_UnitTest_Factory_For_Post {

    /**
     * Constructor
     */
    public function __construct($factory = null) {
        parent::__construct($factory);

        $this->default_generation_definitions = array_merge(
            $this->default_generation_definitions,
            [
                'post_type' => 'product',
                'post_status' => 'publish',
                'post_title' => new WP_UnitTest_Generator_Sequence('Test Product %s'),
                'post_content' => 'Test product description'
            ]
        );
    }

    /**
     * Create product with license settings
     *
     * @param array $license_settings License configuration
     * @param array $args Product arguments
     * @return int Product ID
     */
    public function create_with_license_settings($license_settings = [], $args = []) {
        $product_id = $this->create($args);

        // Default license settings
        $default_settings = [
            'license_enabled' => true,
            'activations_limit' => 5,
            'license_duration' => '1 year',
            'license_type' => 'standard'
        ];

        $settings = array_merge($default_settings, $license_settings);

        // Add license metadata
        foreach ($settings as $key => $value) {
            update_post_meta($product_id, '_vd_license_' . $key, $value);
        }

        return $product_id;
    }

    /**
     * Create WooCommerce product with license features
     *
     * @param array $args Product arguments
     * @return int Product ID
     */
    public function create_wc_product($args = []) {
        $defaults = [
            'post_type' => 'product',
            'meta_input' => [
                '_regular_price' => '29.99',
                '_price' => '29.99',
                '_manage_stock' => 'no',
                '_stock_status' => 'instock',
                '_visibility' => 'visible',
                '_vd_license_enabled' => 'yes'
            ]
        ];

        return $this->create(array_merge($defaults, $args));
    }
}