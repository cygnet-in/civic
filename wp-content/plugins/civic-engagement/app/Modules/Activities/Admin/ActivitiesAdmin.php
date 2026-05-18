<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Activities\Admin;

/**
 * Registers admin pages for the Activities module.
 *
 * Menu registration is separated from page rendering. Protected admin screens
 * use explicit capability checks.
 */
class ActivitiesAdmin
{
    /**
     * Required capability for activity history.
     */
    private const CAPABILITY = 'view_civic_activities';

    /**
     * Parent Civic Platform menu slug.
     */
    private const PARENT_SLUG = 'civic-platform';

    /**
     * Activities page slug.
     */
    private const PAGE_SLUG = 'civic-activities';

    /**
     * Activities list page.
     *
     * @var ActivitiesListPage
     */
    private ActivitiesListPage $listPage;

    /**
     * @param ActivitiesListPage $listPage Activities list page.
     */
    public function __construct(ActivitiesListPage $listPage)
    {
        $this->listPage = $listPage;
    }

    /**
     * Register WordPress admin hooks.
     *
     * @return void
     */
    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenus']);
    }

    /**
     * Register the Activities submenu.
     *
     * @return void
     */
    public function registerMenus(): void
    {
        add_submenu_page(
            self::PARENT_SLUG,
            __('Activities', 'civic-engagement'),
            __('Activities', 'civic-engagement'),
            self::CAPABILITY,
            self::PAGE_SLUG,
            [$this, 'renderActivitiesPage']
        );
    }

    /**
     * Render the Activities admin page.
     *
     * @return void
     */
    public function renderActivitiesPage(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $this->listPage->render();
    }
}
