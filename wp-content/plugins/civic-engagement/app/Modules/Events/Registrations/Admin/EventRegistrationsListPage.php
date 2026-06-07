<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Events\Registrations\Admin;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Modules\Events\Repository\EventRegistrationRepository;
use CivicPlatform\Modules\Events\Repository\EventRepository;

/**
 * Renders the admin event registration listing.
 *
 * This page handles request sanitization and presentation only. Data access is
 * delegated to EventRegistrationRepository.
 */
class EventRegistrationsListPage
{
    /**
     * Required capability for viewing event registrations.
     */
    private const CAPABILITY = 'manage_civic_events';

    /**
     * Admin page slug.
     */
    private const PAGE_SLUG = 'civic-event-registrations';

    /**
     * Event registration repository.
     *
     * @var EventRegistrationRepository
     */
    private EventRegistrationRepository $registrations;

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
     * @param EventRegistrationRepository $registrations Event registration repository.
     * @param EventRepository $events Event repository.
     * @param DateHelper $dates Date helper.
     */
    public function __construct(
        EventRegistrationRepository $registrations,
        EventRepository $events,
        DateHelper $dates
    ) {
        $this->registrations = $registrations;
        $this->events = $events;
        $this->dates = $dates;
    }

    /**
     * Render the paginated event registration listing.
     *
     * @return void
     */
    public function render(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $eventId = $this->eventId();
        $event = $eventId > 0 ? $this->events->findById($eventId) : null;
        $search = $this->searchKeyword();
        $page = $this->currentPage();
        $result = $this->getResult($search, $page, $eventId);
        $items = isset($result['items']) && is_array($result['items']) ? $result['items'] : [];
        $total = isset($result['total']) ? (int) $result['total'] : 0;
        $totalPages = isset($result['total_pages']) ? (int) $result['total_pages'] : 1;

        echo '<div class="wrap">';
        echo '<h1>' . esc_html($this->pageTitle($eventId, $event)) . '</h1>';
        echo '<p>' . esc_html(sprintf(_n('%d registration', '%d registrations', $total, 'civic-engagement'), $total)) . '</p>';
        $this->renderSearchForm($search, $eventId);
        $this->renderTable($items);
        $this->renderPagination($page, $totalPages, $search, $eventId);
        echo '</div>';
    }

    /**
     * Get paginated registrations, optionally searched by keyword.
     *
     * @param string $search Search keyword.
     * @param int $page Current page.
     * @param int $eventId Optional event ID filter.
     * @return array<string, mixed> Paginated repository result.
     */
    private function getResult(string $search, int $page, int $eventId): array
    {
        $args = [
            'page' => $page,
            'per_page' => 20,
            'orderby' => 'created_at',
            'order' => 'DESC',
        ];

        if ($eventId > 0) {
            $args['event_id'] = $eventId;
        }

        if ('' !== $search) {
            return $this->registrations->search($search, $args);
        }

        return $this->registrations->getPaginated($args);
    }

    /**
     * Render the search form.
     *
     * @param string $search Search keyword.
     * @param int $eventId Optional event ID filter.
     * @return void
     */
    private function renderSearchForm(string $search, int $eventId): void
    {
        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="' . esc_attr(self::PAGE_SLUG) . '">';
        if ($eventId > 0) {
            echo '<input type="hidden" name="event_id" value="' . esc_attr((string) $eventId) . '">';
        }
        echo '<p class="search-box">';
        echo '<label class="screen-reader-text" for="civic-event-registrations-search-input">' . esc_html__('Search Registrations', 'civic-engagement') . '</label>';
        echo '<input type="search" id="civic-event-registrations-search-input" name="s" value="' . esc_attr($search) . '">';
        submit_button(__('Search Registrations', 'civic-engagement'), '', '', false);
        echo '</p>';
        echo '</form>';
    }

    /**
     * Render the registrations table.
     *
     * @param array<int, array<string, mixed>> $items Registration rows.
     * @return void
     */
    private function renderTable(array $items): void
    {
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th scope="col">' . esc_html__('ID', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Event ID', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Name', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Email', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Phone', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Electoral Area', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Registered On', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Actions', 'civic-engagement') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        if (empty($items)) {
            echo '<tr><td colspan="8">' . esc_html__('No event registrations found.', 'civic-engagement') . '</td></tr>';
        }

