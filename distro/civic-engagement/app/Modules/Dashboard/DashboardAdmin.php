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
        add_action('admin_menu', [$this, 'separateAdminNavigation'], 1000);
        add_action('admin_init', [$this, 'redirectWordPressDashboard']);
        add_action('wp_dashboard_setup', [$this, 'removeWordPressDashboardWidgets']);
        add_filter('login_redirect', [$this, 'redirectAfterLogin'], 10, 3);
        add_action('init', [$this, 'handleCivicAdminLoginUrl']);
        add_action('login_enqueue_scripts', [$this, 'enqueueLoginAssets']);
        add_filter('login_headerurl', [$this, 'loginHeaderUrl']);
        add_filter('login_headertext', [$this, 'loginHeaderText']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_filter('admin_body_class', [$this, 'addAdminBodyClass']);
        add_action('admin_head', [$this, 'hideWordPressAdminBar']);
        add_action('in_admin_header', [$this, 'renderAdminHeader']);
    }

    public function registerMenu(): void
    {
        add_menu_page(
            __('Civic Dashboard', 'civic-engagement'),
            current_user_can('manage_options') ? __('Civic Admin', 'civic-engagement') : __('Dashboard', 'civic-engagement'),
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

    public function separateAdminNavigation(): void
    {
        if (!$this->isCivicUser()) {
            return;
        }

        if ($this->isCivicAdminPage()) {
            $this->hideNonCivicAdminMenus();
            return;
        }

        $this->hideCivicOperationalMenus();
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
        if (!$this->isCivicAdminPage()) {
            return;
        }

        unset($hookSuffix);

        wp_enqueue_style(
            'civic-admin',
            CIVIC_ENGAGEMENT_PLUGIN_URL . 'assets/css/civic-admin.css',
            [],
            CIVIC_ENGAGEMENT_VERSION
        );

        wp_enqueue_style(
            'civic-manager-admin',
            get_stylesheet_directory_uri() . '/assets/css/civic-manager-admin.css',
            [],
            CIVIC_ENGAGEMENT_VERSION
        );
    }

    public function addAdminBodyClass(string $classes): string
    {
        if ($this->isCivicAdminPage()) {
            $classes .= ' civic-admin civic-manager-admin civic-admin-page civic-admin-fixed-header-active';
        }

        return $classes;
    }

    public function hideWordPressAdminBar(): void
    {
        if (!$this->isCivicAdminPage()) {
            return;
        }

        echo '<style>html.wp-toolbar{padding-top:0!important}#wpadminbar{display:none!important}</style>';
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
        if (current_user_can('manage_options')) {
            echo '<a class="button civic-admin-fixed-header__wp-admin" href="' . esc_url(admin_url('index.php')) . '">' . esc_html__('WP Admin', 'civic-engagement') . '</a>';
        }
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

    private function hideNonCivicAdminMenus(): void
    {
        foreach ($this->registeredTopLevelMenuSlugs() as $menuSlug) {
            if (0 === strpos($menuSlug, 'civic-')) {
                continue;
            }

            remove_menu_page($menuSlug);
        }
    }

    private function hideCivicOperationalMenus(): void
    {
        foreach ($this->registeredTopLevelMenuSlugs() as $menuSlug) {
            if (0 !== strpos($menuSlug, 'civic-') || self::PAGE_SLUG === $menuSlug) {
                continue;
            }

            remove_menu_page($menuSlug);
        }
    }

    /** @return array<int, string> */
    private function registeredTopLevelMenuSlugs(): array
    {
        global $menu;

        if (!is_array($menu)) {
            return [];
        }

        $menuSlugs = [];

        foreach ($menu as $menuItem) {
            if (!is_array($menuItem) || !isset($menuItem[2]) || !is_string($menuItem[2])) {
                continue;
            }

            $menuSlugs[] = $menuItem[2];
        }

        return $menuSlugs;
    }

    private function isCivicAdminPage(): bool
    {
        if (!is_admin() || !isset($_GET['page'])) {
            return false;
        }

        $page = sanitize_key((string) wp_unslash($_GET['page']));

        return 0 === strpos($page, 'civic-');
    }
}
