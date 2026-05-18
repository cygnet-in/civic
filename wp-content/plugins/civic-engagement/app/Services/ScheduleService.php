<?php

declare(strict_types=1);

namespace CivicPlatform\Services;

use CivicPlatform\Modules\Schedules\Repository\ScheduleRepository;

/**
 * Coordinates lightweight schedule creation workflows.
 *
 * This service creates schedules, optionally links them to a source record, and
 * logs a schedule activity when contact context is provided. It does not
 * automate source workflows or synchronize schedule changes back to sources.
 */
class ScheduleService
{
    /**
     * Supported source type values.
     *
     * @var array<int, string>
     */

    private const SOURCE_TYPES = [
        'rep',
        'thread',
    ];

    /**
     * Schedule repository.
     *
     * @var ScheduleRepository
     */
    private ScheduleRepository $schedules;

    /**
     * Activity service.
     *
     * @var ActivityService
     */
    private ActivityService $activities;

    /**
     * @param ScheduleRepository $schedules Schedule repository.
     * @param ActivityService $activities Activity service.
     */
    public function __construct(ScheduleRepository $schedules, ActivityService $activities)
    {
        $this->schedules = $schedules;
        $this->activities = $activities;
    }

    /**
     * Create a schedule.
     *
     * Expected workflow keys include type, title, details, reported_by, status,
     * review_date, internal_comment, response, is_public, start_date, end_date,
     * source_type, source_id, created_by, and optional contact_id for activity
     * logging.
     *
     * Return shape:
     * [
     *     'success' => bool,
     *     'schedule' => array|null,
     *     'schedule_id' => int,
     *     'activity_id' => int,
     *     'error' => string|null,
     * ]
     *
     * @param array<string, mixed> $data Workflow data.
     * @return array<string, mixed> Creation result.
     */
    public function create(array $data): array
    {
        $normalized = $this->normalizeScheduleData($data);

        if (!$this->hasValidSource($normalized)) {
            return $this->buildResult(false, null, 0, 0, 'invalid_source');
        }

        $scheduleId = $this->schedules->create($this->buildScheduleData($normalized));

        if ($scheduleId <= 0) {
            return $this->buildResult(false, null, 0, 0, 'schedule_create_failed');
        }

        $schedule = $this->schedules->findById($scheduleId);
        $activityId = $this->logActivityIfNeeded($scheduleId, $normalized);

        if ((int) $normalized['contact_id'] > 0 && $activityId <= 0) {
            return $this->buildResult(false, $schedule, $scheduleId, 0, 'activity_create_failed');
        }

        return $this->buildResult(true, $schedule, $scheduleId, $activityId, null);
    }

    /**
     * Create a schedule linked to a rep or thread source.
     *
     * Source linking is one-way only. It does not trigger workflow automation or
     * bidirectional synchronization.
     *
     * @param string $sourceType Source type.
     * @param int $sourceId Source record ID.
     * @param array<string, mixed> $data Schedule workflow data.
     * @return array<string, mixed> Creation result.
     */
    public function createFromSource(string $sourceType, int $sourceId, array $data = []): array
    {
        $sourceType = trim($sourceType);

        if (!$this->isSupportedSourceType($sourceType) || $sourceId <= 0) {
            return $this->buildResult(false, null, 0, 0, 'invalid_source');
        }

        $data['source_type'] = $sourceType;
        $data['source_id'] = $sourceId;

        return $this->create($data);
    }

    /**
     * Get public schedules.
     *
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getPublicSchedules(array $args = []): array
    {
        return $this->schedules->getPublicSchedules($args);
    }

    /**
     * Get archived schedules.
     *
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getArchivedSchedules(array $args = []): array
    {
        return $this->schedules->getArchive($args);
    }

    /**
     * Get a paginated schedule listing.
     *
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getPaginated(array $args = []): array
    {
        return $this->schedules->getPaginated($args);
    }

    /**
     * Find a schedule by ID.
     *
     * @param int $id Schedule ID.
     * @return array<string, mixed>|null Schedule row or null when not found.
     */
    public function findById(int $id): ?array
    {
        return $this->schedules->findById($id);
    }

