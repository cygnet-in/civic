<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Threads\Admin;

/**
 * Registers admin pages for the Threads module.
 */
class ThreadsAdmin
{
    /**
     * Required capability for thread administration.
     */
    private const CAPABILITY = 'manage_civic_threads';

    /**
     * Parent Civic Platform menu slug.
     */
    private const PARENT_SLUG = 'civic-platform';

    /**
     * Threads create page slug.
     */
    private const CREATE_SLUG = 'civic-threads';

    /**
     * Thread creation page.
     *
     * @var ThreadCreatePage
     */
    private ThreadCreatePage $createPage;

    /**
     * @param ThreadCreatePage $createPage Thread creation page.
     */
    public function __construct(ThreadCreatePage $createPage)
    {
        $this->createPage = $createPage;
    }

    /**
     * Register WordPress admin hooks.
     *
     * @return void
     */
    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenus']);
    }

    /**
     * Register the Threads submenu.
     *
     * @return void
     */
    public function registerMenus(): void
    {
        add_submenu_page(
            self::PARENT_SLUG,
            __('Threads / Consultations', 'civic-engagement'),
            __('Threads', 'civic-engagement'),
            self::CAPABILITY,
            self::CREATE_SLUG,
            [$this, 'renderCreatePage']
        );
    }

    /**
     * Render the thread creation admin page.
     *
     * @return void
     */
    public function renderCreatePage(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $this->createPage->render();
    }
}
