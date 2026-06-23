<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Dashboard;

/** Registers the Civic Dashboard menu, redirects, and WordPress dashboard cleanup. */
class DashboardAdmin
{
    private const CAPABILITY = 'manage_civic_reps';
    private const PAGE_SLUG = 'civic-dashboard';

    private DashboardPage $page;

    public function __construct(DashboardPage $page)
    {
        $this->page = $page;
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu'], 1);
        add_action('admin_menu', [$this, 'hideWordPressDashboardForCivicUsers'], 999);
        add_action('admin_init', [$this, 'redirectWordPressDashboard']);
        add_action('wp_dashboard_setup', [$this, 'removeWordPressDashboardWidgets']);
        add_filter('login_redirect', [$this, 'redirectAfterLogin'], 10, 3);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_filter('admin_body_class', [$this, 'addAdminBodyClass']);
    }

    public function registerMenu(): void
    {
        add_menu_page(
            __('Dashboard', 'civic-engagement'),
            __('Dashboard', 'civic-engagement'),
            self::CAPABILITY,
            self::PAGE_SLUG,
            [$this, 'renderPage'],
            'dashicons-admin-home',
            2
        );
    }

    public function renderPage(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $this->page->render();
    }

    public function hideWordPressDashboardForCivicUsers(): void
    {
        if (!$this->isRestrictedCivicUser()) {
            return;
        }

        foreach (['index.php', 'edit-comments.php', 'tools.php', 'themes.php', 'plugins.php', 'options-general.php'] as $menuSlug) {
            remove_menu_page($menuSlug);
        }

        remove_submenu_page('index.php', 'update-core.php');
    }

    public function redirectWordPressDashboard(): void
    {
        global $pagenow;

        if ('index.php' !== $pagenow || !$this->isRestrictedCivicUser()) {
            return;
        }

        wp_safe_redirect($this->dashboardUrl());
        exit;
    }

    public function removeWordPressDashboardWidgets(): void
    {
        if (!$this->isRestrictedCivicUser()) {
            return;
        }

        remove_action('welcome_panel', 'wp_welcome_panel');
        foreach (['dashboard_right_now', 'dashboard_activity', 'dashboard_quick_press', 'dashboard_primary', 'dashboard_secondary', 'dashboard_site_health'] as $id) {
            remove_meta_box($id, 'dashboard', 'normal');
            remove_meta_box($id, 'dashboard', 'side');
        }
    }

    /** @param string $redirectTo @param string $requestedRedirectTo @param mixed $user */
    public function redirectAfterLogin(string $redirectTo, string $requestedRedirectTo, $user): string
    {
        unset($requestedRedirectTo);

        if (!is_object($user) || !isset($user->ID) || user_can($user, 'manage_options') || !user_can($user, self::CAPABILITY)) {
            return $redirectTo;
        }

        return $this->dashboardUrl();
    }

    /** @param string $hookSuffix */
    public function enqueueAssets(string $hookSuffix): void
    {
        if (!$this->isRestrictedCivicUser()) {
            return;
        }

        unset($hookSuffix);

        wp_enqueue_style(
            'civic-admin',
            CIVIC_ENGAGEMENT_PLUGIN_URL . 'assets/css/civic-admin.css',
            [],
            CIVIC_ENGAGEMENT_VERSION
        );
    }

    public function addAdminBodyClass(string $classes): string
    {
        return $this->isRestrictedCivicUser() ? $classes . ' civic-admin' : $classes;
    }

    private function isCivicUser(): bool
    {
        return current_user_can(self::CAPABILITY);
    }

    private function isRestrictedCivicUser(): bool
    {
        return $this->isCivicUser() && !current_user_can('manage_options');
    }

    private function dashboardUrl(): string
    {
        return admin_url('admin.php?page=' . self::PAGE_SLUG);
    }
}
