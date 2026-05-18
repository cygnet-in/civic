<?php

declare(strict_types=1);

namespace CivicPlatform\Services;

use CivicPlatform\Modules\Activities\Repository\ActivityRepository;

/**
 * Coordinates standardized activity logging.
 *
 * This service validates common activity input and delegates persistence and
 * retrieval to ActivityRepository. It does not create module records or perform
 * workflow orchestration.
 */
class ActivityService
{
    /**
     * Supported activity type values.
     *
     * @var array<int, string>
     */
    private array $activityTypes = [
        'rep',
        'thread_response',
        'event_registration',
        'schedule',
        'manual',
    ];

    /**
     * Activity repository.
     *
     * @var ActivityRepository
     */
    private ActivityRepository $activities;

    /**
     * @param ActivityRepository $activities Activity repository.
     */
    public function __construct(ActivityRepository $activities)
    {
        $this->activities = $activities;
    }

    /**
     * Log a standardized activity entry.
     *
     * Expected fields: contact_id, activity_type, related_id, summary.
     *
     * @param array<string, mixed> $data Activity data.
     * @return int Inserted activity ID, or 0 on invalid input/failure.
     */
    public function log(array $data): int
    {
        $activity = $this->normalizeActivityData($data);

        if (!$this->isValidActivityData($activity)) {
            return 0;
        }

        return $this->activities->create($activity);
    }

    /**
     * Get paginated activities for a contact.
     *
     * @param int $contactId Contact ID.
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getByContactId(int $contactId, array $args = []): array
    {
        return $this->activities->findByContactId($contactId, $args);
    }

    /**
     * Normalize activity data into repository column names.
     *
     * @param array<string, mixed> $data Raw activity data.
     * @return array<string, mixed>
     */
    private function normalizeActivityData(array $data): array
    {
        return [
            'contact_id' => isset($data['contact_id']) ? (int) $data['contact_id'] : 0,
            'activity_type' => $this->stringValue($data['activity_type'] ?? ''),
            'related_id' => isset($data['related_id']) ? (int) $data['related_id'] : 0,
            'summary' => $this->stringValue($data['summary'] ?? ''),
        ];
    }

    /**
     * Validate normalized activity data.
     *
     * @param array<string, mixed> $data Normalized activity data.
     * @return bool True when the data can be logged.
     */
    private function isValidActivityData(array $data): bool
    {
        return (int) $data['contact_id'] > 0
            && $this->isSupportedActivityType((string) $data['activity_type']);
    }

    /**
     * Check whether an activity type is supported.
     *
     * @param string $activityType Activity type.
     * @return bool True when supported.
     */
    private function isSupportedActivityType(string $activityType): bool
    {
        return in_array(trim($activityType), $this->activityTypes, true);
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
}
