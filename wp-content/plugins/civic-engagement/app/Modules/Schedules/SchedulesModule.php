<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Schedules;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Helpers\FrontendPageResolver;
use CivicPlatform\Modules\Reps\Repository\RepRepository;
use CivicPlatform\Modules\Schedules\Admin\ScheduleEditPage;
use CivicPlatform\Modules\Schedules\Admin\SchedulesAdmin;
use CivicPlatform\Modules\Schedules\Admin\SchedulesListPage;
use CivicPlatform\Modules\Schedules\Frontend\ScheduleArchiveShortcode;
use CivicPlatform\Modules\Schedules\Frontend\ScheduleDetailShortcode;
use CivicPlatform\Modules\Schedules\Frontend\ScheduleListShortcode;
use CivicPlatform\Modules\Schedules\Frontend\UpcomingSchedulesWidget;
use CivicPlatform\Modules\Schedules\Repository\ScheduleNoteRepository;
use CivicPlatform\Modules\Schedules\Repository\ScheduleRepository;
use CivicPlatform\Repositories\MediaRepository;
use CivicPlatform\Repositories\ShortUrlRepository;
use CivicPlatform\Services\Export\ExportManager;
use CivicPlatform\Services\MediaService;
use CivicPlatform\Services\ShortUrlService;
use CivicPlatform\Modules\Schedules\Services\ScheduleService;

/**
 * Bootstraps the Schedules module.
 */
class SchedulesModule
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

        $admin = new SchedulesAdmin(
            $this->createListPage(),
            $this->createEditPage()
        );
        $admin->register();

        $shortcode = new ScheduleListShortcode(
            new ScheduleRepository($this->wpdb),
            new DateHelper(),
            $this->createMediaService(),
            new ExportManager()
        );
        $shortcode->register();

        $archiveShortcode = new ScheduleArchiveShortcode(
            new ScheduleRepository($this->wpdb),
            new DateHelper(),
            $this->createMediaService()
        );
        $archiveShortcode->register();

        $detailShortcode = new ScheduleDetailShortcode(
            new ScheduleRepository($this->wpdb),
            new DateHelper(),
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
            new UpcomingSchedulesWidget(
                new ScheduleRepository($this->wpdb),
                new DateHelper(),
                new FrontendPageResolver()
            )
        );
    }

    /**
     * Create the schedules listing page.
     *
     * @return SchedulesListPage
     */
    private function createListPage(): SchedulesListPage
    {
        return new SchedulesListPage(
            new ScheduleRepository($this->wpdb),
            new DateHelper(),
            $this->createMediaService()
        );
    }

    /**
     * Create the schedule add/edit page.
     *
     * @return ScheduleEditPage
     */
    private function createEditPage(): ScheduleEditPage
    {
        $repository = new ScheduleRepository($this->wpdb);

        return new ScheduleEditPage(
            $repository,
            new ScheduleService($repository, new ScheduleNoteRepository($this->wpdb)),
            new ScheduleNoteRepository($this->wpdb),
            new DateHelper(),
            $this->createMediaService(),
            $this->createShortUrlService(),
            new RepRepository($this->wpdb)
        );
    }

    private function createMediaService(): MediaService
    {
        return new MediaService(new MediaRepository($this->wpdb));
    }

    private function createShortUrlService(): ShortUrlService
    {
        return new ShortUrlService(new ShortUrlRepository($this->wpdb));
    }
}
