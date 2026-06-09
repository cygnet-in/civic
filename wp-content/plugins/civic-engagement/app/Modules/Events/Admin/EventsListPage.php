<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Events\Admin;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Modules\Events\Repository\EventRepository;

/**
 * Renders the admin event listing.
 *
 * This page handles request sanitization and presentation only. Data access is
 * delegated to EventRepository.
 */
class EventsListPage
{
    /**
     * Required capability for viewing events.
     */
    private const CAPABILITY = 'manage_civic_events';

    /**
     * Admin list page slug.
     */
    private const PAGE_SLUG = 'civic-events';

    /**
     * Event repository.
     *
     * @var EventRepository
     */
    private EventRepository $events;

    /**
     * Date helper.
     *
     * @var DateHelper
     */
    private DateHelper $dates;

    /**
     * @param EventRepository $events Event repository.
     * @param DateHelper $dates Date helper.
     */
    public function __construct(EventRepository $events, DateHelper $dates)
    {
        $this->events = $events;
        $this->dates = $dates;
    }

    /**
     * Render the paginated event listing.
     *
     * @return void
     */
    public function render(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $search = $this->searchKeyword();
        $page = $this->currentPage();
        $result = $this->getResult($search, $page);
        $items = isset($result['items']) && is_array($result['items']) ? $result['items'] : [];
        $totalPages = isset($result['total_pages']) ? (int) $result['total_pages'] : 1;

        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">' . esc_html__('Events', 'civic-engagement') . '</h1>';
        echo ' <a href="' . esc_url($this->addUrl()) . '" class="page-title-action">' . esc_html__('Add New', 'civic-engagement') . '</a>';
        $this->renderNotices();
        $this->renderSearchForm($search);
        $this->renderTable($items);
        $this->renderPagination($page, $totalPages, $search);
        echo '</div>';
    }

    /**
     * Get paginated events, optionally searched by keyword.
     *
     * @param string $search Search keyword.
     * @param int $page Current page.
     * @return array<string, mixed> Paginated repository result.
     */
    private function getResult(string $search, int $page): array
    {
        $args = [
            'page' => $page,
            'per_page' => 20,
            'orderby' => 'start_date',
            'order' => 'ASC',
        ];

        if ('' !== $search) {
            return $this->events->search($search, $args);
        }

        return $this->events->getPaginated($args);
    }

    /**
     * Render success notices from redirected saves.
     *
     * @return void
     */
    private function renderNotices(): void
    {
        if ($this->flag('created')) {
            echo '<div class="notice notice-success"><p>' . esc_html__('Event created successfully.', 'civic-engagement') . '</p></div>';
        }

        if ($this->flag('updated')) {
            echo '<div class="notice notice-success"><p>' . esc_html__('Event updated successfully.', 'civic-engagement') . '</p></div>';
        }
    }

    /**
     * Render the search form.
     *
     * @param string $search Search keyword.
     * @return void
     */
    private function renderSearchForm(string $search): void
    {
        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="' . esc_attr(self::PAGE_SLUG) . '">';
        echo '<p class="search-box">';
        echo '<label class="screen-reader-text" for="civic-events-search-input">' . esc_html__('Search Events', 'civic-engagement') . '</label>';
        echo '<input type="search" id="civic-events-search-input" name="s" value="' . esc_attr($search) . '">';
        submit_button(__('Search Events', 'civic-engagement'), '', '', false);
        echo '</p>';
        echo '</form>';
    }

