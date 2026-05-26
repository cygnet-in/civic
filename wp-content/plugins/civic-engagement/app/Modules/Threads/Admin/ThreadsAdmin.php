<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Threads\Admin;

use CivicPlatform\Modules\Threads\Responses\Admin\ThreadResponseDetailPage;
use CivicPlatform\Modules\Threads\Responses\Admin\ThreadResponsesListPage;

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
     * Thread responses list page slug.
     */
    private const RESPONSES_SLUG = 'civic-thread-responses';

    /**
     * Thread response detail page slug.
     */
    private const RESPONSE_DETAIL_SLUG = 'civic-thread-response-view';

    /**
     * Thread listing page.
     *
     * @var ThreadsListPage
     */
    private ThreadsListPage $listPage;

    /**
     * Thread responses listing page.
     *
     * @var ThreadResponsesListPage
     */
    private ThreadResponsesListPage $responsesListPage;

    /**
     * Thread response detail page.
     *
     * @var ThreadResponseDetailPage
     */
    private ThreadResponseDetailPage $responseDetailPage;

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
     * @param ThreadResponsesListPage $responsesListPage Thread responses listing page.
     * @param ThreadResponseDetailPage $responseDetailPage Thread response detail page.
     * @param ThreadDetailPage $detailPage Thread detail page.
     * @param ThreadEditPage $editPage Thread edit page.
     * @param ThreadCreatePage $createPage Thread creation page.
     */
    public function __construct(
        ThreadsListPage $listPage,
        ThreadResponsesListPage $responsesListPage,
        ThreadResponseDetailPage $responseDetailPage,
        ThreadDetailPage $detailPage,
        ThreadEditPage $editPage,
        ThreadCreatePage $createPage
    ) {
        $this->listPage = $listPage;
        $this->responsesListPage = $responsesListPage;
        $this->responseDetailPage = $responseDetailPage;
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
            __('Consultation Responses', 'civic-engagement'),
            __('Thread Responses', 'civic-engagement'),
            self::CAPABILITY,
            self::RESPONSES_SLUG,
            [$this, 'renderResponsesPage']
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
            __('View Response', 'civic-engagement'),
            __('View Response', 'civic-engagement'),
            self::CAPABILITY,
            self::RESPONSE_DETAIL_SLUG,
            [$this, 'renderResponseDetailPage']
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
     * Render the thread responses listing admin page.
     *
     * @return void
     */
    public function renderResponsesPage(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $this->responsesListPage->render();
    }

    /**
     * Render the thread response detail admin page.
     *
     * @return void
     */
    public function renderResponseDetailPage(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $this->responseDetailPage->render();
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
