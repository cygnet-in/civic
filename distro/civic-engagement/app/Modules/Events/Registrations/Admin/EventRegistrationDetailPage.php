<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Events\Registrations\Admin;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Modules\Events\Repository\EventFieldRepository;
use CivicPlatform\Modules\Events\Repository\EventRegistrationRepository;
use CivicPlatform\Modules\Events\Repository\EventRepository;

/**
 * Renders a single event registration detail page.
 *
 * Registration content is immutable. This page provides read-only operational
 * visibility into the submitted snapshots and registration data.
 */
class EventRegistrationDetailPage
{
    /**
     * Required capability for viewing event registrations.
     */
    private const CAPABILITY = 'manage_civic_events';

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
     * Event field repository.
     *
     * @var EventFieldRepository
     */
    private EventFieldRepository $fields;

    /**
     * Date helper.
     *
     * @var DateHelper
     */
    private DateHelper $dates;

    /**
     * @param EventRegistrationRepository $registrations Event registration repository.
     * @param EventRepository $events Event repository.
     * @param EventFieldRepository $fields Event field repository.
     * @param DateHelper $dates Date helper.
     */
    public function __construct(
        EventRegistrationRepository $registrations,
        EventRepository $events,
        EventFieldRepository $fields,
        DateHelper $dates
    ) {
        $this->registrations = $registrations;
        $this->events = $events;
        $this->fields = $fields;
        $this->dates = $dates;
    }

    /**
     * Render the registration detail page.
     *
     * @return void
     */
    public function render(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $registration = $this->registrations->findById($this->registrationId());
        $event = is_array($registration) ? $this->events->findById((int) ($registration['event_id'] ?? 0)) : null;

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Event Registration Detail', 'civic-engagement') . '</h1>';
        echo '<p><a href="' . esc_url($this->listUrl($registration, $event)) . '">' . esc_html__('Back to Registrations', 'civic-engagement') . '</a></p>';

        if (!is_array($registration)) {
            $this->renderNotFound();
            echo '</div>';

            return;
        }

        $this->renderDetails($registration, $event);

        echo '</div>';
    }

    /**
     * Render registration details.
     *
     * @param array<string, mixed> $registration Registration row.
     * @param array<string, mixed>|null $event Event row.
     * @return void
     */
    private function renderDetails(array $registration, ?array $event): void
    {
        echo '<table class="widefat striped"><tbody>';
        $this->renderDetailRow(__('Registration ID', 'civic-engagement'), (string) ($registration['id'] ?? ''));
        $this->renderDetailRow(__('Event', 'civic-engagement'), $this->eventTitle($registration, $event));
        $this->renderDetailRow(__('Registration Date', 'civic-engagement'), $this->dates->formatDateTime($registration['created_at'] ?? null));
        $this->renderDetailRow(__('Name Snapshot', 'civic-engagement'), (string) ($registration['name_snapshot'] ?? ''));
        $this->renderDetailRow(__('Email Snapshot', 'civic-engagement'), (string) ($registration['email_snapshot'] ?? ''));
        $this->renderDetailRow(__('Phone Snapshot', 'civic-engagement'), (string) ($registration['phone_snapshot'] ?? ''));
        $this->renderDetailRow(__('Address Snapshot', 'civic-engagement'), (string) ($registration['address_snapshot'] ?? ''));
        $this->renderDetailRow(__('Eircode Snapshot', 'civic-engagement'), (string) ($registration['eircode_snapshot'] ?? ''));
        $this->renderDetailRow(__('Electoral Area Snapshot', 'civic-engagement'), (string) ($registration['electoral_area_snapshot'] ?? ''));
        $this->renderCustomFieldRows($registration);
        echo '</tbody></table>';
    }

    /**
     * Render submitted custom registration field values.
     *
     * @param array<string, mixed> $registration Registration row.
     * @return void
     */
    private function renderCustomFieldRows(array $registration): void
    {
        $values = $this->registrationDataValues($registration['registration_data'] ?? '');

        if (empty($values)) {
            return;
        }

        $labels = $this->fieldLabels((int) ($registration['event_id'] ?? 0));

        foreach ($values as $fieldKey => $value) {
            if (empty($labels[$fieldKey])) {
                continue;
            }

            $this->renderDetailRow($labels[$fieldKey], $value);
        }
    }

