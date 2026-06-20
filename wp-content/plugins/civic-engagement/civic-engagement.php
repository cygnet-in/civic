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
use CivicPlatform\Modules\Events\EventsModule;
use CivicPlatform\Modules\Reps\RepsModule;
use CivicPlatform\Modules\Schedules\SchedulesModule;
use CivicPlatform\Modules\Threads\ThreadsModule;
use CivicPlatform\Modules\Users\ContactsModule;

if (!defined('ABSPATH')) {
    exit;
}
if (!defined('CIVIC_ENGAGEMENT_PLUGIN_FILE')) {
    define('CIVIC_ENGAGEMENT_PLUGIN_FILE', __FILE__);
}
if (!defined('CIVIC_ENGAGEMENT_PLUGIN_PATH')) {
    define('CIVIC_ENGAGEMENT_PLUGIN_PATH', plugin_dir_path(__FILE__));
}
if (!defined('CIVIC_ENGAGEMENT_PLUGIN_URL')) {
    define('CIVIC_ENGAGEMENT_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('CIVIC_ENGAGEMENT_VERSION')) {
    define('CIVIC_ENGAGEMENT_VERSION', '0.1.0');
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

        $eventsModule = new EventsModule($wpdb);
        $eventsModule->register();

        $schedulesModule = new SchedulesModule($wpdb);
        $schedulesModule->register();

        $contactsModule = new ContactsModule($wpdb);
        $contactsModule->register();

        $capabilities = new Capabilities();
        $capabilities->register();
    }
);

function civic_enqueue_frontend_assets(): void
{
    wp_enqueue_style(
        'civic-frontend',
        CIVIC_ENGAGEMENT_PLUGIN_URL . 'assets/css/frontend.css',
        [],
        CIVIC_ENGAGEMENT_VERSION
    );
}

add_action('wp_enqueue_scripts', 'civic_enqueue_frontend_assets');
