<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Dashboard;

use CivicPlatform\Modules\Dashboard\Frontend\StatisticsShortcode;
use CivicPlatform\Modules\Dashboard\Services\PublicStatisticsService;
use CivicPlatform\Modules\Events\Repository\EventRegistrationRepository;
use CivicPlatform\Modules\Events\Repository\EventRepository;
use CivicPlatform\Modules\Reps\Repository\RepRepository;
use CivicPlatform\Modules\Schedules\Repository\ScheduleRepository;
use CivicPlatform\Modules\Threads\Repository\ThreadRepository;
use CivicPlatform\Modules\Threads\Repository\ThreadResponseRepository;
use CivicPlatform\Modules\Users\Repository\ContactRepository;

/** Bootstraps the Civic Dashboard administration experience. */
class DashboardModule
{
    private \wpdb $wpdb;

    public function __construct(\wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
    }

    public function register(): void
    {
        $statisticsShortcode = new StatisticsShortcode(
            new PublicStatisticsService(
                new RepRepository($this->wpdb),
                new ThreadRepository($this->wpdb),
                new ThreadResponseRepository($this->wpdb),
                new EventRepository($this->wpdb)
            )
        );
        $statisticsShortcode->register();

        $page = new DashboardPage(
            new RepRepository($this->wpdb),
            new ThreadRepository($this->wpdb),
            new ThreadResponseRepository($this->wpdb),
            new EventRepository($this->wpdb),
            new EventRegistrationRepository($this->wpdb),
            new ScheduleRepository($this->wpdb),
            new ContactRepository($this->wpdb)
        );
        (new DashboardAdmin($page))->register();
    }
}