    /**
     * Render the events table.
     *
     * @param array<int, array<string, mixed>> $items Event rows.
     * @return void
     */
    private function renderTable(array $items): void
    {
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th scope="col">' . esc_html__('ID', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Title', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Slug', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Location', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Status', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Public', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Registrations', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Start Date', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('End Date', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Actions', 'civic-engagement') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        if (empty($items)) {
            echo '<tr><td colspan="10">' . esc_html__('No events found.', 'civic-engagement') . '</td></tr>';
        }

        foreach ($items as $item) {
            $this->renderRow($item);
        }

        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Render a single event row.
     *
     * @param array<string, mixed> $item Event row.
     * @return void
     */
    private function renderRow(array $item): void
    {
        $id = isset($item['id']) ? (int) $item['id'] : 0;

        echo '<tr>';
        echo '<td>' . esc_html((string) $id) . '</td>';
        echo '<td>' . esc_html((string) ($item['title'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($item['slug'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($item['location'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($item['status'] ?? '')) . '</td>';
        echo '<td>' . esc_html(!empty($item['is_public']) ? __('Yes', 'civic-engagement') : __('No', 'civic-engagement')) . '</td>';
        echo '<td>' . esc_html(!empty($item['registration_enabled']) ? __('Enabled', 'civic-engagement') : __('Disabled', 'civic-engagement')) . '</td>';
        echo '<td>' . esc_html($this->dates->formatDateTime($item['start_date'] ?? null)) . '</td>';
        echo '<td>' . esc_html($this->dates->formatDateTime($item['end_date'] ?? null)) . '</td>';
        echo '<td><a href="' . esc_url($this->viewUrl($id)) . '">' . esc_html__('View', 'civic-engagement') . '</a> | <a href="' . esc_url($this->editUrl($id)) . '">' . esc_html__('Edit', 'civic-engagement') . '</a> | <a href="' . esc_url($this->fieldsUrl($id)) . '">' . esc_html__('Fields', 'civic-engagement') . '</a> | <a href="' . esc_url($this->registrationsUrl($id)) . '">' . esc_html__('Registrations', 'civic-engagement') . '</a></td>';
        echo '</tr>';
    }

    /**
     * Render pagination links.
     *
     * @param int $page Current page.
     * @param int $totalPages Total pages.
     * @param string $search Search keyword.
     * @return void
     */
    private function renderPagination(int $page, int $totalPages, string $search): void
    {
        if ($totalPages <= 1) {
            return;
        }

        $baseArgs = ['page' => self::PAGE_SLUG];

        if ('' !== $search) {
            $baseArgs['s'] = $search;
        }

        echo '<div class="tablenav bottom">';
        echo '<div class="tablenav-pages">';
        echo '<span class="displaying-num">' . esc_html(sprintf(_n('%s page', '%s pages', $totalPages, 'civic-engagement'), number_format_i18n($totalPages))) . '</span>';

        if ($page > 1) {
            echo '<a class="button" href="' . esc_url(add_query_arg(array_merge($baseArgs, ['paged' => $page - 1]), admin_url('admin.php'))) . '">' . esc_html__('Previous', 'civic-engagement') . '</a> ';
        }

        echo '<span class="paging-input">' . esc_html((string) $page) . ' / ' . esc_html((string) $totalPages) . '</span>';

        if ($page < $totalPages) {
            echo ' <a class="button" href="' . esc_url(add_query_arg(array_merge($baseArgs, ['paged' => $page + 1]), admin_url('admin.php'))) . '">' . esc_html__('Next', 'civic-engagement') . '</a>';
        }

        echo '</div>';
        echo '</div>';
    }

    /**
     * Build add event URL.
     *
     * @return string Add URL.
     */
    private function addUrl(): string
    {
        return add_query_arg(['page' => 'civic-event-edit'], admin_url('admin.php'));
    }

    /**
     * Build event view URL.
     *
     * @param int $id Event ID.
     * @return string View URL.
     */
    private function viewUrl(int $id): string
    {
        return add_query_arg(
            [
                'page' => 'civic-event-edit',
                'event_id' => $id,
                'mode' => 'view',
            ],
            admin_url('admin.php')
        );
    }

    /**
     * Build event edit URL.
     *
     * @param int $id Event ID.
     * @return string Edit URL.
     */
    private function editUrl(int $id): string
    {
        return add_query_arg(
            [
                'page' => 'civic-event-edit',
                'event_id' => $id,
            ],
            admin_url('admin.php')
        );
    }

    /**
     * Build contextual registrations URL for an event.
     *
     * @param int $id Event ID.
     * @return string Registrations URL.
     */
    private function registrationsUrl(int $id): string
    {
        return add_query_arg(
            [
                'page' => 'civic-event-registrations',
                'event_id' => $id,
            ],
            admin_url('admin.php')
        );
    }

    /**
     * Build contextual fields URL for an event.
     *
     * @param int $id Event ID.
     * @return string Fields URL.
     */
    private function fieldsUrl(int $id): string
    {
        return add_query_arg(
            [
                'page' => 'civic-event-fields',
                'event_id' => $id,
            ],
            admin_url('admin.php')
        );
    }

    /**
     * Get sanitized search keyword.
     *
     * @return string Search keyword.
     */
    private function searchKeyword(): string
    {
        if (!isset($_GET['s'])) {
            return '';
        }

        $search = wp_unslash($_GET['s']);

        if (is_array($search) || is_object($search)) {
            return '';
        }

        return sanitize_text_field((string) $search);
    }

    /**
     * Get sanitized current page number.
     *
     * @return int Current page.
     */
    private function currentPage(): int
    {
        if (!isset($_GET['paged'])) {
            return 1;
        }

        $page = wp_unslash($_GET['paged']);

        if (is_array($page) || is_object($page)) {
            return 1;
        }

        return max(1, absint($page));
    }

    /**
     * Check a redirected boolean flag.
     *
     * @param string $key Query arg key.
     * @return bool True when flag is present.
     */
    private function flag(string $key): bool
    {
        if (!isset($_GET[$key])) {
            return false;
        }

        $value = wp_unslash($_GET[$key]);

        if (is_array($value) || is_object($value)) {
            return false;
        }

        return '1' === (string) $value;
    }
}
