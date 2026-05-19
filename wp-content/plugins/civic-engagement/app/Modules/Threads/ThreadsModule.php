<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Threads;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Modules\Threads\Admin\ThreadCreatePage;
use CivicPlatform\Modules\Threads\Admin\ThreadDetailPage;
use CivicPlatform\Modules\Threads\Admin\ThreadEditPage;
use CivicPlatform\Modules\Threads\Admin\ThreadsAdmin;
use CivicPlatform\Modules\Threads\Admin\ThreadsListPage;
use CivicPlatform\Modules\Threads\Frontend\ThreadDetailShortcode;
use CivicPlatform\Modules\Threads\Frontend\ThreadsListShortcode;
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
        $admin = new ThreadsAdmin(
            $this->createListPage(),
            $this->createDetailPage(),
            $this->createEditPage(),
            $this->createCreatePage()
        );
        $admin->register();

        $shortcode = new ThreadsListShortcode(new ThreadRepository($this->wpdb), new DateHelper());
        $shortcode->register();

        $detailShortcode = new ThreadDetailShortcode(new ThreadRepository($this->wpdb), new DateHelper());
        $detailShortcode->register();
    }

    /**
     * Create the thread listing page.
     *
     * @return ThreadsListPage
     */
    private function createListPage(): ThreadsListPage
    {
        return new ThreadsListPage(new ThreadRepository($this->wpdb), new DateHelper());
    }

    /**
     * Create the thread detail page.
     *
     * @return ThreadDetailPage
     */
    private function createDetailPage(): ThreadDetailPage
    {
        return new ThreadDetailPage(new ThreadRepository($this->wpdb), new DateHelper());
    }

    /**
     * Create the thread edit page.
     *
     * @return ThreadEditPage
     */
    private function createEditPage(): ThreadEditPage
    {
        return new ThreadEditPage(new ThreadRepository($this->wpdb));
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
