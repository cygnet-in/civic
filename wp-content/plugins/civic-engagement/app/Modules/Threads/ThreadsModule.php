<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Threads;

use CivicPlatform\Modules\Threads\Admin\ThreadCreatePage;
use CivicPlatform\Modules\Threads\Admin\ThreadsAdmin;
use CivicPlatform\Modules\Threads\Repository\ThreadRepository;

/**
 * Bootstraps the Threads module.
 */
class ThreadsModule
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
        $admin = new ThreadsAdmin($this->createCreatePage());
        $admin->register();
    }

    /**
     * Create the thread creation page.
     *
     * @return ThreadCreatePage
     */
    private function createCreatePage(): ThreadCreatePage
    {
        return new ThreadCreatePage(
            new ThreadRepository($this->wpdb)
        );
    }
}
