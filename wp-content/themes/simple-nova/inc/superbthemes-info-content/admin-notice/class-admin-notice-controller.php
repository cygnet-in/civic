<?php

namespace SuperbThemesThemeInformationContent\AdminNotices;

defined('ABSPATH') || exit();

class AdminNoticeController
{
    const PREFIX = 'spbtic_notice_';
    const PREFIX_DELAY = 'spbtic_notice_delay_';

    const ALLOWED_HTML = [
        'div'     => [
            'class' => [],
        ],
        'p'      => [
            'class' => [],
        ],
        'h2'      => [
            'class' => [],
        ],
        'ul'      => [
            'class' => [],
        ],
        'li'      => [
            'class' => [],
        ],
        'span' => [
            'class' => [],
        ],
        'a'      => [
            'class' => [],
            'href' => [],
            'rel'  => [],
            'target' => [],
        ],
        'em'     => [
            'class' => [],
        ],
        'strong' => [
            'class' => [],
        ],
        'img' => [
            'class' => [],
            'alt' => [],
            'src' => [],
            'width' => [],
            'height' => [],
        ],
        'br'     => [],
        'style' => [],
    ];

    private static $notices = [];

    public static function init($options)
    {
        $notices = [];
        if (isset($options['notices']) && is_array($options['notices'])) {
            foreach ($options['notices'] as $notice) {
                if (!isset($notice['unique_id']) || !isset($notice['content'])) {
                    continue;
                }

                $notices[] = $notice;
            }
        }

        $stylesheet = get_stylesheet();
        $addons_notice = $stylesheet . '_addons_notification';
        $addons_notice_def = $stylesheet . '_addons_notification_def';
        $theme_notice = $stylesheet . '_theme_notification';
        $notices[] = array(
            'unique_id' => $addons_notice,
            'content' => "addons-notice.php",
            'remove_if_active' => 'superb-blocks/plugin.php'
        );
        $notices[] = array(
            'unique_id' => $addons_notice_def,
            'content' => "addons-notice-def.php",
            'requires_dismiss' => $addons_notice,
            'remove_if_active' => 'superb-blocks/plugin.php',
            'delay' => '+3 days'
        );
        if (isset($options['theme_url'])) {
            $notices[] = array(
                'unique_id' => $theme_notice,
                'content' => "theme-notice.php",
                'data' => [
                    'theme_url' => $options['theme_url']
                ],
                'delay' => '+2 days'
            );
        }

        self::$notices = $notices;

        add_action('admin_notices', array(__CLASS__, 'AdminNotices'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'EnqueueAddonsInstallScript'));
        add_action('wp_ajax_spbtic_dismiss_notice', array(__CLASS__, 'MaybeDismissNotice'));
    }

