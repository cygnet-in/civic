<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Account;

/** Registers the Civic Account menu and removes standard profile access points. */
class AccountAdmin
{
    private const CAPABILITY = 'manage_civic_reps';
    private const PAGE_SLUG = 'civic-account';

    private ChangePasswordPage $passwordPage;

    public function __construct(ChangePasswordPage $passwordPage)
    {
        $this->passwordPage = $passwordPage;
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenus'], 2);
        add_action('admin_menu', [$this, 'hideProfileMenu'], 999);
        add_action('admin_head', [$this, 'hideAdminToolbar']);
        add_filter('show_admin_bar', [$this, 'hideFrontendToolbar']);
    }

    public function registerMenus(): void
    {
        if (!$this->isRestrictedCivicUser()) {
            return;
        }

        add_menu_page(
            __('Account', 'civic-engagement'),
            __('Account', 'civic-engagement'),
            self::CAPABILITY,
            self::PAGE_SLUG,
            [$this, 'renderChangePasswordPage'],
            'dashicons-admin-users',
            35
        );
        add_submenu_page(
            self::PAGE_SLUG,
            __('Change Password', 'civic-engagement'),
            __('Change Password', 'civic-engagement'),
            self::CAPABILITY,
            self::PAGE_SLUG,
            [$this, 'renderChangePasswordPage']
        );
        add_submenu_page(
            self::PAGE_SLUG,
            __('Logout', 'civic-engagement'),
            __('Logout', 'civic-engagement'),
            self::CAPABILITY,
            wp_logout_url(home_url('/'))
        );
    }

    public function renderChangePasswordPage(): void
    {
        $this->ensureRestrictedCivicUser();
        $this->passwordPage->render();
    }

    public function hideProfileMenu(): void
    {
        if (!$this->isRestrictedCivicUser()) {
            return;
        }

        remove_menu_page('profile.php');
        remove_submenu_page('users.php', 'profile.php');
    }

    public function hideAdminToolbar(): void
    {
        if (!$this->isRestrictedCivicUser()) {
            return;
        }

        echo '<style>html.wp-toolbar{padding-top:0!important}#wpadminbar{display:none!important}</style>';
    }

    /** @param bool $show */
    public function hideFrontendToolbar(bool $show): bool
    {
        return $this->isRestrictedCivicUser() ? false : $show;
    }

    private function ensureRestrictedCivicUser(): void
    {
        if (!$this->isRestrictedCivicUser()) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }
    }

    private function isRestrictedCivicUser(): bool
    {
        return current_user_can(self::CAPABILITY) && !current_user_can('manage_options');
    }
}
