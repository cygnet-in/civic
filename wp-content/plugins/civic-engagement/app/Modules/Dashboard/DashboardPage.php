<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Dashboard;

use CivicPlatform\Modules\Events\Repository\EventRegistrationRepository;
use CivicPlatform\Modules\Events\Repository\EventRepository;
use CivicPlatform\Modules\Reps\Repository\RepRepository;
use CivicPlatform\Modules\Schedules\Repository\ScheduleRepository;
use CivicPlatform\Modules\Threads\Repository\ThreadRepository;
use CivicPlatform\Modules\Threads\Repository\ThreadResponseRepository;
use CivicPlatform\Modules\Users\Repository\ContactRepository;

/** Renders operational Civic counts and compact recent-work lists. */
class DashboardPage
{
    private RepRepository $reps;
    private ThreadRepository $threads;
    private ThreadResponseRepository $responses;
    private EventRepository $events;
    private EventRegistrationRepository $registrations;
    private ScheduleRepository $schedules;
    private ContactRepository $contacts;

    public function __construct(RepRepository $reps, ThreadRepository $threads, ThreadResponseRepository $responses, EventRepository $events, EventRegistrationRepository $registrations, ScheduleRepository $schedules, ContactRepository $contacts)
    {
        $this->reps = $reps;
        $this->threads = $threads;
        $this->responses = $responses;
        $this->events = $events;
        $this->registrations = $registrations;
        $this->schedules = $schedules;
        $this->contacts = $contacts;
    }

    public function render(): void
    {
        $counts = $this->counts();

        echo '<div class="wrap civic-dashboard">';
        echo '<h1>' . esc_html__('Civic Platform', 'civic-engagement') . '</h1>';
        echo '<p class="civic-dashboard__welcome">' . esc_html__('Manage representations, consultations, events, schedules and community engagement from a single dashboard.', 'civic-engagement') . '</p>';
        echo '<div class="civic-dashboard__cards">';
        foreach ($this->cards($counts) as $card) {
            echo '<a class="civic-dashboard__card" href="' . esc_url($card['url']) . '">';
            echo '<span class="dashicons ' . esc_attr($card['icon']) . '" aria-hidden="true"></span>';
            echo '<span class="civic-dashboard__card-label">' . esc_html($card['label']) . '</span>';
            echo '<strong class="civic-dashboard__count">' . esc_html(number_format_i18n($card['count'])) . '</strong>';
            echo '<span class="civic-dashboard__card-link">' . esc_html__('View', 'civic-engagement') . ' <span aria-hidden="true">&rarr;</span></span>';
            echo '</a>';
        }
        echo '</div>';

        echo '<h2 class="civic-dashboard__recent-heading">' . esc_html__('Recent Activity', 'civic-engagement') . '</h2>';
        echo '<div class="civic-dashboard__recent-grid">';
        $this->renderRecentSection(__('Latest Representations', 'civic-engagement'), $this->recentItems($this->reps), 'title', 'civic-platform');
        $this->renderRecentSection(__('Latest Consultation Responses', 'civic-engagement'), $this->recentItems($this->responses), 'response', 'civic-thread-responses');
        $this->renderRecentSection(__('Upcoming Schedules', 'civic-engagement'), $this->upcomingSchedules(), 'title', 'civic-schedules');
        $this->renderRecentSection(__('Latest Events', 'civic-engagement'), $this->recentItems($this->events), 'title', 'civic-events');
        echo '</div></div>';
    }

    /** @return array<string, int> */
    private function counts(): array
    {
        return [
            'reps' => $this->count($this->reps),
            'threads' => $this->count($this->threads),
            'responses' => $this->count($this->responses),
            'events' => $this->count($this->events),
            'registrations' => $this->count($this->registrations),
            'schedules' => $this->count($this->schedules),
            'contacts' => $this->count($this->contacts),
        ];
    }

