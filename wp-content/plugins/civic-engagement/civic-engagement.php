<?php
/**
 * Plugin Name: Civic Engagement
 * Description: Modular civic engagement and communication platform.
 * Version: 0.1.0
 * Author: Cygnet Informatics
 * Text Domain: civic-engagement
 */

declare(strict_types=1);

use CivicPlatform\Modules\Reps\RepsModule;

if (!defined('ABSPATH')) {
    exit;
}
if (!defined('CIVIC_ENGAGEMENT_PLUGIN_FILE')) {
    define('CIVIC_ENGAGEMENT_PLUGIN_FILE', __FILE__);
}
if (!defined('CIVIC_ENGAGEMENT_PLUGIN_PATH')) {
    define('CIVIC_ENGAGEMENT_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

spl_autoload_register(
    static function (string $class): void {
        $prefix = 'CivicPlatform\\';

        if (0 !== strpos($class, $prefix)) {
            return;
        }

        $relativeClass = substr($class, strlen($prefix));
        $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
        $file = CIVIC_ENGAGEMENT_PLUGIN_PATH . 'app' . DIRECTORY_SEPARATOR . $relativePath;

        if (is_readable($file)) {
            require_once $file;
        }
    }
);

add_action(
    'plugins_loaded',
    static function (): void {
        global $wpdb;

        if (!$wpdb instanceof \wpdb) {
            return;
        }

        $repsModule = new RepsModule($wpdb);
        $repsModule->register();
    }
);
