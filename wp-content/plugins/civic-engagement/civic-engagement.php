<?php
/**
 * Plugin Name: Civic Engagement
 * Description: Modular civic engagement and communication platform.
 * Version: 0.1.0
 * Author: Cygnet Informatics
 * Text Domain: civic-engagement
 */

declare(strict_types=1);

use CivicPlatform\Core\Capabilities;
use CivicPlatform\Modules\Activities\ActivitiesModule;
use CivicPlatform\Modules\Reps\RepsModule;
use CivicPlatform\Modules\Threads\ThreadsModule;

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

register_activation_hook(
    __FILE__,
    static function (): void {
        $capabilities = new Capabilities();
        $capabilities->register();
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

        $activitiesModule = new ActivitiesModule($wpdb);
        $activitiesModule->register();

        $threadsModule = new ThreadsModule($wpdb);
        $threadsModule->register();

        $capabilities = new Capabilities();
        $capabilities->register();
    }
);
