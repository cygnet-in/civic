<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Events\Registrations\Services;

use CivicPlatform\Modules\Events\Repository\EventRegistrationRepository;
use CivicPlatform\Modules\Events\Repository\EventRepository;
use CivicPlatform\Services\ActivityService;
use CivicPlatform\Services\ContactService;

/**
 * Coordinates public event registration submissions.
 *
 * This service validates event availability, synchronizes latest contact
 * details, and stores immutable registration snapshots. Request handling,
 * nonce checks, and rendering belong elsewhere.
 */
class EventRegistrationService
{
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
     * @param EventRepository $events Event repository.
     * @param ContactService $contacts Contact service.
     * @param ActivityService $activities Activity service.
     */
    public function __construct(
        EventRegistrationRepository $registrations,
        EventRepository $events,
        ContactService $contacts,
        ActivityService $activities
    ) {
        $this->registrations = $registrations;
        $this->events = $events;
        $this->contacts = $contacts;
        $this->activities = $activities;
    }

    /**
     * Submit a public event registration.
     *
     * Expected keys: event_id, name, email, phone, address, eircode,
     * electoral_area, and registration_data.
     *
     * @param array<string, mixed> $data Submission data.
     * @return array<string, mixed> Structured workflow result.
     */
    public function submit(array $data): array
    {
        $normalized = $this->normalizeSubmissionData($data);

        if ($normalized['event_id'] <= 0) {
            return $this->buildResult(false, null, 0, 'invalid_event_id');
        }

        if ('' === $normalized['name'] || '' === $normalized['email']) {
            return $this->buildResult(false, null, 0, 'validation_failed');
        }

        $event = $this->events->findById((int) $normalized['event_id']);

        if (!is_array($event)) {
            return $this->buildResult(false, null, 0, 'event_not_found');
        }

        if (!$this->events->isAcceptingRegistrations($event)) {
            return $this->buildResult(false, null, 0, 'registration_closed');
        }

        $contactResult = $this->contacts->syncContact($normalized);
        $contact = $contactResult['contact'] ?? null;

        if (!is_array($contact) || empty($contact['id'])) {
            return $this->buildResult(false, null, 0, 'contact_sync_failed');
        }

        $registrationId = $this->registrations->create(
            $this->buildRegistrationData($normalized, (int) $contact['id'])
        );

        if ($registrationId <= 0) {
            return $this->buildResult(false, $contact, 0, 'registration_create_failed');
        }

        $activityId = $this->activities->log($this->buildActivityData($contact, $registrationId, $normalized));

        if ($activityId <= 0) {
            return $this->buildResult(false, $contact, $registrationId, 'activity_create_failed');
        }

        return $this->buildResult(true, $contact, $registrationId, null, $activityId);
    }

    /**
     * Normalize workflow input into repository-ready scalar values.
     *
     * @param array<string, mixed> $data Raw submission data.
     * @return array<string, mixed>
     */
    private function normalizeSubmissionData(array $data): array
    {
        return [
            'event_id' => isset($data['event_id']) ? (int) $data['event_id'] : 0,
            'name' => $this->stringValue($data['name'] ?? ''),
            'email' => $this->stringValue($data['email'] ?? ''),
            'phone' => $this->stringValue($data['phone'] ?? ''),
            'address' => $this->stringValue($data['address'] ?? ''),
            'eircode' => $this->stringValue($data['eircode'] ?? ''),
            'electoral_area' => $this->stringValue($data['electoral_area'] ?? ''),
            'consent_email' => !empty($data['consent_email']) ? 1 : 0,
            'consent_call' => !empty($data['consent_call']) ? 1 : 0,
            'consent_sms' => !empty($data['consent_sms']) ? 1 : 0,
            'consent_post' => !empty($data['consent_post']) ? 1 : 0,
            'registration_data' => is_array($data['registration_data'] ?? null) ? $data['registration_data'] : [],
        ];
    }

    /**
     * Build data for civic_event_registrations while preserving submitted snapshots.
     *
     * @param array<string, mixed> $data Normalized submission data.
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
     * @param array<string, mixed> $data Normalized submission data.
     * @return array<string, mixed>
     */
    private function buildActivityData(array $contact, int $registrationId, array $data): array
    {
        return [
            'contact_id' => (int) $contact['id'],
            'activity_type' => 'event_registration',
            'related_id' => $registrationId,
            'summary' => 'Event registration submitted for event #' . (int) $data['event_id'],
        ];
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
     * Build a consistent submission result.
     *
     * @param bool $success Whether the registration was created.
     * @param array<string, mixed>|null $contact Contact row.
     * @param int $registrationId Created registration ID.
     * @param string|null $error Optional error code.
     * @param int $activityId Created activity ID.
     * @return array<string, mixed>
     */
    private function buildResult(
        bool $success,
        ?array $contact,
        int $registrationId,
        ?string $error,
        int $activityId = 0
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
