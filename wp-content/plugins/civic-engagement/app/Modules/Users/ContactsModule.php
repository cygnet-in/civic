<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Users;

use CivicPlatform\Modules\Users\Admin\ContactsAdmin;
use CivicPlatform\Modules\Users\Admin\ContactsListPage;
use CivicPlatform\Modules\Users\Repository\ContactRepository;
use CivicPlatform\Services\ContactService;
use CivicPlatform\Services\Export\ExportManager;

/**
 * Bootstraps contact administration.
 */
class ContactsModule
{
    private \wpdb $wpdb;

    public function __construct(\wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
    }

    /**
     * Register contact administration.
     *
     * @return void
     */
    public function register(): void
    {
        $admin = new ContactsAdmin(
            new ContactsListPage(new ContactService(new ContactRepository($this->wpdb)), new ExportManager())
        );
        $admin->register();
    }
}
