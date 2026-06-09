<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Events;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Modules\Events\Admin\EventEditPage;
use CivicPlatform\Modules\Events\Admin\EventFieldEditPage;
use CivicPlatform\Modules\Events\Admin\EventFieldsListPage;
use CivicPlatform\Modules\Events\Admin\EventsAdmin;
use CivicPlatform\Modules\Events\Admin\EventsListPage;
use CivicPlatform\Modules\Events\Frontend\EventDetailShortcode;
use CivicPlatform\Modules\Events\Frontend\EventListShortcode;
use CivicPlatform\Modules\Events\Registrations\Frontend\EventRegistrationForm;
use CivicPlatform\Modules\Events\Registrations\Services\EventRegistrationService;
use CivicPlatform\Modules\Activities\Repository\ActivityRepository;
use CivicPlatform\Modules\Events\Repository\EventRepository;
use CivicPlatform\Modules\Events\Repository\EventRegistrationRepository;
use CivicPlatform\Modules\Events\Repository\EventFieldRepository;
use CivicPlatform\Modules\Events\Registrations\Admin\EventRegistrationDetailPage;
use CivicPlatform\Modules\Events\Registrations\Admin\EventRegistrationsListPage;
use CivicPlatform\Modules\Users\Repository\ContactRepository;
use CivicPlatform\Repositories\ElectoralAreaRepository;
use CivicPlatform\Services\ActivityService;
use CivicPlatform\Services\ContactService;

/**
 * Bootstraps the Events module.
 */
class EventsModule
{
    /**
     * WordPress database adapter.
     *
     * @var \wpdb
     */
    private \wpdb $wpdb;

    /**
     * @param \wpdb $wpdb WordPress database adapter.
     */
    public function __construct(\wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
    }

    /**
     * Register module integrations.
     *
     * @return void
     */
    public function register(): void
    {
        $admin = new EventsAdmin(
            $this->createListPage(),
            $this->createEditPage(),
            $this->createFieldsListPage(),
            $this->createFieldEditPage(),
            $this->createRegistrationsListPage(),
            $this->createRegistrationDetailPage()
        );
        $admin->register();

        $shortcode = new EventListShortcode(
            new EventRepository($this->wpdb),
            new DateHelper()
        );
        $shortcode->register();

        $detailShortcode = new EventDetailShortcode(
            new EventRepository($this->wpdb),
            new DateHelper(),
            $this->createRegistrationForm()
        );
        $detailShortcode->register();
    }

    /**
     * Create the events listing page.
     *
     * @return EventsListPage
     */
    private function createListPage(): EventsListPage
    {
        return new EventsListPage(
            new EventRepository($this->wpdb),
            new DateHelper()
        );
    }

    /**
     * Create the event add/edit page.
     *
     * @return EventEditPage
     */
    private function createEditPage(): EventEditPage
    {
        return new EventEditPage(
            new EventRepository($this->wpdb),
            new DateHelper()
        );
    }

    /**
     * Create the event fields listing page.
     *
     * @return EventFieldsListPage
     */
    private function createFieldsListPage(): EventFieldsListPage
    {
        return new EventFieldsListPage(
            new EventFieldRepository($this->wpdb),
            new EventRepository($this->wpdb)
        );
    }

    /**
     * Create the event field add/edit page.
     *
     * @return EventFieldEditPage
     */
    private function createFieldEditPage(): EventFieldEditPage
    {
        return new EventFieldEditPage(
            new EventFieldRepository($this->wpdb),
            new EventRepository($this->wpdb)
        );
    }

    /**
     * Create the event registrations listing page.
     *
     * @return EventRegistrationsListPage
     */
    private function createRegistrationsListPage(): EventRegistrationsListPage
    {
        return new EventRegistrationsListPage(
            new EventRegistrationRepository($this->wpdb),
            new EventRepository($this->wpdb),
            new DateHelper()
        );
    }

    /**
     * Create the event registration detail page.
     *
     * @return EventRegistrationDetailPage
     */
    private function createRegistrationDetailPage(): EventRegistrationDetailPage
    {
        return new EventRegistrationDetailPage(
            new EventRegistrationRepository($this->wpdb),
            new EventRepository($this->wpdb),
            new EventFieldRepository($this->wpdb),
            new DateHelper()
        );
    }

    /**
     * Create the public event registration form.
     *
     * @return EventRegistrationForm
     */
    private function createRegistrationForm(): EventRegistrationForm
    {
        return new EventRegistrationForm(
            $this->createRegistrationService(),
            new ElectoralAreaRepository($this->wpdb)
        );
    }

    /**
     * Create the public event registration service.
     *
     * @return EventRegistrationService
     */
    private function createRegistrationService(): EventRegistrationService
    {
        return new EventRegistrationService(
            new EventRegistrationRepository($this->wpdb),
            new EventRepository($this->wpdb),
            new ContactService(new ContactRepository($this->wpdb)),
            new ActivityService(new ActivityRepository($this->wpdb))
        );
    }
}