        foreach ($items as $item) {
            $this->renderRow($item);
        }

        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Render a single registration row.
     *
     * @param array<string, mixed> $item Registration row.
     * @return void
     */
    private function renderRow(array $item): void
    {
        $id = isset($item['id']) ? (int) $item['id'] : 0;
        $eventId = isset($item['event_id']) ? (int) $item['event_id'] : 0;

        echo '<tr>';
        echo '<td>' . esc_html((string) $id) . '</td>';
        echo '<td>' . esc_html((string) $eventId) . '</td>';
        echo '<td>' . esc_html((string) ($item['name_snapshot'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($item['email_snapshot'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($item['phone_snapshot'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($item['electoral_area_snapshot'] ?? '')) . '</td>';
        echo '<td>' . esc_html($this->dates->formatDateTime($item['created_at'] ?? null)) . '</td>';
        echo '<td><a href="' . esc_url($this->viewUrl($id, $eventId)) . '">' . esc_html__('View', 'civic-engagement') . '</a></td>';
        echo '</tr>';
    }

    /**
     * Render pagination links.
     *
     * @param int $page Current page.
     * @param int $totalPages Total pages.
     * @param string $search Search keyword.
     * @param int $eventId Optional event ID filter.
     * @return void
     */
    private function renderPagination(int $page, int $totalPages, string $search, int $eventId): void
    {
        if ($totalPages <= 1) {
            return;
        }

        echo '<div class="tablenav bottom">';
        echo '<div class="tablenav-pages">';
        echo '<span class="displaying-num">' . esc_html(sprintf(_n('%s page', '%s pages', $totalPages, 'civic-engagement'), number_format_i18n($totalPages))) . '</span>';

        if ($page > 1) {
            echo '<a class="button" href="' . esc_url($this->pageUrl($page - 1, $search, $eventId)) . '">' . esc_html__('Previous', 'civic-engagement') . '</a> ';
        }

        echo '<span class="paging-input">' . esc_html((string) $page) . ' / ' . esc_html((string) $totalPages) . '</span>';

        if ($page < $totalPages) {
            echo ' <a class="button" href="' . esc_url($this->pageUrl($page + 1, $search, $eventId)) . '">' . esc_html__('Next', 'civic-engagement') . '</a>';
        }

        echo '</div>';
        echo '</div>';
    }

    /**
     * Build a pagination URL.
     *
     * @param int $page Page number.
     * @param string $search Search keyword.
     * @param int $eventId Optional event ID filter.
     * @return string Page URL.
     */
    private function pageUrl(int $page, string $search, int $eventId): string
    {
        $args = [
            'page' => self::PAGE_SLUG,
            'paged' => $page,
        ];

        if ($eventId > 0) {
            $args['event_id'] = $eventId;
        }

        if ('' !== $search) {
            $args['s'] = $search;
        }

        return add_query_arg($args, admin_url('admin.php'));
    }

    /**
     * Build the page title for global or contextual registration listings.
     *
     * @param int $eventId Optional event ID filter.
     * @param array<string, mixed>|null $event Event row.
     * @return string Page title.
     */
    private function pageTitle(int $eventId, ?array $event): string
    {
        if ($eventId <= 0) {
            return __('Event Registrations', 'civic-engagement');
        }

        $title = isset($event['title']) ? trim((string) $event['title']) : '';

        if ('' === $title) {
            $title = sprintf(__('ID %d', 'civic-engagement'), $eventId);
        }

        return sprintf(
            __('Registrations for Event: %s', 'civic-engagement'),
            $title
        );
    }

    /**
     * Build registration detail URL.
     *
     * @param int $id Registration ID.
     * @param int $eventId Event ID.
     * @return string View URL.
     */
    private function viewUrl(int $id, int $eventId): string
    {
        $args = [
            'page' => 'civic-event-registration-view',
            'registration_id' => $id,
        ];

        if ($eventId > 0) {
            $args['event_id'] = $eventId;
        }

        return add_query_arg($args, admin_url('admin.php'));
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
     * Get sanitized contextual event ID.
     *
     * @return int Event ID, or 0 for global listing.
     */
    private function eventId(): int
    {
        if (!isset($_GET['event_id'])) {
            return 0;
        }

        $eventId = wp_unslash($_GET['event_id']);

        if (is_array($eventId) || is_object($eventId)) {
            return 0;
        }

        return max(0, absint($eventId));
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
