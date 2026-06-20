<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Users\Admin;

/**
 * Registers the Contacts administration page.
 */
class ContactsAdmin
{
    private const CAPABILITY = 'manage_civic_contacts';
    private const PARENT_SLUG = 'civic-platform';
    private const PAGE_SLUG = 'civic-contacts';

    private ContactsListPage $listPage;

    public function __construct(ContactsListPage $listPage)
    {
        $this->listPage = $listPage;
    }

    /**
     * Register admin hooks.
     *
     * @return void
     */
    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenus']);
        add_action('admin_menu', [$this, 'hideMenu'], 999);
        add_action('admin_init', [$this, 'handleExport']);
    }

    /**
     * Register the contact page while retaining direct URL access.
     *
     * @return void
     */
    public function registerMenus(): void
    {
        add_submenu_page(
            self::PARENT_SLUG,
            __('Contacts', 'civic-engagement'),
            __('Contacts', 'civic-engagement'),
            self::CAPABILITY,
            self::PAGE_SLUG,
            [$this, 'renderPage']
        );
    }

    /**
     * Hide the internal contact page after menus are registered.
     *
     * @return void
     */
    public function hideMenu(): void
    {
        remove_submenu_page(self::PARENT_SLUG, self::PAGE_SLUG);
    }

    /**
     * Render the contacts page.
     *
     * @return void
     */
    public function renderPage(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $this->listPage->render();
    }

    /**
     * Handle a contact CSV export request.
     *
     * @return void
     */
    public function handleExport(): void
    {
        if (!isset($_GET['page']) || is_array($_GET['page']) || is_object($_GET['page'])) {
            return;
        }

        if (self::PAGE_SLUG !== sanitize_key((string) wp_unslash($_GET['page']))) {
            return;
        }

        if (!isset($_GET['civic_contact_export']) || is_array($_GET['civic_contact_export']) || is_object($_GET['civic_contact_export'])) {
            return;
        }

        if ('1' !== (string) wp_unslash($_GET['civic_contact_export'])) {
            return;
        }

        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to export contacts.', 'civic-engagement'));
        }

        check_admin_referer('civic_contact_export');
        $this->listPage->export();
    }
}
