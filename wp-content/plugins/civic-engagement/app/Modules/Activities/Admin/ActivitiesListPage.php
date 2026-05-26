<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Activities\Admin;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Modules\Activities\Repository\ActivityRepository;

/**
 * Renders the admin activity history listing.
 *
 * This page handles request sanitization and presentation only. Data access is
 * delegated to ActivityRepository.
 */
class ActivitiesListPage
{
    /**
     * Required capability for viewing activity history.
     */
    private const CAPABILITY = 'view_civic_activities';

    /**
     * Admin page slug.
     */
    private const PAGE_SLUG = 'civic-activities';

    /**
     * Activity repository.
     *
     * @var ActivityRepository
     */
    private ActivityRepository $activities;

    /**
     * Date helper.
     *
     * @var DateHelper
     */
    private DateHelper $dates;

    /**
     * @param ActivityRepository $activities Activity repository.
     * @param DateHelper $dates Date helper.
     */
    public function __construct(ActivityRepository $activities, DateHelper $dates)
    {
        $this->activities = $activities;
        $this->dates = $dates;
    }

    /**
     * Render the paginated activity listing.
     *
     * @return void
     */
    public function render(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $page = $this->currentPage();
        $result = $this->activities->getPaginated(
            [
                'page' => $page,
                'per_page' => 20,
                'orderby' => 'created_at',
                'order' => 'DESC',
            ]
        );
        $items = isset($result['items']) && is_array($result['items']) ? $result['items'] : [];
        $totalPages = isset($result['total_pages']) ? (int) $result['total_pages'] : 1;

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Activities', 'civic-engagement') . '</h1>';
        $this->renderTable($items);
        $this->renderPagination($page, $totalPages);
        echo '</div>';
    }

    /**
     * Render the activities table.
     *
     * @param array<int, array<string, mixed>> $items Activity rows.
     * @return void
     */
    private function renderTable(array $items): void
    {
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th scope="col">' . esc_html__('Activity ID', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Contact ID', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Activity Type', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Summary', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Related ID', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Created At', 'civic-engagement') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        if (empty($items)) {
            echo '<tr><td colspan="6">' . esc_html__('No activities found.', 'civic-engagement') . '</td></tr>';
        }

        foreach ($items as $item) {
            $this->renderRow($item);
        }

        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Render a single activity row.
     *
     * @param array<string, mixed> $item Activity row.
     * @return void
     */
    private function renderRow(array $item): void
    {
        echo '<tr>';
        echo '<td>' . esc_html((string) ($item['id'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($item['contact_id'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($item['activity_type'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($item['summary'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($item['related_id'] ?? '')) . '</td>';
        echo '<td>' . esc_html($this->dates->formatDateTime($item['created_at'] ?? null)) . '</td>';
        echo '</tr>';
    }

    /**
     * Render pagination links.
     *
     * @param int $page Current page.
     * @param int $totalPages Total pages.
     * @return void
     */
    private function renderPagination(int $page, int $totalPages): void
    {
        if ($totalPages <= 1) {
            return;
        }

        echo '<div class="tablenav bottom">';
        echo '<div class="tablenav-pages">';
        echo '<span class="displaying-num">' . esc_html(sprintf(_n('%s page', '%s pages', $totalPages, 'civic-engagement'), number_format_i18n($totalPages))) . '</span>';

        if ($page > 1) {
            echo '<a class="button" href="' . esc_url($this->pageUrl($page - 1)) . '">' . esc_html__('Previous', 'civic-engagement') . '</a> ';
        }

        echo '<span class="paging-input">' . esc_html((string) $page) . ' / ' . esc_html((string) $totalPages) . '</span>';

        if ($page < $totalPages) {
            echo ' <a class="button" href="' . esc_url($this->pageUrl($page + 1)) . '">' . esc_html__('Next', 'civic-engagement') . '</a>';
        }

        echo '</div>';
        echo '</div>';
    }

    /**
     * Build a pagination URL.
     *
     * @param int $page Page number.
     * @return string Page URL.
     */
    private function pageUrl(int $page): string
    {
        return add_query_arg(
            [
                'page' => self::PAGE_SLUG,
                'paged' => $page,
            ],
            admin_url('admin.php')
        );
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
}
