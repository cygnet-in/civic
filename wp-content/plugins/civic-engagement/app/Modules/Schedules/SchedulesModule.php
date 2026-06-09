<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Schedules;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Modules\Schedules\Admin\ScheduleEditPage;
use CivicPlatform\Modules\Schedules\Admin\SchedulesAdmin;
use CivicPlatform\Modules\Schedules\Admin\SchedulesListPage;
use CivicPlatform\Modules\Schedules\Repository\ScheduleRepository;
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
        $admin = new SchedulesAdmin(
            $this->createListPage(),
            $this->createEditPage()
        );
        $admin->register();
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
            new DateHelper()
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
            new ScheduleService($repository),
            new DateHelper()
        );
    }
}
