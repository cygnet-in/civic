<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Schedules\Admin;

use CivicPlatform\Helpers\AdminMenuHelper;

/**
 * Registers admin pages for the Schedules module.
 */
class SchedulesAdmin
{
    /**
     * Required capability for schedule administration.
     */
    private const CAPABILITY = 'manage_civic_schedules';

    /**
     * Parent Civic Platform menu slug.
     */
    private const PARENT_SLUG = 'civic-schedules';

    /**
     * Schedule list page slug.
     */
    private const LIST_SLUG = 'civic-schedules';

    /**
     * Schedule edit page slug.
     */
    private const EDIT_SLUG = 'civic-schedule-edit';

    /**
     * Schedule list page.
     *
     * @var SchedulesListPage
     */
    private SchedulesListPage $listPage;

    /**
     * Schedule edit page.
     *
     * @var ScheduleEditPage
     */
    private ScheduleEditPage $editPage;

    /**
     * @param SchedulesListPage $listPage Schedule list page.
     * @param ScheduleEditPage $editPage Schedule edit page.
     */
    public function __construct(SchedulesListPage $listPage, ScheduleEditPage $editPage)
    {
        $this->listPage = $listPage;
        $this->editPage = $editPage;
    }

    /**
     * Register WordPress admin hooks.
     *
     * @return void
     */
    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenus']);
        add_action('admin_menu', [$this, 'hideInternalMenuPages'], 999);
    }

    /**
     * Register Schedules admin menu pages.
     *
     * @return void
     */
    public function registerMenus(): void
    {
        add_menu_page(
            __('Schedules', 'civic-engagement'),
            __('Schedules', 'civic-engagement'),
            self::CAPABILITY,
            self::LIST_SLUG,
            [$this, 'renderListPage'],
            'dashicons-clock',
            33
        );

        add_submenu_page(
            self::PARENT_SLUG,
            __('Schedules', 'civic-engagement'),
            __('Schedules', 'civic-engagement'),
            self::CAPABILITY,
            self::LIST_SLUG,
            [$this, 'renderListPage']
        );

        add_submenu_page(
            ' ',
            __('Add Schedule', 'civic-engagement'),
            __('Add Schedule', 'civic-engagement'),
            self::CAPABILITY,
            self::EDIT_SLUG,
            [$this, 'renderEditPage']
        );
    }

    /**
     * Hide internal admin pages after all menu registration is complete.
     *
     * @return void
     */
    public function hideInternalMenuPages(): void
    {
        AdminMenuHelper::hideSubmenuPages(
            self::PARENT_SLUG,
            [
                self::EDIT_SLUG,
            ]
        );
    }

    /**
     * Render the schedule listing page.
     *
     * @return void
     */
    public function renderListPage(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $this->listPage->render();
    }

    /**
     * Render the schedule add/edit/view page.
     *
     * @return void
     */
    public function renderEditPage(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $this->editPage->render();
    }
}
