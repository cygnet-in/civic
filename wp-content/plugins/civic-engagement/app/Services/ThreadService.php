<?php

declare(strict_types=1);

namespace CivicPlatform\Services;

use CivicPlatform\Modules\Threads\Repository\ThreadResponseRepository;

/**
 * Coordinates the thread response submission workflow.
 *
 * This service synchronizes latest contact details, stores submitted snapshot
 * data on the response row, and logs the activity. Request handling, nonce
 * checks, rendering, redirects, and notifications belong elsewhere.
 */
class ThreadService
{
    /**
     * Thread response repository.
     *
     * @var ThreadResponseRepository
     */
    private ThreadResponseRepository $responses;

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
     * @param ThreadResponseRepository $responses Thread response repository.
     * @param ContactService $contacts Contact service.
     * @param ActivityService $activities Activity service.
     */
    public function __construct(
        ThreadResponseRepository $responses,
        ContactService $contacts,
        ActivityService $activities
    ) {
        $this->responses = $responses;
        $this->contacts = $contacts;
        $this->activities = $activities;
    }

    /**
     * Submit a thread response.
     *
     * Expected workflow keys include thread_id, name, email, phone, address,
     * eircode, electoral_area, response_data, and is_public.
     *
     * Return shape:
     * [
     *     'success' => bool,
     *     'contact' => array|null,
     *     'response_id' => int,
     *     'activity_id' => int,
     *     'error' => string|null,
     * ]
     *
     * @param array<string, mixed> $data Workflow data.
     * @return array<string, mixed> Submission result.
     */
    public function submitResponse(array $data): array
    {
        $normalized = $this->normalizeSubmissionData($data);
        if ($normalized['thread_id'] <= 0) {
            return $this->buildResult(false, null, 0, 0, 'invalid_thread_id');
        }
        $contactResult = $this->contacts->syncContact($normalized);
        $contact = $contactResult['contact'] ?? null;

        if (!is_array($contact) || empty($contact['id'])) {
            return $this->buildResult(false, null, 0, 0, 'contact_sync_failed');
        }

        $responseId = $this->responses->create($this->buildResponseData($normalized, (int) $contact['id']));

        if ($responseId <= 0) {
            return $this->buildResult(false, $contact, 0, 0, 'response_create_failed');
        }

        $activityId = $this->activities->log($this->buildActivityData($contact, $responseId, $normalized));

        if ($activityId <= 0) {
            return $this->buildResult(false, $contact, $responseId, 0, 'activity_create_failed');
        }

        return $this->buildResult(true, $contact, $responseId, $activityId, null);
    }

    /**
     * Get paginated responses for a thread.
     *
     * @param int $threadId Thread ID.
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getByThreadId(int $threadId, array $args = []): array
    {
        return $this->responses->findByThreadId($threadId, $args);
    }

    /**
     * Get a paginated response listing.
     *
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getPaginated(array $args = []): array
    {
        return $this->responses->getPaginated($args);
    }

    /**
     * Get public thread responses.
     *
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function getPublicResponses(array $args = []): array
    {
        return $this->responses->getPublicResponses($args);
    }

    /**
     * Search thread responses.
     *
     * @param string $keyword Search keyword.
     * @param array<string, mixed> $args Search arguments.
     * @return array<string, mixed> Paginated result set and metadata.
     */
    public function searchResponses(string $keyword, array $args = []): array
    {
        return $this->responses->search($keyword, $args);
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
            'thread_id' => isset($data['thread_id']) ? (int) $data['thread_id'] : 0,
            'name' => $this->stringValue($data['name'] ?? $data['latest_name'] ?? ''),
            'email' => $this->stringValue($data['email'] ?? ''),
            'phone' => $this->stringValue($data['phone'] ?? $data['latest_phone'] ?? ''),
            'address' => $this->stringValue($data['address'] ?? $data['latest_address'] ?? ''),
            'eircode' => $this->stringValue($data['eircode'] ?? $data['latest_eircode'] ?? ''),
            'electoral_area' => $this->stringValue(
                $data['electoral_area'] ?? $data['latest_electoral_area'] ?? ''
            ),
            'response_data' => $data['response_data'] ?? [],
            'is_public' => !empty($data['is_public']) ? 1 : 0,
        ];
    }

    /**
     * Build data for civic_thread_responses while preserving snapshots.
     *
     * @param array<string, mixed> $data Normalized workflow data.
     * @param int $contactId Contact ID.
     * @return array<string, mixed>
     */
    private function buildResponseData(array $data, int $contactId): array
    {
        return [
            'thread_id' => $data['thread_id'],
            'contact_id' => $contactId,
            'name_snapshot' => $data['name'],
            'email_snapshot' => $data['email'],
            'phone_snapshot' => $data['phone'],
            'address_snapshot' => $data['address'],
            'eircode_snapshot' => $data['eircode'],
            'electoral_area_snapshot' => $data['electoral_area'],
            'response_data' => $data['response_data'],
            'is_public' => $data['is_public'],
        ];
    }

    /**
     * Build activity data for the created thread response.
     *
     * @param array<string, mixed> $contact Contact row.
     * @param int $responseId Response ID.
     * @param array<string, mixed> $data Normalized workflow data.
     * @return array<string, mixed>
     */
    private function buildActivityData(array $contact, int $responseId, array $data): array
    {
        return [
            'contact_id' => (int) $contact['id'],
            'activity_type' => 'thread_response',
            'related_id' => $responseId,
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
        $threadId = isset($data['thread_id']) ? (int) $data['thread_id'] : 0;

        if ($threadId > 0) {
            return 'Thread response submitted for thread #' . $threadId;
        }

        return 'Thread response submitted';
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
     * @param int $responseId Response ID.
     * @param int $activityId Activity ID.
     * @param string|null $error Optional error code.
     * @return array<string, mixed>
     */
    private function buildResult(
        bool $success,
        ?array $contact,
        int $responseId,
        int $activityId,
        ?string $error
    ): array {
        return [
            'success' => $success,
            'contact' => $contact,
            'response_id' => $responseId,
            'activity_id' => $activityId,
            'error' => $error,
        ];
    }
}
