<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Threads\Responses\Services;

use CivicPlatform\Modules\Threads\Repository\ThreadRepository;
use CivicPlatform\Modules\Threads\Repository\ThreadResponseRepository;
use CivicPlatform\Services\ActivityService;
use CivicPlatform\Services\ContactService;

/**
 * Coordinates public consultation response submissions.
 *
 * This service validates consultation availability, synchronizes latest contact
 * details, and stores immutable response snapshots. Request handling, nonce
 * checks, rendering, and moderation belong elsewhere.
 */
class ThreadResponseService
{
    /**
     * Thread response repository.
     *
     * @var ThreadResponseRepository
     */
    private ThreadResponseRepository $responses;

    /**
     * Thread repository.
     *
     * @var ThreadRepository
     */
    private ThreadRepository $threads;

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
     * @param ThreadRepository $threads Thread repository.
     * @param ContactService $contacts Contact service.
     * @param ActivityService $activities Activity service.
     */
    public function __construct(
        ThreadResponseRepository $responses,
        ThreadRepository $threads,
        ContactService $contacts,
        ActivityService $activities
    ) {
        $this->responses = $responses;
        $this->threads = $threads;
        $this->contacts = $contacts;
        $this->activities = $activities;
    }

    /**
     * Submit a public consultation response.
     *
     * Expected keys: thread_id, name, email, phone, address, eircode,
     * response_text, and custom_fields.
     *
     * @param array<string, mixed> $data Submission data.
     * @return array<string, mixed> Structured workflow result.
     */
    public function submit(array $data): array
    {
        $normalized = $this->normalizeSubmissionData($data);

        if ($normalized['thread_id'] <= 0) {
            return $this->buildResult(false, null, 0, 'invalid_thread_id');
        }

        if ('' === $normalized['name'] || '' === $normalized['email'] || '' === $normalized['response_text']) {
            return $this->buildResult(false, null, 0, 'validation_failed');
        }

        $thread = $this->threads->findPublicById((int) $normalized['thread_id']);

        if (!is_array($thread)) {
            return $this->buildResult(false, null, 0, 'thread_not_found');
        }

        if (!$this->threads->isAcceptingResponses($thread)) {
            return $this->buildResult(false, null, 0, 'responses_closed');
        }

        $contactResult = $this->contacts->syncContact($normalized);
        $contact = $contactResult['contact'] ?? null;

        if (!is_array($contact) || empty($contact['id'])) {
            return $this->buildResult(false, null, 0, 'contact_sync_failed');
        }

        $responseId = $this->responses->create($this->buildResponseData($normalized, (int) $contact['id']));

        if ($responseId <= 0) {
            return $this->buildResult(false, $contact, 0, 'response_create_failed');
        }

        $activityId = $this->activities->log($this->buildActivityData($contact, $responseId, $normalized));

        if ($activityId <= 0) {
            return $this->buildResult(false, $contact, $responseId, 'activity_create_failed');
        }

        return $this->buildResult(true, $contact, $responseId, null, $activityId);
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
            'thread_id' => isset($data['thread_id']) ? (int) $data['thread_id'] : 0,
            'name' => $this->stringValue($data['name'] ?? ''),
            'email' => $this->stringValue($data['email'] ?? ''),
            'phone' => $this->stringValue($data['phone'] ?? ''),
            'address' => $this->stringValue($data['address'] ?? ''),
            'eircode' => $this->stringValue($data['eircode'] ?? ''),
            'electoral_area_id' => isset($data['electoral_area_id']) ? (int) $data['electoral_area_id'] : 0,
            'electoral_area' => $this->stringValue($data['electoral_area'] ?? ''),
            'consent_email' => !empty($data['consent_email']) ? 1 : 0,
            'consent_call' => !empty($data['consent_call']) ? 1 : 0,
            'consent_sms' => !empty($data['consent_sms']) ? 1 : 0,
            'consent_post' => !empty($data['consent_post']) ? 1 : 0,
            'response_text' => $this->stringValue($data['response_text'] ?? ''),
            'custom_fields' => $this->customFields($data['custom_fields'] ?? []),
        ];
    }

    /**
     * Build data for civic_thread_responses while preserving submitted snapshots.
     *
     * @param array<string, mixed> $data Normalized submission data.
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
            'electoral_area_id' => $data['electoral_area_id'],
            'electoral_area_snapshot' => $data['electoral_area'],
            'response_data' => [
                'response_text' => $data['response_text'],
                'custom_fields' => $data['custom_fields'],
            ],
            'is_public' => 0,
        ];
    }

    /**
     * Build activity data for the created response.
     *
     * @param array<string, mixed> $contact Contact row.
     * @param int $responseId Response ID.
     * @param array<string, mixed> $data Normalized submission data.
     * @return array<string, mixed>
     */
    private function buildActivityData(array $contact, int $responseId, array $data): array
    {
        return [
            'contact_id' => (int) $contact['id'],
            'activity_type' => 'thread_response',
            'related_id' => $responseId,
            'summary' => 'Thread response submitted for thread #' . (int) $data['thread_id'],
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
     * Normalize custom field values.
     *
     * @param mixed $value Raw custom field values.
     * @return array<string, string>
     */
    private function customFields($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $customFields = [];

        foreach ($value as $key => $fieldValue) {
            if (is_array($fieldValue) || is_object($fieldValue)) {
                continue;
            }

            $fieldKey = function_exists('sanitize_key')
                ? sanitize_key((string) $key)
                : trim((string) $key);

            if ('' === $fieldKey) {
                continue;
            }

            $customFields[$fieldKey] = trim((string) $fieldValue);
        }

        return $customFields;
    }

    /**
     * Build a consistent submission result.
     *
     * @param bool $success Whether the response was created.
     * @param array<string, mixed>|null $contact Contact row.
     * @param int $responseId Created response ID.
     * @param string|null $error Optional error code.
     * @param int $activityId Created activity ID.
     * @return array<string, mixed>
     */
    private function buildResult(
        bool $success,
        ?array $contact,
        int $responseId,
        ?string $error,
        int $activityId = 0
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
