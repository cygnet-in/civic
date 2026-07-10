<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Threads\Admin;

use CivicPlatform\Helpers\AdminMenuHelper;
use CivicPlatform\Modules\Threads\Fields\Admin\ThreadFieldsListPage;
use CivicPlatform\Modules\Threads\Fields\Admin\ThreadFieldEditPage;
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
    private const PARENT_SLUG = 'civic-threads';

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
     * Thread fields list page slug.
     */
    private const FIELDS_SLUG = 'civic-thread-fields';

    /**
     * Thread field edit page slug.
     */
    private const FIELD_EDIT_SLUG = 'civic-thread-field-edit';

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
     * Thread fields listing page.
     *
     * @var ThreadFieldsListPage
     */
    private ThreadFieldsListPage $fieldsListPage;

    /**
     * Thread field edit page.
     *
     * @var ThreadFieldEditPage
     */
    private ThreadFieldEditPage $fieldEditPage;

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
     * @param ThreadFieldsListPage $fieldsListPage Thread fields listing page.
     * @param ThreadFieldEditPage $fieldEditPage Thread field edit page.
     * @param ThreadResponsesListPage $responsesListPage Thread responses listing page.
     * @param ThreadResponseDetailPage $responseDetailPage Thread response detail page.
     * @param ThreadDetailPage $detailPage Thread detail page.
     * @param ThreadEditPage $editPage Thread edit page.
     * @param ThreadCreatePage $createPage Thread creation page.
     */
    public function __construct(
        ThreadsListPage $listPage,
        ThreadFieldsListPage $fieldsListPage,
        ThreadFieldEditPage $fieldEditPage,
        ThreadResponsesListPage $responsesListPage,
        ThreadResponseDetailPage $responseDetailPage,
        ThreadDetailPage $detailPage,
        ThreadEditPage $editPage,
        ThreadCreatePage $createPage
    ) {
        $this->listPage = $listPage;
        $this->fieldsListPage = $fieldsListPage;
        $this->fieldEditPage = $fieldEditPage;
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
        add_action('admin_menu', [$this, 'hideInternalMenuPages'], 999);
        add_action('admin_init', [$this, 'handleExport']);
    }

    /**
     * Handle XLSX exports for consultation admin lists.
     *
     * @return void
     */
    public function handleExport(): void
    {
        if (!$this->isExportRequest()) {
            return;
        }

        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to export consultation records.', 'civic-engagement'));
        }

        $export = isset($_GET['civic_export']) ? wp_unslash($_GET['civic_export']) : '';

        if (is_array($export) || is_object($export)) {
            return;
        }

        if ('consultations' === (string) $export) {
            check_admin_referer('civic_threads_export');
            $this->listPage->export();
        }

        if ('consultation-responses' === (string) $export) {
            check_admin_referer('civic_thread_responses_export');
            $this->responsesListPage->export();
        }
    }

    /**
     * Determine whether the current request is a Threads export request.
     *
     * @return bool
     */
    private function isExportRequest(): bool
    {
        if (!isset($_GET['page'], $_GET['civic_export'])) {
            return false;
        }

        $page = wp_unslash($_GET['page']);
        $export = wp_unslash($_GET['civic_export']);

        if (is_array($page) || is_object($page) || is_array($export) || is_object($export)) {
            return false;
        }

        return in_array((string) $page, [self::LIST_SLUG, self::RESPONSES_SLUG], true)
            && in_array((string) $export, ['consultations', 'consultation-responses'], true);
    }

    /**
     * Register the Threads submenu.
     *
     * @return void
     */
    public function registerMenus(): void
    {
        add_menu_page(
            __('Consultations', 'civic-engagement'),
            __('Consultations', 'civic-engagement'),
            self::CAPABILITY,
            self::LIST_SLUG,
            [$this, 'renderListPage'],
            'dashicons-format-chat',
            31
        );

        add_submenu_page(
            self::PARENT_SLUG,
            __('Consultations', 'civic-engagement'),
            __('Consultations', 'civic-engagement'),
            self::CAPABILITY,
            self::LIST_SLUG,
            [$this, 'renderListPage']
        );

        add_submenu_page(
            ' ',
            __('Create Consultation', 'civic-engagement'),
            __('Add New Thread', 'civic-engagement'),
            self::CAPABILITY,
            self::CREATE_SLUG,
            [$this, 'renderCreatePage']
        );

        add_submenu_page(
            ' ',
            __('Consultation Fields', 'civic-engagement'),
            __('Fields', 'civic-engagement'),
            self::CAPABILITY,
            self::FIELDS_SLUG,
            [$this, 'renderFieldsPage']
        );

        add_submenu_page(
            ' ',
            __('Edit Consultation Field', 'civic-engagement'),
            __('Edit Field', 'civic-engagement'),
            self::CAPABILITY,
            self::FIELD_EDIT_SLUG,
            [$this, 'renderFieldEditPage']
        );

        add_submenu_page(
            self::PARENT_SLUG,
            __('Consultation Responses', 'civic-engagement'),
            __('Responses', 'civic-engagement'),
            self::CAPABILITY,
            self::RESPONSES_SLUG,
            [$this, 'renderResponsesPage']
        );

        add_submenu_page(
            ' ',
            __('View Thread', 'civic-engagement'),
            __('View Thread', 'civic-engagement'),
            self::CAPABILITY,
            self::DETAIL_SLUG,
            [$this, 'renderDetailPage']
        );

        add_submenu_page(
            ' ',
            __('View Response', 'civic-engagement'),
            __('View Response', 'civic-engagement'),
            self::CAPABILITY,
            self::RESPONSE_DETAIL_SLUG,
            [$this, 'renderResponseDetailPage']
        );

        add_submenu_page(
            ' ',
            __('Edit Thread', 'civic-engagement'),
            __('Edit Thread', 'civic-engagement'),
            self::CAPABILITY,
            self::EDIT_SLUG,
            [$this, 'renderEditPage']
        );
    }

    /**
     * Hide internal admin pages after all menu registration is complete.
     *
     * @return void
     */
    public function hideInternalMenuPages(): void
    {
        AdminMenuHelper::hideSubmenuPages(
            self::PARENT_SLUG,
            [
                self::CREATE_SLUG,
                self::DETAIL_SLUG,
                self::EDIT_SLUG,
                self::FIELDS_SLUG,
                self::FIELD_EDIT_SLUG,
                self::RESPONSE_DETAIL_SLUG,
            ]
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
     * Render the thread fields listing admin page.
     *
     * @return void
     */
    public function renderFieldsPage(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $this->fieldsListPage->render();
    }

    /**
     * Render the thread field edit admin page.
     *
     * @return void
     */
    public function renderFieldEditPage(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $this->fieldEditPage->render();
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