    /** @param array<string, int> $counts @return array<int, array{label: string, count: int, url: string, icon: string}> */
    private function cards(array $counts): array
    {
        return [
            ['label' => __('Representations', 'civic-engagement'), 'count' => $counts['reps'], 'url' => $this->adminUrl('civic-platform'), 'icon' => 'dashicons-megaphone'],
            ['label' => __('Consultations', 'civic-engagement'), 'count' => $counts['threads'], 'url' => $this->adminUrl('civic-threads'), 'icon' => 'dashicons-format-chat'],
            ['label' => __('Responses', 'civic-engagement'), 'count' => $counts['responses'], 'url' => $this->adminUrl('civic-thread-responses'), 'icon' => 'dashicons-admin-comments'],
            ['label' => __('Events', 'civic-engagement'), 'count' => $counts['events'], 'url' => $this->adminUrl('civic-events'), 'icon' => 'dashicons-calendar-alt'],
            ['label' => __('Registrations', 'civic-engagement'), 'count' => $counts['registrations'], 'url' => $this->adminUrl('civic-event-registrations'), 'icon' => 'dashicons-tickets-alt'],
            ['label' => __('Schedules', 'civic-engagement'), 'count' => $counts['schedules'], 'url' => $this->adminUrl('civic-schedules'), 'icon' => 'dashicons-clock'],
            ['label' => __('Contacts', 'civic-engagement'), 'count' => $counts['contacts'], 'url' => $this->adminUrl('civic-contacts'), 'icon' => 'dashicons-groups'],
        ];
    }

    /** @param object $repository */
    private function count($repository): int
    {
        $result = $repository->getPaginated(['page' => 1, 'per_page' => 1]);
        return (int) ($result['total'] ?? 0);
    }

    /** @param object $repository @return array<int, array<string, mixed>> */
    private function recentItems($repository): array
    {
        $result = $repository->getPaginated(['page' => 1, 'per_page' => 5, 'orderby' => 'created_at', 'order' => 'DESC']);
        return is_array($result['items'] ?? null) ? $result['items'] : [];
    }

    /** @return array<int, array<string, mixed>> */
    private function upcomingSchedules(): array
    {
        $result = $this->schedules->getPaginated([
            'page' => 1,
            'per_page' => 5,
            'start_date_from' => current_time('mysql'),
            'orderby' => 'start_date',
            'order' => 'ASC',
        ]);
        return is_array($result['items'] ?? null) ? $result['items'] : [];
    }

    /** @param array<int, array<string, mixed>> $items */
    private function renderRecentSection(string $heading, array $items, string $field, string $pageSlug): void
    {
        echo '<section class="civic-dashboard__recent-section"><h3>' . esc_html($heading) . '</h3><ul>';
        if (empty($items)) {
            echo '<li>' . esc_html__('No items found.', 'civic-engagement') . '</li>';
        }
        foreach ($items as $item) {
            $value = 'response' === $field ? $this->responseSummary($item) : (string) ($item[$field] ?? '');
            echo '<li>' . esc_html('' !== trim($value) ? $value : __('Untitled', 'civic-engagement')) . '</li>';
        }
        echo '</ul><p><a href="' . esc_url($this->adminUrl($pageSlug)) . '">' . esc_html__('View All', 'civic-engagement') . ' <span aria-hidden="true">&rarr;</span></a></p></section>';
    }

    /** @param array<string, mixed> $response */
    private function responseSummary(array $response): string
    {
        $data = json_decode((string) ($response['response_data'] ?? ''), true);
        $text = is_array($data) ? (string) ($data['response_text'] ?? '') : '';
        if ('' === trim($text) && is_array($data)) {
            $customFields = isset($data['custom_fields']) && is_array($data['custom_fields'])
                ? $data['custom_fields']
                : [];

            foreach ($customFields as $value) {
                if (is_array($value) || is_object($value)) {
                    continue;
                }

                $text = trim((string) $value);

                if ('' !== $text) {
                    break;
                }
            }
        }
        return '' !== trim($text) ? wp_trim_words(wp_strip_all_tags($text), 12, '...') : (string) ($response['name_snapshot'] ?? '');
    }

    private function adminUrl(string $page): string
    {
        return add_query_arg(['page' => $page], admin_url('admin.php'));
    }
}