    /**
     * Render a table detail row.
     *
     * @param string $label Row label.
     * @param string $value Row value.
     * @return void
     */
    private function renderDetailRow(string $label, string $value): void
    {
        echo '<tr>';
        echo '<th scope="row">' . esc_html($label) . '</th>';
        echo '<td>' . nl2br(esc_html($value)) . '</td>';
        echo '</tr>';
    }

    /**
     * Render an admin error when the registration cannot be found.
     *
     * @return void
     */
    private function renderNotFound(): void
    {
        echo '<div class="notice notice-error"><p>' . esc_html__('Registration not found.', 'civic-engagement') . '</p></div>';
    }

    /**
     * Build a readable event title.
     *
     * @param array<string, mixed> $registration Registration row.
     * @param array<string, mixed>|null $event Event row.
     * @return string Event title.
     */
    private function eventTitle(array $registration, ?array $event): string
    {
        $title = isset($event['title']) ? trim((string) $event['title']) : '';

        if ('' !== $title) {
            return $title;
        }

        $eventId = isset($registration['event_id']) ? (int) $registration['event_id'] : 0;

        return $eventId > 0 ? sprintf(__('Event #%d', 'civic-engagement'), $eventId) : '';
    }

    /**
     * Extract custom registration data values.
     *
     * @param mixed $value Raw registration_data value.
     * @return array<string, string>
     */
    private function registrationDataValues($value): array
    {
        $data = $this->registrationDataArray($value);
        $data = isset($data['custom_fields']) && is_array($data['custom_fields'])
            ? $data['custom_fields']
            : $data;
        $values = [];

        foreach ($data as $fieldKey => $fieldValue) {
            if (is_array($fieldValue) || is_object($fieldValue)) {
                continue;
            }

            $fieldKey = sanitize_key((string) $fieldKey);
            $fieldValue = trim((string) $fieldValue);

            if ('' !== $fieldKey && '' !== $fieldValue) {
                $values[$fieldKey] = $fieldValue;
            }
        }

        return $values;
    }

    /**
     * Decode registration_data to an array.
     *
     * @param mixed $value Raw registration_data value.
     * @return array<string, mixed>
     */
    private function registrationDataArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return [];
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Build field label lookup for an event.
     *
     * @param int $eventId Event ID.
     * @return array<string, string>
     */
    private function fieldLabels(int $eventId): array
    {
        if ($eventId <= 0) {
            return [];
        }

        $labels = [];

        foreach ($this->fields->findByEventId($eventId) as $field) {
            $label = trim((string) ($field['field_label'] ?? ''));

            if ('' === $label) {
                continue;
            }

            foreach ($this->fieldKeys($field) as $fieldKey) {
                $labels[$fieldKey] = $label;
            }
        }

        return $labels;
    }

    /**
     * Build stable field keys from available field metadata.
     *
     * @param array<string, mixed> $field Field row.
     * @return array<int, string> Field keys.
     */
    private function fieldKeys(array $field): array
    {
        $keys = [];
        $fieldKey = isset($field['field_key']) ? sanitize_key((string) $field['field_key']) : '';

        if ('' !== $fieldKey) {
            $keys[] = $fieldKey;
        }

        $label = trim((string) ($field['field_label'] ?? ''));

        if ('' !== $label) {
            $keys[] = sanitize_key($label);
            $keys[] = sanitize_title($label);
            $keys[] = str_replace('-', '_', sanitize_title($label));
        }

        return array_values(array_unique(array_filter($keys)));
    }

    /**
     * Get sanitized requested registration ID.
     *
     * @return int Registration ID.
     */
    private function registrationId(): int
    {
        if (!isset($_GET['registration_id'])) {
            return 0;
        }

        $registrationId = wp_unslash($_GET['registration_id']);

        if (is_array($registrationId) || is_object($registrationId)) {
            return 0;
        }

        return absint($registrationId);
    }

    /**
     * Build the registration listing URL.
     *
     * @param array<string, mixed>|null $registration Registration row.
     * @param array<string, mixed>|null $event Event row.
     * @return string List URL.
     */
    private function listUrl(?array $registration, ?array $event): string
    {
        $args = ['page' => 'civic-event-registrations'];
        $eventId = isset($registration['event_id']) ? (int) $registration['event_id'] : 0;

        if ($eventId <= 0 && isset($event['id'])) {
            $eventId = (int) $event['id'];
        }

        if ($eventId > 0) {
            $args['event_id'] = $eventId;
        }

        return add_query_arg($args, admin_url('admin.php'));
    }
}
