<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Events\Admin;

use CivicPlatform\Helpers\AdminMenuHelper;
use CivicPlatform\Modules\Events\Registrations\Admin\EventRegistrationDetailPage;
use CivicPlatform\Modules\Events\Registrations\Admin\EventRegistrationsListPage;

/**
 * Registers admin pages for the Events module.
 */
class EventsAdmin
{
    /**
     * Required capability for event administration.
     */
    private const CAPABILITY = 'manage_civic_events';

    /**
     * Parent Civic Platform menu slug.
     */
    private const PARENT_SLUG = 'civic-events';

    /**
     * Event list page slug.
     */
    private const LIST_SLUG = 'civic-events';

    /**
     * Event edit page slug.
     */
    private const EDIT_SLUG = 'civic-event-edit';

    /**
     * Event fields list page slug.
     */
    private const FIELDS_SLUG = 'civic-event-fields';

    /**
     * Event field edit page slug.
     */
    private const FIELD_EDIT_SLUG = 'civic-event-field-edit';

    /**
     * Event registrations list page slug.
     */
    private const REGISTRATIONS_SLUG = 'civic-event-registrations';

    /**
     * Event registration detail page slug.
     */
    private const REGISTRATION_DETAIL_SLUG = 'civic-event-registration-view';

    /**
     * Events list page.
     *
     * @var EventsListPage
     */
    private EventsListPage $listPage;

    /**
     * Event edit page.
     *
     * @var EventEditPage
     */
    private EventEditPage $editPage;

    /**
     * Event fields list page.
     *
     * @var EventFieldsListPage
     */
    private EventFieldsListPage $fieldsListPage;

    /**
     * Event field edit page.
     *
     * @var EventFieldEditPage
     */
    private EventFieldEditPage $fieldEditPage;

    /**
     * Event registrations listing page.
     *
     * @var EventRegistrationsListPage
     */
    private EventRegistrationsListPage $registrationsListPage;

    /**
     * Event registration detail page.
     *
     * @var EventRegistrationDetailPage
     */
    private EventRegistrationDetailPage $registrationDetailPage;

    /**
     * @param EventsListPage $listPage Events list page.
     * @param EventEditPage $editPage Event edit page.
     * @param EventFieldsListPage $fieldsListPage Event fields list page.
     * @param EventFieldEditPage $fieldEditPage Event field edit page.
     * @param EventRegistrationsListPage $registrationsListPage Event registrations listing page.
     * @param EventRegistrationDetailPage $registrationDetailPage Event registration detail page.
     */
    public function __construct(
        EventsListPage $listPage,
        EventEditPage $editPage,
        EventFieldsListPage $fieldsListPage,
        EventFieldEditPage $fieldEditPage,
        EventRegistrationsListPage $registrationsListPage,
        EventRegistrationDetailPage $registrationDetailPage
    ) {
        $this->listPage = $listPage;
        $this->editPage = $editPage;
        $this->fieldsListPage = $fieldsListPage;
        $this->fieldEditPage = $fieldEditPage;
        $this->registrationsListPage = $registrationsListPage;
        $this->registrationDetailPage = $registrationDetailPage;
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
     * Handle XLSX exports for event admin lists.
     *
     * @return void
     */
    public function handleExport(): void
    {
        if (!$this->isExportRequest()) {
            return;
        }

        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to export event records.', 'civic-engagement'));
        }

        $export = isset($_GET['civic_export']) ? wp_unslash($_GET['civic_export']) : '';

        if (is_array($export) || is_object($export)) {
            return;
        }

        if ('events' === (string) $export) {
            check_admin_referer('civic_events_export');
            $this->listPage->export();
        }

        if ('event-registrations' === (string) $export) {
            check_admin_referer('civic_event_registrations_export');
            $this->registrationsListPage->export();
        }
    }

    /**
     * Determine whether the current request is an Events export request.
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

        return in_array((string) $page, [self::LIST_SLUG, self::REGISTRATIONS_SLUG], true)
            && in_array((string) $export, ['events', 'event-registrations'], true);
    }

    /**
     * Register Events admin menu pages.
     *
     * @return void
     */
    public function registerMenus(): void
    {
        add_menu_page(
            __('Events', 'civic-engagement'),
            __('Events', 'civic-engagement'),
            self::CAPABILITY,
            self::LIST_SLUG,
            [$this, 'renderListPage'],
            'dashicons-calendar-alt',
            32
        );

        add_submenu_page(
            self::PARENT_SLUG,
            __('Events', 'civic-engagement'),
            __('Events', 'civic-engagement'),
            self::CAPABILITY,
            self::LIST_SLUG,
            [$this, 'renderListPage']
        );

        add_submenu_page(
            ' ',
            __('Add Event', 'civic-engagement'),
            __('Add Event', 'civic-engagement'),
            self::CAPABILITY,
            self::EDIT_SLUG,
            [$this, 'renderEditPage']
        );

        add_submenu_page(
            ' ',
            __('Event Fields', 'civic-engagement'),
            __('Event Fields', 'civic-engagement'),
            self::CAPABILITY,
            self::FIELDS_SLUG,
            [$this, 'renderFieldsPage']
        );

        add_submenu_page(
            ' ',
            __('Edit Event Field', 'civic-engagement'),
            __('Edit Event Field', 'civic-engagement'),
            self::CAPABILITY,
            self::FIELD_EDIT_SLUG,
            [$this, 'renderFieldEditPage']
        );

        add_submenu_page(
            self::PARENT_SLUG,
            __('Event Registrations', 'civic-engagement'),
            __('Registrations', 'civic-engagement'),
            self::CAPABILITY,
            self::REGISTRATIONS_SLUG,
            [$this, 'renderRegistrationsPage']
        );

        add_submenu_page(
            ' ',
            __('View Event Registration', 'civic-engagement'),
            __('View Event Registration', 'civic-engagement'),
            self::CAPABILITY,
            self::REGISTRATION_DETAIL_SLUG,
            [$this, 'renderRegistrationDetailPage']
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
                self::EDIT_SLUG,
                self::FIELDS_SLUG,
                self::FIELD_EDIT_SLUG,
                self::REGISTRATION_DETAIL_SLUG,
            ]
        );
    }

    /**
     * Render the Events listing page.
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
     * Render the Event add/edit/view page.
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

    /**
     * Render the event fields listing page.
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
     * Render the event field create/edit page.
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
     * Render the event registrations listing page.
     *
     * @return void
     */
    public function renderRegistrationsPage(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $this->registrationsListPage->render();
    }

    /**
     * Render the event registration detail page.
     *
     * @return void
     */
    public function renderRegistrationDetailPage(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $this->registrationDetailPage->render();
    }
}
