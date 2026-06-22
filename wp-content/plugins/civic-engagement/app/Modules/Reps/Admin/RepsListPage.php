<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Reps\Admin;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Helpers\StatusLabelHelper;
use CivicPlatform\Modules\Reps\Repository\RepRepository;

/**
 * Renders the admin representations listing.
 *
 * This page handles request sanitization and presentation only. Data access is
 * delegated to RepRepository.
 */
class RepsListPage
{
    /**
     * Required capability for viewing the page.
     */
    private const CAPABILITY = 'manage_civic_reps';

    /**
     * Admin page slug.
     */
    private const PAGE_SLUG = 'civic-platform';

    /**
     * Reps repository.
     *
     * @var RepRepository
     */
    private RepRepository $reps;

    /**
     * Date helper.
     *
     * @var DateHelper
     */
    private DateHelper $dates;

    /**
     * @param RepRepository $reps Reps repository.
     * @param DateHelper $dates Date helper.
     */
    public function __construct(RepRepository $reps, DateHelper $dates)
    {
        $this->reps = $reps;
        $this->dates = $dates;
    }

    /**
     * Render the paginated representations listing.
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
        echo '<h1>' . esc_html__('Representations', 'civic-engagement') . '</h1>';
        $this->renderSearchForm($search);
        $this->renderTable($items);
        $this->renderPagination($page, $totalPages, $search);
        echo '</div>';
    }

    /**
     * Get paginated reps, optionally searched by keyword.
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
            'orderby' => 'created_at',
            'order' => 'DESC',
        ];

        if ('' !== $search) {
            return $this->reps->search($search, $args);
        }

        return $this->reps->getPaginated($args);
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
        echo '<label class="screen-reader-text" for="civic-reps-search-input">' . esc_html__('Search Representations', 'civic-engagement') . '</label>';
        echo '<input type="search" id="civic-reps-search-input" name="s" value="' . esc_attr($search) . '">';
        submit_button(__('Search Representations', 'civic-engagement'), '', '', false);
        echo '</p>';
        echo '</form>';
    }

    /**
     * Render the reps table.
     *
     * @param array<int, array<string, mixed>> $items Rep rows.
     * @return void
     */
    private function renderTable(array $items): void
    {
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th scope="col">' . esc_html__('ID', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Submitted Name', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Email', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Status', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Created', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Actions', 'civic-engagement') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        if (empty($items)) {
            echo '<tr><td colspan="6">' . esc_html__('No representations found.', 'civic-engagement') . '</td></tr>';
        }

        foreach ($items as $item) {
            $this->renderRow($item);
        }

        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Render a single rep row.
     *
     * @param array<string, mixed> $item Rep row.
     * @return void
     */
    private function renderRow(array $item): void
    {
        $id = isset($item['id']) ? (int) $item['id'] : 0;

        echo '<tr>';
        echo '<td>' . esc_html((string) $id) . '</td>';
        echo '<td>' . esc_html((string) ($item['name_snapshot'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($item['email_snapshot'] ?? '')) . '</td>';
        echo '<td>' . esc_html(StatusLabelHelper::format($item['status'] ?? '')) . '</td>';
        echo '<td>' . esc_html($this->dates->formatDateTime($item['created_at'] ?? null)) . '</td>';
        echo '<td><a href="' . esc_url($this->viewUrl($id)) . '">' . esc_html__('View', 'civic-engagement') . '</a></td>';
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
     * Build a placeholder detail URL for future view page.
     *
     * @param int $id Rep ID.
     * @return string View URL.
     */
    private function viewUrl(int $id): string
    {
        return add_query_arg(
            [
                'page' => 'civic-rep-view',
                'rep_id' => $id,
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
}
