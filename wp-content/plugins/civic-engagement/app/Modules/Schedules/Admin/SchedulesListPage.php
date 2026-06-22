<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Schedules\Admin;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Helpers\StatusLabelHelper;
use CivicPlatform\Modules\Schedules\Repository\ScheduleRepository;
use CivicPlatform\Services\MediaService;

/**
 * Renders the admin schedule listing.
 *
 * This page handles request sanitization and presentation only. Data access is
 * delegated to ScheduleRepository.
 */
class SchedulesListPage
{
    /**
     * Required capability for viewing schedules.
     */
    private const CAPABILITY = 'manage_civic_schedules';

    /**
     * Admin list page slug.
     */
    private const PAGE_SLUG = 'civic-schedules';

    /**
     * Schedule repository.
     *
     * @var ScheduleRepository
     */
    private ScheduleRepository $schedules;

    /**
     * Date helper.
     *
     * @var DateHelper
     */
    private DateHelper $dates;
    private MediaService $media;

    /**
     * @param ScheduleRepository $schedules Schedule repository.
     * @param DateHelper $dates Date helper.
     */
    public function __construct(ScheduleRepository $schedules, DateHelper $dates, MediaService $media)
    {
        $this->schedules = $schedules;
        $this->dates = $dates;
        $this->media = $media;
    }

    /**
     * Render the paginated schedule listing.
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
        echo '<h1 class="wp-heading-inline">' . esc_html__('Schedules', 'civic-engagement') . '</h1>';
        echo ' <a href="' . esc_url($this->addUrl()) . '" class="page-title-action">' . esc_html__('Add New', 'civic-engagement') . '</a>';
        $this->renderNotices();
        $this->renderSearchForm($search);
        $ids = array_map(static fn(array $item): int => (int) ($item['id'] ?? 0), $items);
        $this->renderTable($items, $this->media->getCountsByEntityIds('schedule', $ids));
        $this->renderPagination($page, $totalPages, $search);
        echo '</div>';
    }

    /**
     * Get paginated schedules, optionally searched by keyword.
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
        ];

        if ('' !== $search) {
            return $this->schedules->search($search, $args);
        }

        return $this->schedules->getPaginated($args);
    }

    /**
     * Render success notices from redirected saves.
     *
     * @return void
     */
    private function renderNotices(): void
    {
        if ($this->flag('created')) {
            echo '<div class="notice notice-success"><p>' . esc_html__('Schedule created successfully.', 'civic-engagement') . '</p></div>';
        }

        if ($this->flag('updated')) {
            echo '<div class="notice notice-success"><p>' . esc_html__('Schedule updated successfully.', 'civic-engagement') . '</p></div>';
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
        echo '<label class="screen-reader-text" for="civic-schedules-search-input">' . esc_html__('Search Schedules', 'civic-engagement') . '</label>';
        echo '<input type="search" id="civic-schedules-search-input" name="s" value="' . esc_attr($search) . '">';
        submit_button(__('Search Schedules', 'civic-engagement'), '', '', false);
        echo '</p>';
        echo '</form>';
    }

    /**
     * Render the schedules table.
     *
     * @param array<int, array<string, mixed>> $items Schedule rows.
     * @return void
     */
    private function renderTable(array $items, array $mediaCounts): void
    {
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th scope="col">' . esc_html__('ID', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Title', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Type', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Status', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Priority', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Public', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Archived', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Start Date', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Images', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Actions', 'civic-engagement') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        if (empty($items)) {
            echo '<tr><td colspan="10">' . esc_html__('No schedules found.', 'civic-engagement') . '</td></tr>';
        }

        foreach ($items as $item) {
            $this->renderRow($item, $mediaCounts);
        }

        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Render a single schedule row.
     *
     * @param array<string, mixed> $item Schedule row.
     * @return void
     */
    private function renderRow(array $item, array $mediaCounts): void
    {
        $id = isset($item['id']) ? (int) $item['id'] : 0;

        echo '<tr>';
        echo '<td>' . esc_html((string) $id) . '</td>';
        echo '<td>' . esc_html((string) ($item['title'] ?? '')) . '</td>';
        echo '<td>' . esc_html(StatusLabelHelper::format($item['type'] ?? '')) . '</td>';
        echo '<td>' . esc_html(StatusLabelHelper::format($item['status'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($item['priority'] ?? 0)) . '</td>';
        echo '<td>' . esc_html(!empty($item['is_public']) ? __('Yes', 'civic-engagement') : __('No', 'civic-engagement')) . '</td>';
        echo '<td>' . esc_html(!empty($item['is_archived']) ? __('Yes', 'civic-engagement') : __('No', 'civic-engagement')) . '</td>';
        echo '<td>' . esc_html($this->dates->formatDateTime($item['start_date'] ?? null)) . '</td>';
        echo '<td>' . esc_html((string) ($mediaCounts[$id] ?? 0)) . '</td>';
        echo '<td><a href="' . esc_url($this->viewUrl($id)) . '">' . esc_html__('View', 'civic-engagement') . '</a> | <a href="' . esc_url($this->editUrl($id)) . '">' . esc_html__('Edit', 'civic-engagement') . '</a></td>';
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
     * Build add schedule URL.
     *
     * @return string Add URL.
     */
    private function addUrl(): string
    {
        return add_query_arg(['page' => 'civic-schedule-edit'], admin_url('admin.php'));
    }

    /**
     * Build schedule view URL.
     *
     * @param int $id Schedule ID.
     * @return string View URL.
     */
    private function viewUrl(int $id): string
    {
        return add_query_arg(
            [
                'page' => 'civic-schedule-edit',
                'schedule_id' => $id,
                'mode' => 'view',
            ],
            admin_url('admin.php')
        );
    }

    /**
     * Build schedule edit URL.
     *
     * @param int $id Schedule ID.
     * @return string Edit URL.
     */
    private function editUrl(int $id): string
    {
        return add_query_arg(
            [
                'page' => 'civic-schedule-edit',
                'schedule_id' => $id,
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
