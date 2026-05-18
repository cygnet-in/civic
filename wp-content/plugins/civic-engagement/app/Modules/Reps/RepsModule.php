<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Reps;

use CivicPlatform\Modules\Activities\Repository\ActivityRepository;
use CivicPlatform\Modules\Reps\Frontend\RepFormController;
use CivicPlatform\Modules\Reps\Frontend\RepsShortcodes;
use CivicPlatform\Modules\Reps\Repository\RepRepository;
use CivicPlatform\Modules\Users\Repository\ContactRepository;
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
        $shortcodes = new RepsShortcodes($this->createFormController());
        $shortcodes->register();
    }

    /**
     * Create the representation form controller.
     *
     * @return RepFormController
     */
    private function createFormController(): RepFormController
    {
        return new RepFormController($this->createRepService());
    }

    /**
     * Create the representation workflow service.
     *
     * @return RepService
     */
    private function createRepService(): RepService
    {
        $repRepository = new RepRepository($this->wpdb);
        $contactRepository = new ContactRepository($this->wpdb);
        $activityRepository = new ActivityRepository($this->wpdb);

        $contactService = new ContactService($contactRepository);
        $activityService = new ActivityService($activityRepository);

        return new RepService($repRepository, $contactService, $activityService);
    }
}
