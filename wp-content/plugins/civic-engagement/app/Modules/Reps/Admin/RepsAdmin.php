<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Reps\Admin;

/**
 * Registers admin pages for the Reps module.
 *
 * Menu registration is kept separate from page rendering. All protected admin
 * screens use explicit capability checks.
 */
class RepsAdmin
{
    /**
     * Required capability for Reps administration.
     */
    private const CAPABILITY = 'manage_civic_reps';

    /**
     * Main admin menu slug.
     */
    private const MENU_SLUG = 'civic-platform';

    /**
     * Detail page slug.
     */
    private const DETAIL_SLUG = 'civic-rep-view';

    /**
     * Representations list page.
     *
     * @var RepsListPage
     */
    private RepsListPage $listPage;

    /**
     * Representation detail page.
     *
     * @var RepDetailPage
     */
    private RepDetailPage $detailPage;

    /**
     * @param RepsListPage $listPage Representations list page.
     * @param RepDetailPage $detailPage Representation detail page.
     */
    public function __construct(RepsListPage $listPage, RepDetailPage $detailPage)
    {
        $this->listPage = $listPage;
        $this->detailPage = $detailPage;
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
     * Register Civic Platform admin menu pages.
     *
     * @return void
     */
    public function registerMenus(): void
    {
        add_menu_page(
            __('Representations', 'civic-engagement'),
            __('Civic Platform', 'civic-engagement'),
            self::CAPABILITY,
            self::MENU_SLUG,
            [$this, 'renderRepresentationsPage'],
            'dashicons-megaphone',
            30
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Representations', 'civic-engagement'),
            __('Representations', 'civic-engagement'),
            self::CAPABILITY,
            self::MENU_SLUG,
            [$this, 'renderRepresentationsPage']
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('View Representation', 'civic-engagement'),
            __('View Representation', 'civic-engagement'),
            self::CAPABILITY,
            self::DETAIL_SLUG,
            [$this, 'renderRepresentationDetailPage']
        );
    }

    /**
     * Render the Representations admin page.
     *
     * @return void
     */
    public function renderRepresentationsPage(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $this->listPage->render();
    }

    /**
     * Render the Representation detail admin page.
     *
     * @return void
     */
    public function renderRepresentationDetailPage(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $this->detailPage->render();
    }
}
