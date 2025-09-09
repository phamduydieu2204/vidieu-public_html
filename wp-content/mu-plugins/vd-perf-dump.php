<?php
/**
 * Plugin Name: VD Perf Dump (temporary)
 * Description: Dump phpinfo, opcache, active plugins/themes, table sizes, autoload options... to wp-content/perf/inputs
 */

if (!defined('ABSPATH')) exit;

add_action('init', function () {
    // ĐỔI CHUỖI NÀY TRƯỚC KHI CHẠY
    $secret = 'SECRET_KEY_CHANGE_ME';

    // Gọi qua: https://yourdomain.com/?vd_perf_dump=run&key=SECRET_KEY
    if (isset($_GET['vd_perf_dump'], $_GET['key']) && $_GET['vd_perf_dump'] === 'run' && hash_equals($secret, $_GET['key'])) {
        $base = WP_CONTENT_DIR . '/perf/inputs';
        if (!is_dir($base)) wp_mkdir_p($base);

        // 1) phpinfo
        ob_start(); phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES); $phpinfo = ob_get_clean();
        file_put_contents($base . '/phpinfo.html', $phpinfo);

        // 2) OPcache
        if (function_exists('opcache_get_status')) {
            $status = @opcache_get_status(true);
            file_put_contents($base . '/opcache-status.json', json_encode($status, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        }
        if (function_exists('opcache_get_configuration')) {
            $cfg = @opcache_get_configuration();
            file_put_contents($base . '/opcache-config.json', json_encode($cfg, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        }

        // 3) WP info: plugins, theme
        $wp_info = [
            'siteurl' => get_option('siteurl'),
            'home'    => get_option('home'),
            'theme'   => wp_get_theme()->get('Name'),
            'template'=> get_template(),
            'stylesheet' => get_stylesheet(),
            'active_plugins' => get_option('active_plugins'),
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? '',
        ];
        file_put_contents($base . '/wp-info.json', json_encode($wp_info, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

        // 4) Autoload options top list & tổng size ước tính
        global $wpdb;
        $rows = $wpdb->get_results("
            SELECT option_name, LENGTH(option_value) AS bytes
            FROM {$wpdb->options}
            WHERE autoload='yes'
            ORDER BY bytes DESC
            LIMIT 100
        ", ARRAY_A);
        file_put_contents($base . '/top_autoload.csv', "option_name,bytes\n" . implode("\n",
            array_map(fn($r)=>$r['option_name'].','.$r['bytes'], $rows)
        ));

        $sum = $wpdb->get_var("SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload='yes'");
        file_put_contents($base . '/autoload_total_bytes.txt', (string)$sum);

        // 5) Bảng & kích thước (ước tính)
        $tables = $wpdb->get_col("SHOW TABLES");
        $sizes = [];
        foreach ($tables as $t) {
            $status = $wpdb->get_row("SHOW TABLE STATUS LIKE '{$t}'", ARRAY_A);
            if ($status){
                $sizes[] = [
                    'table' => $t,
                    'rows' => $status['Rows'] ?? 0,
                    'data_length' => $status['Data_length'] ?? 0,
                    'index_length'=> $status['Index_length'] ?? 0,
                    'engine' => $status['Engine'] ?? '',
                ];
            }
        }
        file_put_contents($base . '/db_table_sizes.json', json_encode($sizes, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

        // 6) Environment cơ bản
        $env = [
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ];
        file_put_contents($base . '/php-ini.json', json_encode($env, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

        wp_die('VD Perf Dump: done. Files saved to wp-content/perf/inputs');
    }
});
