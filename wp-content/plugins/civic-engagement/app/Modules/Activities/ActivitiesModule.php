<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Activities;

use CivicPlatform\Modules\Activities\Admin\ActivitiesAdmin;
use CivicPlatform\Modules\Activities\Admin\ActivitiesListPage;
use CivicPlatform\Modules\Activities\Repository\ActivityRepository;

/**
 * Bootstraps the Activities module.
 *
 * This class wires Activities admin dependencies and registers module
 * integrations. Activity persistence stays inside repositories/services.
 */
class ActivitiesModule
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
        $admin = new ActivitiesAdmin($this->createListPage());
        $admin->register();
    }

    /**
     * Create the activities admin list page.
     *
     * @return ActivitiesListPage
     */
    private function createListPage(): ActivitiesListPage
    {
        return new ActivitiesListPage(new ActivityRepository($this->wpdb));
    }
}
