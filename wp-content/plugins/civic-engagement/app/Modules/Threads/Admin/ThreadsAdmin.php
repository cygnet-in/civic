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
     * Threads list page slug.
     */
    private const LIST_SLUG = 'civic-threads';

    /**
     * Threads create page slug.
     */
    private const CREATE_SLUG = 'civic-thread-create';

    /**
     * Thread detail page slug.
     */
    private const DETAIL_SLUG = 'civic-thread-view';

    /**
     * Thread edit page slug.
     */
    private const EDIT_SLUG = 'civic-thread-edit';

    /**
     * Thread listing page.
     *
     * @var ThreadsListPage
     */
    private ThreadsListPage $listPage;

    /**
     * Thread detail page.
     *
     * @var ThreadDetailPage
     */
    private ThreadDetailPage $detailPage;

    /**
     * Thread edit page.
     *
     * @var ThreadEditPage
     */
    private ThreadEditPage $editPage;

    /**
     * Thread creation page.
     *
     * @var ThreadCreatePage
     */
    private ThreadCreatePage $createPage;

    /**
     * @param ThreadsListPage $listPage Thread listing page.
     * @param ThreadDetailPage $detailPage Thread detail page.
     * @param ThreadEditPage $editPage Thread edit page.
     * @param ThreadCreatePage $createPage Thread creation page.
     */
    public function __construct(
        ThreadsListPage $listPage,
        ThreadDetailPage $detailPage,
        ThreadEditPage $editPage,
        ThreadCreatePage $createPage
    ) {
        $this->listPage = $listPage;
        $this->detailPage = $detailPage;
        $this->editPage = $editPage;
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
            self::LIST_SLUG,
            [$this, 'renderListPage']
        );

        add_submenu_page(
            self::PARENT_SLUG,
            __('Create Consultation', 'civic-engagement'),
            __('Add New Thread', 'civic-engagement'),
            self::CAPABILITY,
            self::CREATE_SLUG,
            [$this, 'renderCreatePage']
        );

        add_submenu_page(
            self::PARENT_SLUG,
            __('View Thread', 'civic-engagement'),
            __('View Thread', 'civic-engagement'),
            self::CAPABILITY,
            self::DETAIL_SLUG,
            [$this, 'renderDetailPage']
        );

        add_submenu_page(
            self::PARENT_SLUG,
            __('Edit Thread', 'civic-engagement'),
            __('Edit Thread', 'civic-engagement'),
            self::CAPABILITY,
            self::EDIT_SLUG,
            [$this, 'renderEditPage']
        );
    }

    /**
     * Render the thread listing admin page.
     *
     * @return void
     */
    public function renderListPage(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $this->listPage->render();
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

    /**
     * Render the thread detail admin page.
     *
     * @return void
     */
    public function renderDetailPage(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $this->detailPage->render();
    }

    /**
     * Render the thread edit admin page.
     *
     * @return void
     */
    public function renderEditPage(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $this->editPage->render();
    }
}
