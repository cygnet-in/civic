<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Search;

use CivicPlatform\Modules\Events\Repository\EventRepository;
use CivicPlatform\Modules\Schedules\Repository\ScheduleRepository;
use CivicPlatform\Modules\Search\Frontend\SearchFormShortcode;
use CivicPlatform\Modules\Search\Frontend\SearchResultsShortcode;
use CivicPlatform\Modules\Threads\Repository\ThreadRepository;
use CivicPlatform\Services\CivicSettingsService;
use CivicPlatform\Services\SearchService;

/**
 * Registers shared public Civic search shortcodes.
 */
class SearchModule
{
    private \wpdb $wpdb;

    public function __construct(\wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
    }

    public function register(): void
    {
        $service = new SearchService(
            new ThreadRepository($this->wpdb),
            new EventRepository($this->wpdb),
            new ScheduleRepository($this->wpdb)
        );

        (new SearchFormShortcode(new CivicSettingsService()))->register();
        (new SearchResultsShortcode($service))->register();
    }
}