    public static function EnqueueAddonsInstallScript()
    {
        if (!current_user_can('manage_options') || !current_user_can('install_plugins')) {
            return;
        }

        if (!class_exists('TGM_Plugin_Activation')) {
            return;
        }

        $tgmpa = \TGM_Plugin_Activation::get_instance();
        if (!isset($tgmpa->plugins['superb-blocks']) || $tgmpa->is_plugin_active('superb-blocks')) {
            return;
        }

        $plugin_basename = 'superb-blocks/plugin.php';
        $plugin_status = $tgmpa->is_plugin_installed('superb-blocks') ? 'installed' : 'not-installed';
        $activation_url = wp_nonce_url(
            self_admin_url('plugins.php?action=activate&plugin=' . $plugin_basename),
            'activate-plugin_' . $plugin_basename
        );

        wp_enqueue_script(
            'spbtic-addons-notice',
            trailingslashit(get_template_directory_uri()) . 'inc/superbthemes-info-content/admin-notice/assets/addons-notice.js',
            array('jquery', 'updates'),
            '1.0.0',
            true
        );

        wp_localize_script(
            'spbtic-addons-notice',
            'spbticAddonsNotice',
            array(
                'slug'           => 'superb-blocks',
                'nonce'          => wp_create_nonce('updates'),
                'ajaxUrl'        => admin_url('admin-ajax.php'),
                'pluginStatus'   => $plugin_status,
                'activationUrl'  => $activation_url,
                'installing'     => __('Installing...', 'simple-nova'),
                'activating'     => __('Activating...', 'simple-nova'),
                'done'           => __('Done!', 'simple-nova'),
            )
        );

        wp_add_inline_style('dashicons', '
            .spbtic-addons-install { display: inline-flex !important; align-items: center; gap: 6px; }
            .spbtic-addons-install .spbtic-addons-spinner { box-sizing: border-box; width: 14px; height: 14px; border: 2px solid currentColor; border-top-color: transparent; border-radius: 50%; animation: spbtic-addons-spin 0.8s linear infinite; opacity: 0.8; flex: 0 0 auto; }
            .spbtic-addons-install .spbtic-addons-spinner:before { content: none !important; }
            .spbtic-addons-install .spbtic-addons-spinner.hidden { display: none; }
            @keyframes spbtic-addons-spin { 100% { transform: rotate(360deg); } }
        ');
    }

    public static function AdminNotices()
    {
        foreach (self::$notices as $notice) {
            $notice_path = trailingslashit(get_template_directory()) . 'inc/superbthemes-info-content/admin-notice/notices/' . $notice['content'];
            if (!file_exists($notice_path)) {
                continue;
            }

            // Remove notice if the required plugin is active
            if (isset($notice['remove_if_active'])) {
                if (is_plugin_active($notice['remove_if_active'])) {
                    continue;
                }
            }

            // Check if the notice requires another notice to be dismissed.
            if (isset($notice['requires_dismiss'])) {
                if (!get_user_meta(get_current_user_id(), self::PREFIX . $notice['requires_dismiss'], true)) {
                    continue;
                }
            }

            // Check if the notice has been dismissed.
            if (get_user_meta(get_current_user_id(), self::PREFIX . $notice['unique_id'], true)) {
                continue;
            }

            // Check if the notice is delayed
            if (isset($notice['delay'])) {
                $delay_init = get_user_meta(get_current_user_id(), self::PREFIX_DELAY . $notice['unique_id'], true);
                if (!$delay_init) {
                    update_user_meta(get_current_user_id(), self::PREFIX_DELAY . $notice['unique_id'], time());
                    continue;
                }

                $delay = strtotime($notice['delay'], $delay_init);
                if ($delay > time()) {
                    continue;
                }
            }

            ob_start();
            include_once $notice_path;
            $content = ob_get_clean();
            echo wp_kses($content, self::ALLOWED_HTML);
        }

        self::PrintScripts();
    }

    public static function PrintScripts()
    {
?>
        <script>
            window.addEventListener("load", function() {
                setTimeout(function() {
                    var notice_ids = <?php echo json_encode(array_column(self::$notices, 'unique_id')); ?>;
                    var nonce = "<?php echo esc_attr(wp_create_nonce('spbtic_dismiss_notice')); ?>";
                    var ajaxurl = "<?php echo esc_url(admin_url('admin-ajax.php')); ?>";

                    notice_ids.forEach(function(notice) {
                        var dismissBtn = document.querySelector(
                            "." + notice + " .notice-dismiss"
                        );

                        if (!dismissBtn) return;

                        // Add an event listener to the dismiss button.
                        dismissBtn.addEventListener("click", function(event) {
                            var httpRequest = new XMLHttpRequest(),
                                postData = "";

                            // Build the data to send in our request.
                            // Data has to be formatted as a string here.
                            postData += "id=" + notice;
                            postData += "&action=spbtic_dismiss_notice";
                            postData += "&nonce=" + nonce;

                            httpRequest.open("POST", ajaxurl);
                            httpRequest.setRequestHeader(
                                "Content-Type",
                                "application/x-www-form-urlencoded"
                            );
                            httpRequest.send(postData);
                        });
                    });
                }, 0);
            });
        </script>
<?php
    }

    public static function MaybeDismissNotice()
    {
        // Sanity check: Early exit if we're not on a spbtic_dismiss_notice action.
        if (!isset($_POST['action']) || 'spbtic_dismiss_notice' !== $_POST['action']) {
            return;
        }

        // Sanity check: Early exit if the ID of the notice does not exist.
        if (!isset($_POST['id']) || !in_array($_POST['id'], array_column(self::$notices, 'unique_id'))) {
            return;
        }

        // Notice ID exists in array, so we can safely use it.
        $notice_id = sanitize_text_field($_POST['id']);

        // Security check: Make sure nonce is OK. check_ajax_referer exits if it fails.
        check_ajax_referer('spbtic_dismiss_notice', 'nonce', true);

        update_user_meta(get_current_user_id(), self::PREFIX . $notice_id, true);
    }

    public static function Cleanup()
    {
        foreach (self::$notices as $notice) {
            delete_user_meta(get_current_user_id(), self::PREFIX . $notice['unique_id']);
            if (isset($notice['delay'])) {
                delete_user_meta(get_current_user_id(), self::PREFIX_DELAY . $notice['unique_id']);
            }
        }
    }
}
