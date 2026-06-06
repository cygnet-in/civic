<?php

declare(strict_types=1);

namespace CivicPlatform\Services;

use CivicPlatform\Modules\Reps\Repository\RepRepository;

/**
 * Coordinates the representation submission workflow.
 *
 * This service synchronizes latest contact details, stores the submitted
 * snapshot on the rep row, and logs the activity. Request handling, nonce
 * checks, rendering, redirects, and notifications belong elsewhere.
 */
class RepService
{
    /**
     * Rep repository.
     *
     * @var RepRepository
     */
    private RepRepository $reps;

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
     * @param RepRepository $reps Rep repository.
     * @param ContactService $contacts Contact service.
     * @param ActivityService $activities Activity service.
     */
    public function __construct(
        RepRepository $reps,
        ContactService $contacts,
        ActivityService $activities
    ) {
        $this->reps = $reps;
        $this->contacts = $contacts;
        $this->activities = $activities;
    }

    /**
     * Submit a representation.
     *
     * Expected workflow keys include name, email, phone, whatsapp, address,
     * eircode, electoral_area, title, details, map_lat, map_lng, and status.
     *
     * Return shape:
     * [
     *     'success' => bool,
     *     'contact' => array|null,
     *     'rep' => array|null,
     *     'rep_id' => int,
     *     'activity_id' => int,
     *     'error' => string|null,
     * ]
     *
     * @param array<string, mixed> $data Workflow data.
     * @return array<string, mixed> Submission result.
     */
    public function submitRep(array $data): array
    {
        $normalized = $this->normalizeSubmissionData($data);
        $contactResult = $this->contacts->syncContact($normalized);
        $contact = $contactResult['contact'] ?? null;

        if (!is_array($contact) || empty($contact['id'])) {
            return $this->buildResult(false, null, null, 0, 0, 'contact_sync_failed');
        }

        $repId = $this->reps->create($this->buildRepData($normalized, (int) $contact['id']));

        if ($repId <= 0) {
            return $this->buildResult(false, $contact, null, 0, 0, 'rep_create_failed');
        }

        $rep = $this->reps->findById($repId);
        $activityId = $this->activities->log($this->buildActivityData($contact, $repId, $normalized));

        if ($activityId <= 0) {
            return $this->buildResult(false, $contact, $rep, $repId, 0, 'activity_create_failed');
        }

        return $this->buildResult(true, $contact, $rep, $repId, $activityId, null);
    }

    /**
     * Find a rep by ID.
     *
     * @param int $id Rep ID.
     * @return array<string, mixed>|null Rep row or null when not found.
     */
    public function findById(int $id): ?array
    {
        return $this->reps->findById($id);
    }

    /**
     * Get a paginated rep listing.
     *
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getPaginated(array $args = []): array
    {
        return $this->reps->getPaginated($args);
    }

    /**
     * Normalize workflow input into simple scalar values.
     *
     * @param array<string, mixed> $data Raw workflow data.
     * @return array<string, mixed>
     */
    private function normalizeSubmissionData(array $data): array
    {
        return [
            'name' => $this->stringValue($data['name'] ?? $data['latest_name'] ?? ''),
            'email' => $this->stringValue($data['email'] ?? ''),
            'phone' => $this->stringValue($data['phone'] ?? $data['latest_phone'] ?? ''),
            'whatsapp' => $this->stringValue($data['whatsapp'] ?? $data['latest_whatsapp'] ?? ''),
            'address' => $this->stringValue($data['address'] ?? $data['latest_address'] ?? ''),
            'eircode' => $this->stringValue($data['eircode'] ?? $data['latest_eircode'] ?? ''),
            'electoral_area_id' => isset($data['electoral_area_id']) ? (int) $data['electoral_area_id'] : 0,
            'electoral_area' => $this->stringValue(
                $data['electoral_area'] ?? $data['latest_electoral_area'] ?? ''
            ),
            'title' => $this->stringValue($data['title'] ?? ''),
            'details' => $this->stringValue($data['details'] ?? ''),
            'map_lat' => $this->numericValue($data['map_lat'] ?? null),
            'map_lng' => $this->numericValue($data['map_lng'] ?? null),
            'status' => $this->stringValue($data['status'] ?? 'new'),
        ];
    }

    /**
     * Build data for civic_reps while preserving submitted snapshot values.
     *
     * @param array<string, mixed> $data Normalized workflow data.
     * @param int $contactId Contact ID.
     * @return array<string, mixed>
     */
    private function buildRepData(array $data, int $contactId): array
    {
        $repData = [
            'contact_id' => $contactId,
            'name_snapshot' => $data['name'],
            'email_snapshot' => $data['email'],
            'phone_snapshot' => $data['phone'],
            'whatsapp_snapshot' => $data['whatsapp'],
            'address_snapshot' => $data['address'],
            'eircode_snapshot' => $data['eircode'],
            'electoral_area_id' => $data['electoral_area_id'],
            'electoral_area_snapshot' => $data['electoral_area'],
            'title' => $data['title'],
            'details' => $data['details'],
            'status' => $data['status'],
        ];

        if (null !== $data['map_lat']) {
            $repData['map_lat'] = $data['map_lat'];
        }

        if (null !== $data['map_lng']) {
            $repData['map_lng'] = $data['map_lng'];
        }

        return $repData;
    }

    /**
     * Build activity data for the created rep.
     *
     * @param array<string, mixed> $contact Contact row.
     * @param int $repId Rep ID.
     * @param array<string, mixed> $data Normalized workflow data.
     * @return array<string, mixed>
     */
    private function buildActivityData(array $contact, int $repId, array $data): array
    {
        return [
            'contact_id' => (int) $contact['id'],
            'activity_type' => 'rep',
            'related_id' => $repId,
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
        $title = $this->stringValue($data['title'] ?? '');

        if ('' !== $title) {
            return 'Rep submitted: ' . $title;
        }

        return 'Rep submitted';
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
     * Normalize numeric input.
     *
     * @param mixed $value Raw value.
     * @return float|null Numeric value or null when absent/invalid.
     */
    private function numericValue($value): ?float
    {
        if (is_array($value) || is_object($value)) {
            return null;
        }

        if (null === $value || '' === trim((string) $value) || !is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    /**
     * Build a consistent workflow result.
     *
     * @param bool $success Whether the workflow fully succeeded.
     * @param array<string, mixed>|null $contact Contact row.
     * @param array<string, mixed>|null $rep Rep row.
     * @param int $repId Rep ID.
     * @param int $activityId Activity ID.
     * @param string|null $error Optional error code.
     * @return array<string, mixed>
     */
    private function buildResult(
        bool $success,
        ?array $contact,
        ?array $rep,
        int $repId,
        int $activityId,
        ?string $error
    ): array {
        return [
            'success' => $success,
            'contact' => $contact,
            'rep' => $rep,
            'rep_id' => $repId,
            'activity_id' => $activityId,
            'error' => $error,
        ];
    }
}