    /**
     * Normalize workflow input into repository-ready values.
     *
     * @param array<string, mixed> $data Raw workflow data.
     * @return array<string, mixed>
     */
    private function normalizeScheduleData(array $data): array
    {
        return [
            'type' => $this->stringValue($data['type'] ?? 'other'),
            'title' => $this->stringValue($data['title'] ?? ''),
            'details' => $this->stringValue($data['details'] ?? ''),
            'reported_by' => $this->stringValue($data['reported_by'] ?? ''),
            'status' => $this->stringValue($data['status'] ?? 'draft'),
            'review_date' => $this->stringValue($data['review_date'] ?? ''),
            'internal_comment' => $this->stringValue($data['internal_comment'] ?? ''),
            'response' => $this->stringValue($data['response'] ?? ''),
            'is_public' => !empty($data['is_public']) ? 1 : 0,
            'start_date' => $this->stringValue($data['start_date'] ?? ''),
            'end_date' => $this->stringValue($data['end_date'] ?? ''),
            'source_type' => $this->stringValue($data['source_type'] ?? ''),
            'source_id' => isset($data['source_id']) ? (int) $data['source_id'] : 0,
            'created_by' => isset($data['created_by']) ? (int) $data['created_by'] : 0,
            'contact_id' => isset($data['contact_id']) ? (int) $data['contact_id'] : 0,
        ];
    }

    /**
     * Build data for civic_schedules.
     *
     * @param array<string, mixed> $data Normalized workflow data.
     * @return array<string, mixed>
     */
    private function buildScheduleData(array $data): array
    {
        $schedule = [
            'type' => $data['type'],
            'title' => $data['title'],
            'details' => $data['details'],
            'reported_by' => $data['reported_by'],
            'status' => $data['status'],
            'review_date' => $data['review_date'],
            'internal_comment' => $data['internal_comment'],
            'response' => $data['response'],
            'is_public' => $data['is_public'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ];

        if ('' !== $data['source_type']) {
            $schedule['source_type'] = $data['source_type'];
            $schedule['source_id'] = $data['source_id'];
        }

        if ((int) $data['created_by'] > 0) {
            $schedule['created_by'] = $data['created_by'];
        }

        return $schedule;
    }

    /**
     * Validate optional source link data.
     *
     * @param array<string, mixed> $data Normalized workflow data.
     * @return bool True when no source is provided or the source link is valid.
     */
    private function hasValidSource(array $data): bool
    {
        if ('' === $data['source_type']) {
            return (int) $data['source_id'] <= 0;
        }

        return $this->isSupportedSourceType((string) $data['source_type'])
            && (int) $data['source_id'] > 0;
    }

    /**
     * Check whether a source type is supported.
     *
     * @param string $sourceType Source type.
     * @return bool True when supported.
     */
    private function isSupportedSourceType(string $sourceType): bool
    {
        return in_array(trim($sourceType), $this->sourceTypes, true);
    }

    /**
     * Log a schedule activity when contact context is supplied.
     *
     * @param int $scheduleId Schedule ID.
     * @param array<string, mixed> $data Normalized workflow data.
     * @return int Inserted activity ID, or 0 when not logged.
     */
    private function logActivityIfNeeded(int $scheduleId, array $data): int
    {
        if ((int) $data['contact_id'] <= 0) {
            return 0;
        }

        return $this->activities->log(
            [
                'contact_id' => (int) $data['contact_id'],
                'activity_type' => 'schedule',
                'related_id' => $scheduleId,
                'summary' => $this->buildActivitySummary($data),
            ]
        );
    }

    /**
     * Build a lightweight activity summary.
     *
     * @param array<string, mixed> $data Normalized workflow data.
     * @return string Activity summary.
     */
    private function buildActivitySummary(array $data): string
    {
        $title = $this->stringValue($data['title'] ?? '');

        if ('' !== $title) {
            return 'Schedule created: ' . $title;
        }

        return 'Schedule created';
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
     * @param array<string, mixed>|null $schedule Schedule row.
     * @param int $scheduleId Schedule ID.
     * @param int $activityId Activity ID.
     * @param string|null $error Optional error code.
     * @return array<string, mixed>
     */
    private function buildResult(
        bool $success,
        ?array $schedule,
        int $scheduleId,
        int $activityId,
        ?string $error
    ): array {
        return [
            'success' => $success,
            'schedule' => $schedule,
            'schedule_id' => $scheduleId,
            'activity_id' => $activityId,
            'error' => $error,
        ];
    }
}
