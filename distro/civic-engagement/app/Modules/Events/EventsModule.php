<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Events;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Helpers\FrontendPageResolver;
use CivicPlatform\Modules\Events\Admin\EventEditPage;
use CivicPlatform\Modules\Events\Admin\EventFieldEditPage;
use CivicPlatform\Modules\Events\Admin\EventFieldsListPage;
use CivicPlatform\Modules\Events\Admin\EventsAdmin;
use CivicPlatform\Modules\Events\Admin\EventsListPage;
use CivicPlatform\Modules\Events\Frontend\EventArchiveShortcode;
use CivicPlatform\Modules\Events\Frontend\EventDetailShortcode;
use CivicPlatform\Modules\Events\Frontend\EventListShortcode;
use CivicPlatform\Modules\Events\Frontend\LatestEventsWidget;
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
use CivicPlatform\Repositories\MediaRepository;
use CivicPlatform\Repositories\ShortUrlRepository;
use CivicPlatform\Services\ActivityService;
use CivicPlatform\Services\CaptchaService;
use CivicPlatform\Services\ContactService;
use CivicPlatform\Services\Export\ExportManager;
use CivicPlatform\Services\MediaService;
use CivicPlatform\Services\ShortUrlService;

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
        add_action('widgets_init', [$this, 'registerWidgets']);

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
            new DateHelper(),
            $this->createMediaService()
        );
        $shortcode->register();

        $archiveShortcode = new EventArchiveShortcode(
            new EventRepository($this->wpdb),
            new DateHelper(),
            $this->createMediaService()
        );
        $archiveShortcode->register();

        $detailShortcode = new EventDetailShortcode(
            new EventRepository($this->wpdb),
            new DateHelper(),
            $this->createRegistrationForm(),
            $this->createMediaService()
        );
        $detailShortcode->register();
    }

    /**
     * Register frontend widgets.
     *
     * @return void
     */
    public function registerWidgets(): void
    {
        register_widget(
            new LatestEventsWidget(
                new EventRepository($this->wpdb),
                new DateHelper(),
                new FrontendPageResolver()
            )
        );
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
            new DateHelper(),
            new EventRegistrationRepository($this->wpdb),
            $this->createMediaService(),
            new ExportManager()
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
            new DateHelper(),
            $this->createMediaService(),
            $this->createShortUrlService()
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
            new DateHelper(),
            new ExportManager()
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
            new ElectoralAreaRepository($this->wpdb),
            new EventFieldRepository($this->wpdb),
            $this->createCaptchaService()
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

    private function createMediaService(): MediaService
    {
        return new MediaService(new MediaRepository($this->wpdb));
    }

    private function createCaptchaService(): CaptchaService
    {
        return new CaptchaService();
    }

    private function createShortUrlService(): ShortUrlService
    {
        return new ShortUrlService(new ShortUrlRepository($this->wpdb));
    }
}
