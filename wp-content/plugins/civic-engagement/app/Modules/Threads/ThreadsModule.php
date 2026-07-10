<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Threads;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Helpers\FrontendPageResolver;
use CivicPlatform\Modules\Threads\Admin\ThreadCreatePage;
use CivicPlatform\Modules\Threads\Admin\ThreadDetailPage;
use CivicPlatform\Modules\Threads\Admin\ThreadEditPage;
use CivicPlatform\Modules\Threads\Admin\ThreadsAdmin;
use CivicPlatform\Modules\Threads\Admin\ThreadsListPage;
use CivicPlatform\Modules\Threads\Fields\Admin\ThreadFieldEditPage;
use CivicPlatform\Modules\Threads\Fields\Admin\ThreadFieldsListPage;
use CivicPlatform\Modules\Threads\Frontend\LatestConsultationsWidget;
use CivicPlatform\Modules\Threads\Frontend\ThreadDetailShortcode;
use CivicPlatform\Modules\Threads\Frontend\ThreadsArchiveShortcode;
use CivicPlatform\Modules\Threads\Frontend\ThreadsListShortcode;
use CivicPlatform\Modules\Threads\Repository\ThreadFieldRepository;
use CivicPlatform\Modules\Threads\Repository\ThreadRepository;
use CivicPlatform\Modules\Threads\Repository\ThreadResponseRepository;
use CivicPlatform\Modules\Threads\Responses\Admin\ThreadResponseDetailPage;
use CivicPlatform\Modules\Threads\Responses\Admin\ThreadResponsesListPage;
use CivicPlatform\Modules\Threads\Responses\Frontend\ThreadResponseForm;
use CivicPlatform\Modules\Threads\Responses\Services\ThreadResponseService;
use CivicPlatform\Modules\Activities\Repository\ActivityRepository;
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
        add_action('widgets_init', [$this, 'registerWidgets']);

        $admin = new ThreadsAdmin(
            $this->createListPage(),
            $this->createFieldsListPage(),
            $this->createFieldEditPage(),
            $this->createResponsesListPage(),
            $this->createResponseDetailPage(),
            $this->createDetailPage(),
            $this->createEditPage(),
            $this->createCreatePage()
        );
        $admin->register();

        $shortcode = new ThreadsListShortcode(new ThreadRepository($this->wpdb), new DateHelper(), $this->createMediaService());
        $shortcode->register();

        $archiveShortcode = new ThreadsArchiveShortcode(new ThreadRepository($this->wpdb), new DateHelper(), $this->createMediaService());
        $archiveShortcode->register();

        $detailShortcode = new ThreadDetailShortcode(
            new ThreadRepository($this->wpdb),
            new ThreadResponseRepository($this->wpdb),
            new ThreadFieldRepository($this->wpdb),
            new DateHelper(),
            new ThreadResponseForm(
                $this->createThreadResponseService(),
                new ThreadFieldRepository($this->wpdb),
                new ElectoralAreaRepository($this->wpdb),
                $this->createCaptchaService()
            ),
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
            new LatestConsultationsWidget(
                new ThreadRepository($this->wpdb),
                new FrontendPageResolver()
            )
        );
    }

    /**
     * Create the thread listing page.
     *
     * @return ThreadsListPage
     */
    private function createListPage(): ThreadsListPage
    {
        return new ThreadsListPage(
            new ThreadRepository($this->wpdb),
            new DateHelper(),
            new ThreadResponseRepository($this->wpdb),
            $this->createMediaService(),
            new ExportManager()
        );
    }

    /**
     * Create the thread fields listing page.
     *
     * @return ThreadFieldsListPage
     */
    private function createFieldsListPage(): ThreadFieldsListPage
    {
        return new ThreadFieldsListPage(
            new ThreadFieldRepository($this->wpdb),
            new ThreadRepository($this->wpdb)
        );
    }

    /**
     * Create the thread field edit page.
     *
     * @return ThreadFieldEditPage
     */
    private function createFieldEditPage(): ThreadFieldEditPage
    {
        return new ThreadFieldEditPage(
            new ThreadFieldRepository($this->wpdb),
            new ThreadRepository($this->wpdb)
        );
    }

    /**
     * Create the thread responses listing page.
     *
     * @return ThreadResponsesListPage
     */
    private function createResponsesListPage(): ThreadResponsesListPage
    {
        return new ThreadResponsesListPage(
            new ThreadResponseRepository($this->wpdb),
            new ThreadRepository($this->wpdb),
            new DateHelper(),
            new ExportManager()
        );
    }

    /**
     * Create the thread response detail page.
     *
     * @return ThreadResponseDetailPage
     */
    private function createResponseDetailPage(): ThreadResponseDetailPage
    {
        return new ThreadResponseDetailPage(
            new ThreadResponseRepository($this->wpdb),
            new ThreadRepository($this->wpdb),
            new ThreadFieldRepository($this->wpdb),
            new DateHelper()
        );
    }

    /**
     * Create the thread detail page.
     *
     * @return ThreadDetailPage
     */
    private function createDetailPage(): ThreadDetailPage
    {
        return new ThreadDetailPage(new ThreadRepository($this->wpdb), new DateHelper(), $this->createMediaService());
    }

    /**
     * Create the thread edit page.
     *
     * @return ThreadEditPage
     */
    private function createEditPage(): ThreadEditPage
    {
        return new ThreadEditPage(new ThreadRepository($this->wpdb), $this->createMediaService(), $this->createShortUrlService());
    }

    /**
     * Create the thread creation page.
     *
     * @return ThreadCreatePage
     */
    private function createCreatePage(): ThreadCreatePage
    {
        return new ThreadCreatePage(
            new ThreadRepository($this->wpdb),
            $this->createShortUrlService()
        );
    }

    /**
     * Create the public thread response submission service.
     *
     * @return ThreadResponseService
     */
    private function createThreadResponseService(): ThreadResponseService
    {
        return new ThreadResponseService(
            new ThreadResponseRepository($this->wpdb),
            new ThreadRepository($this->wpdb),
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
