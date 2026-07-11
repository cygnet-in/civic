<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Dashboard;

/** Registers the Civic Dashboard menu, redirects, and WordPress dashboard cleanup. */
class DashboardAdmin
{
    private const CAPABILITY = 'manage_civic_reps';
    private const SETTINGS_CAPABILITY = 'manage_civic_settings';
    private const PAGE_SLUG = 'civic-dashboard';
    private const SYSTEM_PAGE_SLUG = 'civic-system';
    private const SECURITY_PAGE_SLUG = 'civic-security-settings';
    private const CIVIC_ADMIN_PATH = 'civic-admin';
    private const CIVIC_PAGE_SLUGS = [
        'civic-dashboard',
        'civic-system',
        'civic-security-settings',
        'civic-account',
        'civic-platform',
        'civic-rep-view',
        'civic-activities',
        'civic-threads',
        'civic-thread-create',
        'civic-thread-view',
        'civic-thread-edit',
        'civic-thread-fields',
        'civic-thread-field-edit',
        'civic-thread-responses',
        'civic-thread-response-view',
        'civic-events',
        'civic-event-edit',
        'civic-event-fields',
        'civic-event-field-edit',
        'civic-event-registrations',
        'civic-event-registration-view',
        'civic-schedules',
        'civic-schedule-edit',
        'civic-contacts',
    ];

    private DashboardPage $page;
    private SecuritySettingsPage $securityPage;
    private DocumentationPage $documentationPage;

    public function __construct(DashboardPage $page, SecuritySettingsPage $securityPage, ?DocumentationPage $documentationPage = null)
    {
        $this->page = $page;
        $this->securityPage = $securityPage;
        $this->documentationPage = $documentationPage ?? new DocumentationPage();
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenu'], 1);
        add_action('admin_menu', [$this, 'hideWordPressDashboardForCivicUsers'], 999);
        add_action('admin_init', [$this, 'redirectWordPressDashboard']);
        add_action('wp_dashboard_setup', [$this, 'removeWordPressDashboardWidgets']);
        add_filter('login_redirect', [$this, 'redirectAfterLogin'], 10, 3);
        add_action('init', [$this, 'handleCivicAdminLoginUrl']);
        add_action('login_enqueue_scripts', [$this, 'enqueueLoginAssets']);
        add_filter('login_headerurl', [$this, 'loginHeaderUrl']);
        add_filter('login_headertext', [$this, 'loginHeaderText']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_filter('admin_body_class', [$this, 'addAdminBodyClass']);
        add_action('in_admin_header', [$this, 'renderAdminHeader']);
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

        add_menu_page(
            __('System', 'civic-engagement'),
            __('System', 'civic-engagement'),
            self::CAPABILITY,
            self::SYSTEM_PAGE_SLUG,
            [$this, 'renderDocumentationPage'],
            'dashicons-admin-tools',
            59
        );

        add_submenu_page(
            self::SYSTEM_PAGE_SLUG,
            __('Documentation', 'civic-engagement'),
            __('Documentation', 'civic-engagement'),
            self::CAPABILITY,
            self::SYSTEM_PAGE_SLUG,
            [$this, 'renderDocumentationPage']
        );

        add_submenu_page(
            self::SYSTEM_PAGE_SLUG,
            __('Security Settings', 'civic-engagement'),
            __('Security', 'civic-engagement'),
            self::SETTINGS_CAPABILITY,
            self::SECURITY_PAGE_SLUG,
            [$this, 'renderSecurityPage']
        );
    }

    public function renderPage(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $this->page->render();
    }

    public function renderDocumentationPage(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $this->documentationPage->render();
    }

    public function renderSecurityPage(): void
    {
        if (!current_user_can(self::SETTINGS_CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $this->securityPage->render();
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
        if (!$this->isRestrictedCivicUser() && !$this->isCivicAdminPage()) {
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
        if ($this->isRestrictedCivicUser()) {
            $classes .= ' civic-admin';
        }

        if ($this->isCivicAdminPage()) {
            $classes .= ' civic-admin-page civic-admin-fixed-header-active';
        }

        return $classes;
    }

    public function enqueueLoginAssets(): void
    {
        wp_enqueue_style(
            'civic-login',
            CIVIC_ENGAGEMENT_PLUGIN_URL . 'assets/css/civic-login.css',
            [],
            CIVIC_ENGAGEMENT_VERSION
        );
    }

    public function loginHeaderUrl(): string
    {
        return home_url('/');
    }

    public function loginHeaderText(): string
    {
        return __('Civic Platform', 'civic-engagement');
    }

    public function handleCivicAdminLoginUrl(): void
    {
        $path = trim((string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');
        $segments = '' === $path ? [] : explode('/', $path);
        $lastSegment = sanitize_key((string) end($segments));

        if (self::CIVIC_ADMIN_PATH !== $lastSegment) {
            return;
        }

        if (is_user_logged_in()) {
            wp_safe_redirect(current_user_can(self::CAPABILITY) ? $this->dashboardUrl() : admin_url());
            exit;
        }

        wp_safe_redirect(wp_login_url($this->dashboardUrl()));
        exit;
    }

    public function renderAdminHeader(): void
    {
        if (!$this->isCivicAdminPage()) {
            return;
        }

        echo '<div class="civic-admin-fixed-header" role="banner">';
        echo '<div class="civic-admin-fixed-header__identity">';
        echo '<span class="civic-admin-fixed-header__logo" aria-hidden="true">CP</span>';
        echo '<div class="civic-admin-fixed-header__text">';
        echo '<div class="civic-admin-fixed-header__title">' . esc_html__('Civic Platform', 'civic-engagement') . '</div>';
        echo '<div class="civic-admin-fixed-header__version">' . esc_html(sprintf(__('Version %s', 'civic-engagement'), CIVIC_ENGAGEMENT_VERSION)) . '</div>';
        echo '</div>';
        echo '</div>';
        echo '<div class="civic-admin-fixed-header__actions">';
        echo '<a class="button civic-admin-fixed-header__documentation" href="' . esc_url($this->documentationUrl()) . '">' . esc_html__('Documentation', 'civic-engagement') . '</a>';
        echo '<a class="button button-primary civic-admin-fixed-header__visit" href="' . esc_url(home_url('/')) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Visit Website', 'civic-engagement') . '</a>';
        echo '</div>';
        echo '</div>';
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

    private function documentationUrl(): string
    {
        return admin_url('admin.php?page=' . self::SYSTEM_PAGE_SLUG);
    }

    private function isCivicAdminPage(): bool
    {
        if (!is_admin() || !isset($_GET['page'])) {
            return false;
        }

        $page = sanitize_key((string) wp_unslash($_GET['page']));

        return in_array($page, self::CIVIC_PAGE_SLUGS, true);
    }
}
