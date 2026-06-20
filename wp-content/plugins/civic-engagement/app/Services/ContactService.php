<?php

declare(strict_types=1);

namespace CivicPlatform\Services;

use CivicPlatform\Modules\Users\Repository\ContactRepository;

/**
 * Coordinates latest contact data synchronization.
 *
 * This service uses email as the public identity key and stores only the latest
 * contact details in civic_contacts. Submitted snapshots remain the
 * responsibility of module repositories such as reps, threads, and events.
 */
class ContactService
{
    /**
     * Contact repository.
     *
     * @var ContactRepository
     */
    private ContactRepository $contacts;

    /**
     * @param ContactRepository $contacts Contact repository.
     */
    public function __construct(ContactRepository $contacts)
    {
        $this->contacts = $contacts;
    }

    /**
     * Synchronize latest contact details by email.
     *
     * Expected input keys may use public workflow names such as name, phone,
     * whatsapp, address, eircode, and electoral_area, or their latest_* column
     * equivalents.
     *
     * Return shape:
     * [
     *     'contact' => array|null,
     *     'created' => bool,
     *     'updated' => bool,
     *     'error' => string|null,
     * ]
     *
     * @param array<string, mixed> $data Contact data.
     * @return array<string, mixed> Synchronization result.
     */
    public function syncContact(array $data): array
    {
        $email = $this->normalizeEmail($data['email'] ?? '');

        if ('' === $email) {
            return $this->buildResult(null, false, false, 'email_required');
        }

        $createData = $this->mapContactData($data, $email, true);
        $existing = $this->contacts->findByEmail($email);

        if (is_array($existing)) {
            $updateData = $this->mapContactData($data, $email, false);
            $updateData = $this->promoteConsent($updateData, $existing);
            $updated = empty($updateData)
                ? false
                : $this->contacts->updateLatestDetails((int) $existing['id'], $updateData);
            $contact = $this->contacts->findById((int) $existing['id']) ?: $existing;

            return $this->buildResult($contact, false, $updated, null);
        }

        $contactId = $this->contacts->create($createData);

        if ($contactId <= 0) {
            return $this->buildResult(null, false, false, 'contact_create_failed');
        }

        return $this->buildResult($this->contacts->findById($contactId), true, false, null);
    }

    /**
     * Find a contact by email.
     *
     * @param string $email Email address.
     * @return array<string, mixed>|null Contact row or null when not found.
     */
    public function findByEmail(string $email): ?array
    {
        $email = $this->normalizeEmail($email);

        if ('' === $email) {
            return null;
        }

        return $this->contacts->findByEmail($email);
    }

    /**
     * Find a contact by ID.
     *
     * @param int $id Contact ID.
     * @return array<string, mixed>|null Contact row or null when not found.
     */
    public function findById(int $id): ?array
    {
        return $this->contacts->findById($id);
    }

    /**
     * Get a paginated contact listing.
     *
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed>
     */
    public function getPaginated(array $args = []): array
    {
        return $this->contacts->getPaginated($args);
    }

    /**
     * Search contacts by keyword.
     *
     * @param string $keyword Search keyword.
     * @param array<string, mixed> $args Listing arguments.
     * @return array<string, mixed>
     */
    public function search(string $keyword, array $args = []): array
    {
        return $this->contacts->search($keyword, $args);
    }

    /**
     * Get contacts for export.
     *
     * @param array<string, mixed> $args Export arguments.
     * @return array<int, array<string, mixed>>
     */
    public function getForExport(array $args = []): array
    {
        return $this->contacts->getForExport($args);
    }

    /**
     * Map workflow contact data to latest contact columns.
     *
     * @param array<string, mixed> $data Raw workflow data.
     * @param string $email Normalized email address.
     * @param bool $includeMissing Whether to include absent detail fields as empty strings.
     * @return array<string, mixed>
     */
    private function mapContactData(array $data, string $email, bool $includeMissing): array
    {
        $mapped = $includeMissing ? ['email' => $email] : [];
        $fields = [
            'latest_name' => ['latest_name', 'name'],
            'latest_phone' => ['latest_phone', 'phone'],
            'latest_whatsapp' => ['latest_whatsapp', 'whatsapp'],
            'latest_address' => ['latest_address', 'address'],
            'latest_eircode' => ['latest_eircode', 'eircode'],
            'latest_electoral_area' => ['latest_electoral_area', 'electoral_area'],
        ];

        foreach ($fields as $column => $keys) {
            $value = $this->firstAvailableValue($data, $keys, $includeMissing);

            if (null !== $value) {
                $mapped[$column] = $this->stringValue($value);
            }
        }

        $consentFields = [
            'consent_email',
            'consent_call',
            'consent_sms',
            'consent_post',
        ];
        $hasConsent = $includeMissing;

        foreach ($consentFields as $field) {
            if (array_key_exists($field, $data)) {
                $hasConsent = true;
            }

            if ($includeMissing || array_key_exists($field, $data)) {
                $mapped[$field] = !empty($data[$field]) ? 1 : 0;
            }
        }

        if ($hasConsent) {
            $mapped['consent_updated_at'] = current_time('mysql');
        }

        return $mapped;
    }

    /**
     * Retain existing consent and only promote submitted consent from no to yes.
     *
     * @param array<string, mixed> $updateData Mapped latest contact data.
     * @param array<string, mixed> $existing Existing contact record.
     * @return array<string, mixed>
     */
    private function promoteConsent(array $updateData, array $existing): array
    {
        $consentFields = [
            'consent_email',
            'consent_call',
            'consent_sms',
            'consent_post',
        ];
        $promoted = false;

        foreach ($consentFields as $field) {
            $submitted = !empty($updateData[$field]);
            unset($updateData[$field]);

            if ($submitted && empty($existing[$field])) {
                $updateData[$field] = 1;
                $promoted = true;
            }
        }

        if (!$promoted) {
            unset($updateData['consent_updated_at']);
        }

        return $updateData;
    }

    /**
     * Get the first provided value from the given keys.
     *
     * @param array<string, mixed> $data Raw workflow data.
     * @param array<int, string> $keys Accepted keys.
     * @param bool $includeMissing Whether to return an empty string for missing values.
     * @return mixed|null
     */
    private function firstAvailableValue(array $data, array $keys, bool $includeMissing)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                return $data[$key];
            }
        }

        return $includeMissing ? '' : null;
    }

    /**
     * Normalize an email address for identity lookup.
     *
     * @param mixed $email Raw email value.
     * @return string Normalized email address.
     */
    private function normalizeEmail($email): string
    {
        $email = strtolower(trim((string) $email));       

        if (function_exists('sanitize_email')) {
            $email = sanitize_email($email);
        }

        if (function_exists('is_email') && !is_email($email)) {
            return '';
        }

        return $email;
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
     * Build a consistent synchronization result.
     *
     * @param array<string, mixed>|null $contact Contact row.
     * @param bool $created Whether a contact was created.
     * @param bool $updated Whether an existing contact was updated.
     * @param string|null $error Optional error code.
     * @return array<string, mixed>
     */
    private function buildResult(?array $contact, bool $created, bool $updated, ?string $error): array
    {
        return [
            'contact' => $contact,
            'created' => $created,
            'updated' => $updated,
            'error' => $error,
        ];
    }
}
