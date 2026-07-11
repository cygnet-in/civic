<?php

declare(strict_types=1);

namespace CivicPlatform\Services;

use CivicPlatform\Modules\Events\Repository\EventRegistrationRepository;

/**
 * Coordinates the event registration workflow.
 *
 * This service synchronizes latest contact details, stores submitted snapshot
 * data on the registration row, and logs the activity. Request handling, nonce
 * checks, rendering, redirects, and notifications belong elsewhere.
 */
class EventService
{
    /**
     * Event registration repository.
     *
     * @var EventRegistrationRepository
     */
    private EventRegistrationRepository $registrations;

    /**
     * Contact service.
     *
     * @var ContactService
     */
    private ContactService $contacts;

    /**
     * Activity service.
     *
     * @var ActivityService
     */
    private ActivityService $activities;

    /**
     * @param EventRegistrationRepository $registrations Event registration repository.
     * @param ContactService $contacts Contact service.
     * @param ActivityService $activities Activity service.
     */
    public function __construct(
        EventRegistrationRepository $registrations,
        ContactService $contacts,
        ActivityService $activities
    ) {
        $this->registrations = $registrations;
        $this->contacts = $contacts;
        $this->activities = $activities;
    }

    /**
     * Submit an event registration.
     *
     * Expected workflow keys include event_id, name, email, phone, address,
     * eircode, electoral_area, and registration_data.
     *
     * Return shape:
     * [
     *     'success' => bool,
     *     'contact' => array|null,
     *     'registration_id' => int,
     *     'activity_id' => int,
     *     'error' => string|null,
     * ]
     *
     * @param array<string, mixed> $data Workflow data.
     * @return array<string, mixed> Submission result.
     */
    public function submitRegistration(array $data): array
    {
        $normalized = $this->normalizeSubmissionData($data);

        if ($normalized['event_id'] <= 0) {
            return $this->buildResult(false, null, 0, 0, 'invalid_event_id');
        }

        $contactResult = $this->contacts->syncContact($normalized);
        $contact = $contactResult['contact'] ?? null;

        if (!is_array($contact) || empty($contact['id'])) {
            return $this->buildResult(false, null, 0, 0, 'contact_sync_failed');
        }

        $registrationId = $this->registrations->create(
            $this->buildRegistrationData($normalized, (int) $contact['id'])
        );

        if ($registrationId <= 0) {
            return $this->buildResult(false, $contact, 0, 0, 'registration_create_failed');
        }

        $activityId = $this->activities->log(
            $this->buildActivityData($contact, $registrationId, $normalized)
        );

        if ($activityId <= 0) {
            return $this->buildResult(false, $contact, $registrationId, 0, 'activity_create_failed');
        }

        return $this->buildResult(true, $contact, $registrationId, $activityId, null);
    }

    /**
     * Get paginated registrations for an event.
     *
     * @param int $eventId Event ID.
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getByEventId(int $eventId, array $args = []): array
    {
        return $this->registrations->findByEventId($eventId, $args);
    }

    /**
     * Get a paginated registration listing.
     *
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getPaginated(array $args = []): array
    {
        return $this->registrations->getPaginated($args);
    }

    /**
     * Search event registrations.
     *
     * @param string $keyword Search keyword.
     * @param array<string, mixed> $args Search arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function searchRegistrations(string $keyword, array $args = []): array
    {
        return $this->registrations->search($keyword, $args);
    }

    /**
     * Normalize workflow input into simple values.
     *
     * @param array<string, mixed> $data Raw workflow data.
     * @return array<string, mixed>
     */
    private function normalizeSubmissionData(array $data): array
    {
        return [
            'event_id' => isset($data['event_id']) ? (int) $data['event_id'] : 0,
            'name' => $this->stringValue($data['name'] ?? $data['latest_name'] ?? ''),
            'email' => $this->stringValue($data['email'] ?? ''),
            'phone' => $this->stringValue($data['phone'] ?? $data['latest_phone'] ?? ''),
            'address' => $this->stringValue($data['address'] ?? $data['latest_address'] ?? ''),
            'eircode' => $this->stringValue($data['eircode'] ?? $data['latest_eircode'] ?? ''),
            'electoral_area' => $this->stringValue(
                $data['electoral_area'] ?? $data['latest_electoral_area'] ?? ''
            ),
            'registration_data' => is_array($data['registration_data'] ?? null)? $data['registration_data']: [],
        ];
    }

    /**
     * Build data for civic_event_registrations while preserving snapshots.
     *
     * @param array<string, mixed> $data Normalized workflow data.
     * @param int $contactId Contact ID.
     * @return array<string, mixed>
     */
    private function buildRegistrationData(array $data, int $contactId): array
    {
        return [
            'event_id' => $data['event_id'],
            'contact_id' => $contactId,
            'name_snapshot' => $data['name'],
            'email_snapshot' => $data['email'],
            'phone_snapshot' => $data['phone'],
            'address_snapshot' => $data['address'],
            'eircode_snapshot' => $data['eircode'],
            'electoral_area_snapshot' => $data['electoral_area'],
            'registration_data' => $data['registration_data'],
        ];
    }

    /**
     * Build activity data for the created event registration.
     *
     * @param array<string, mixed> $contact Contact row.
     * @param int $registrationId Registration ID.
     * @param array<string, mixed> $data Normalized workflow data.
     * @return array<string, mixed>
     */
    private function buildActivityData(array $contact, int $registrationId, array $data): array
    {
        return [
            'contact_id' => (int) $contact['id'],
            'activity_type' => 'event_registration',
            'related_id' => $registrationId,
            'summary' => $this->buildActivitySummary($data),
        ];
    }

    /**
     * Build a lightweight activity summary.
     *
     * @param array<string, mixed> $data Normalized workflow data.
     * @return string Activity summary.
     */
    private function buildActivitySummary(array $data): string
    {
        $eventId = isset($data['event_id']) ? (int) $data['event_id'] : 0;

        if ($eventId > 0) {
            return 'Event registration submitted for event #' . $eventId;
        }

        return 'Event registration submitted';
    }

    /**
     * Normalize scalar input to a trimmed string.
     *
     * @param mixed $value Raw value.
     * @return string Trimmed string value.
     */
    private function stringValue($value): string
    {
        if (is_array($value) || is_object($value)) {
            return '';
        }

        return trim((string) $value);
    }

    /**
     * Build a consistent workflow result.
     *
     * @param bool $success Whether the workflow fully succeeded.
     * @param array<string, mixed>|null $contact Contact row.
     * @param int $registrationId Registration ID.
     * @param int $activityId Activity ID.
     * @param string|null $error Optional error code.
     * @return array<string, mixed>
     */
    private function buildResult(
        bool $success,
        ?array $contact,
        int $registrationId,
        int $activityId,
        ?string $error
    ): array {
        return [
            'success' => $success,
            'contact' => $contact,
            'registration_id' => $registrationId,
            'activity_id' => $activityId,
            'error' => $error,
        ];
    }
}
