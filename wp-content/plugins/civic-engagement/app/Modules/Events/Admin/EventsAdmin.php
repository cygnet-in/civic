<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Events\Admin;

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
    private const PARENT_SLUG = 'civic-platform';

    /**
     * Event list page slug.
     */
    private const LIST_SLUG = 'civic-events';

    /**
     * Event edit page slug.
     */
    private const EDIT_SLUG = 'civic-event-edit';

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
     * @param EventRegistrationsListPage $registrationsListPage Event registrations listing page.
     * @param EventRegistrationDetailPage $registrationDetailPage Event registration detail page.
     */
    public function __construct(
        EventsListPage $listPage,
        EventEditPage $editPage,
        EventRegistrationsListPage $registrationsListPage,
        EventRegistrationDetailPage $registrationDetailPage
    ) {
        $this->listPage = $listPage;
        $this->editPage = $editPage;
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
    }

    /**
     * Register Events admin menu pages.
     *
     * @return void
     */
    public function registerMenus(): void
    {
        add_submenu_page(
            self::PARENT_SLUG,
            __('Events', 'civic-engagement'),
            __('Events', 'civic-engagement'),
            self::CAPABILITY,
            self::LIST_SLUG,
            [$this, 'renderListPage']
        );

        add_submenu_page(
            self::PARENT_SLUG,
            __('Add Event', 'civic-engagement'),
            __('Add Event', 'civic-engagement'),
            self::CAPABILITY,
            self::EDIT_SLUG,
            [$this, 'renderEditPage']
        );

        add_submenu_page(
            self::PARENT_SLUG,
            __('Event Registrations', 'civic-engagement'),
            __('Event Registrations', 'civic-engagement'),
            self::CAPABILITY,
            self::REGISTRATIONS_SLUG,
            [$this, 'renderRegistrationsPage']
        );

        add_submenu_page(
            self::PARENT_SLUG,
            __('View Event Registration', 'civic-engagement'),
            __('View Event Registration', 'civic-engagement'),
            self::CAPABILITY,
            self::REGISTRATION_DETAIL_SLUG,
            [$this, 'renderRegistrationDetailPage']
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
