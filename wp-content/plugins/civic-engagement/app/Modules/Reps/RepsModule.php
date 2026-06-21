<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Reps;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Modules\Activities\Repository\ActivityRepository;
use CivicPlatform\Modules\Reps\Admin\RepDetailPage;
use CivicPlatform\Modules\Reps\Admin\RepsAdmin;
use CivicPlatform\Modules\Reps\Admin\RepsListPage;
use CivicPlatform\Modules\Reps\Frontend\RepFormController;
use CivicPlatform\Modules\Reps\Frontend\RepDetailShortcode;
use CivicPlatform\Modules\Reps\Frontend\RepsShortcodes;
use CivicPlatform\Modules\Reps\Repository\RepRepository;
use CivicPlatform\Modules\Users\Repository\ContactRepository;
use CivicPlatform\Repositories\ElectoralAreaRepository;
use CivicPlatform\Services\ActivityService;
use CivicPlatform\Services\ContactService;
use CivicPlatform\Services\RepService;

/**
 * Bootstraps the Reps module.
 *
 * This class wires Reps dependencies and registers the module's frontend
 * shortcode handlers. Workflow behavior remains inside services.
 */
class RepsModule
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
        $shortcodes = new RepsShortcodes(
            $this->createFormController(),
            new RepDetailShortcode($this->createRepService())
        );
        $shortcodes->register();

        $admin = new RepsAdmin($this->createListPage(), $this->createDetailPage());
        $admin->register();
    }

    /**
     * Create the representation form controller.
     *
     * @return RepFormController
     */
    private function createFormController(): RepFormController
    {
        return new RepFormController(
            $this->createRepService(),
            new ElectoralAreaRepository($this->wpdb)
        );
    }

    /**
     * Create the representations admin list page.
     *
     * @return RepsListPage
     */
    private function createListPage(): RepsListPage
    {
        return new RepsListPage(new RepRepository($this->wpdb), new DateHelper());
    }

    /**
     * Create the representation admin detail page.
     *
     * @return RepDetailPage
     */
    private function createDetailPage(): RepDetailPage
    {
        $services = $this->createRepWorkflowServices();

        return new RepDetailPage(
            $services['reps'],
            $services['activities'],
            new DateHelper()
        );
    }

    /**
     * Create the representation workflow service.
     *
     * @return RepService
     */
    private function createRepService(): RepService
    {
        return $this->createRepWorkflowServices()['reps'];
    }

    /**
     * Create shared Reps workflow services.
     *
     * @return array{reps: RepService, contacts: ContactService, activities: ActivityService}
     */
    private function createRepWorkflowServices(): array
    {
        $repRepository = new RepRepository($this->wpdb);
        $contactRepository = new ContactRepository($this->wpdb);
        $activityRepository = new ActivityRepository($this->wpdb);

        $contactService = new ContactService($contactRepository);
        $activityService = new ActivityService($activityRepository);

        return [
            'reps' => new RepService($repRepository, $contactService, $activityService),
            'contacts' => $contactService,
            'activities' => $activityService,
        ];
    }
}
