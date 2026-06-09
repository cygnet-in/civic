<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Events\Admin;

use CivicPlatform\Modules\Events\Repository\EventFieldRepository;
use CivicPlatform\Modules\Events\Repository\EventRepository;

/**
 * Renders the admin listing for event registration fields.
 *
 * Fields belong to a single event. This page handles request sanitization and
 * presentation only; persistence stays in EventFieldRepository.
 */
class EventFieldsListPage
{
    /**
     * Required capability for event field administration.
     */
    private const CAPABILITY = 'manage_civic_events';

    /**
     * Event field repository.
     *
     * @var EventFieldRepository
     */
    private EventFieldRepository $fields;

    /**
     * Event repository.
     *
     * @var EventRepository
     */
    private EventRepository $events;

    /**
     * @param EventFieldRepository $fields Event field repository.
     * @param EventRepository $events Event repository.
     */
    public function __construct(EventFieldRepository $fields, EventRepository $events)
    {
        $this->fields = $fields;
        $this->events = $events;
    }

    /**
     * Render the field listing page.
     *
     * @return void
     */
    public function render(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $eventId = $this->eventId();
        $event = $this->events->findById($eventId);

        echo '<div class="wrap">';
        echo '<h1>' . esc_html($this->pageTitle($event)) . '</h1>';
        echo '<p><a href="' . esc_url($this->eventsUrl()) . '">' . esc_html__('Back to Events', 'civic-engagement') . '</a></p>';

        if (!is_array($event)) {
            $this->renderNotFound();
            echo '</div>';

            return;
        }

        $this->renderStatusNotice();
        echo '<p><a class="button button-primary" href="' . esc_url($this->addUrl($eventId)) . '">' . esc_html__('Add Field', 'civic-engagement') . '</a></p>';
        $this->renderTable($this->fields->findByEventId($eventId), $eventId);

        echo '</div>';
    }

    /**
     * Render the fields table.
     *
     * @param array<int, array<string, mixed>> $items Field rows.
     * @param int $eventId Event ID.
     * @return void
     */
    private function renderTable(array $items, int $eventId): void
    {
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th scope="col">' . esc_html__('ID', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Label', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Key', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Field Type', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Required', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Sort Order', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Actions', 'civic-engagement') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        if (empty($items)) {
            echo '<tr><td colspan="7">' . esc_html__('No fields found for this event.', 'civic-engagement') . '</td></tr>';
        }

        foreach ($items as $item) {
            $this->renderRow($item, $eventId);
        }

        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Render a single field row.
     *
     * @param array<string, mixed> $item Field row.
     * @param int $eventId Event ID.
     * @return void
     */
    private function renderRow(array $item, int $eventId): void
    {
        $id = isset($item['id']) ? (int) $item['id'] : 0;

        echo '<tr>';
        echo '<td>' . esc_html((string) $id) . '</td>';
        echo '<td>' . esc_html((string) ($item['field_label'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($item['field_key'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($item['field_type'] ?? '')) . '</td>';
        echo '<td>' . esc_html($this->yesNo($item['is_required'] ?? 0)) . '</td>';
        echo '<td>' . esc_html((string) ($item['sort_order'] ?? '')) . '</td>';
        echo '<td><a href="' . esc_url($this->editUrl($eventId, $id)) . '">' . esc_html__('Edit', 'civic-engagement') . '</a></td>';
        echo '</tr>';
    }

    /**
     * Build the page title.
     *
     * @param array<string, mixed>|null $event Event row.
     * @return string Page title.
     */
    private function pageTitle(?array $event): string
    {
        if (!is_array($event)) {
            return __('Event Fields', 'civic-engagement');
        }

        $title = trim((string) ($event['title'] ?? ''));

        if ('' === $title) {
            return __('Event Fields', 'civic-engagement');
        }

        return sprintf(__('Fields for Event: %s', 'civic-engagement'), $title);
    }

    /**
     * Render an admin error when the event cannot be found.
     *
     * @return void
     */
    private function renderNotFound(): void
    {
        echo '<div class="notice notice-error"><p>' . esc_html__('Event not found.', 'civic-engagement') . '</p></div>';
    }

    /**
     * Render create/update success notices from redirect query flags.
     *
     * @return void
     */
    private function renderStatusNotice(): void
    {
        if ($this->queryFlag('created')) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Field created successfully.', 'civic-engagement') . '</p></div>';

            return;
        }

        if ($this->queryFlag('updated')) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Field updated successfully.', 'civic-engagement') . '</p></div>';
        }
    }

    /**
     * Convert truthy values to a display label.
     *
     * @param mixed $value Raw value.
     * @return string Display label.
     */
    private function yesNo($value): string
    {
        return !empty($value)
            ? __('Yes', 'civic-engagement')
            : __('No', 'civic-engagement');
    }

    /**
     * Build the Events list URL.
     *
     * @return string Events URL.
     */
    private function eventsUrl(): string
    {
        return add_query_arg(
            ['page' => 'civic-events'],
            admin_url('admin.php')
        );
    }

    /**
     * Build an Add Field URL.
     *
     * @param int $eventId Event ID.
     * @return string Add URL.
     */
    private function addUrl(int $eventId): string
    {
        return add_query_arg(
            [
                'page' => 'civic-event-field-edit',
                'event_id' => $eventId,
            ],
            admin_url('admin.php')
        );
    }

    /**
     * Build an Edit Field URL.
     *
     * @param int $eventId Event ID.
     * @param int $fieldId Field ID.
     * @return string Edit URL.
     */
    private function editUrl(int $eventId, int $fieldId): string
    {
        return add_query_arg(
            [
                'page' => 'civic-event-field-edit',
                'event_id' => $eventId,
                'field_id' => $fieldId,
            ],
            admin_url('admin.php')
        );
    }

    /**
     * Get sanitized requested event ID.
     *
     * @return int Event ID.
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

        return absint($eventId);
    }

    /**
     * Read a sanitized boolean query flag.
     *
     * @param string $key Query key.
     * @return bool True when the flag is set to 1.
     */
    private function queryFlag(string $key): bool
    {
        if (!isset($_GET[$key])) {
            return false;
        }

        $value = wp_unslash($_GET[$key]);

        if (is_array($value) || is_object($value)) {
            return false;
        }

        return 1 === absint($value);
    }
}
