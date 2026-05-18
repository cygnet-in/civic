<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Reps\Admin;

use CivicPlatform\Services\ActivityService;
use CivicPlatform\Services\ContactService;
use CivicPlatform\Services\RepService;

/**
 * Renders a single representation detail page.
 *
 * This page handles request sanitization and presentation only. Rep, contact,
 * and activity data are loaded through services.
 */
class RepDetailPage
{
    /**
     * Required capability for viewing rep details.
     */
    private const CAPABILITY = 'manage_civic_reps';

    /**
     * Reps service.
     *
     * @var RepService
     */
    private RepService $reps;

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
     * @param RepService $reps Reps service.
     * @param ContactService $contacts Contact service.
     * @param ActivityService $activities Activity service.
     */
    public function __construct(
        RepService $reps,
        ContactService $contacts,
        ActivityService $activities
    ) {
        $this->reps = $reps;
        $this->contacts = $contacts;
        $this->activities = $activities;
    }

    /**
     * Render the representation detail page.
     *
     * @return void
     */
    public function render(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $repId = $this->repId();
        $rep = $this->reps->findById($repId);

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Representation Detail', 'civic-engagement') . '</h1>';
        echo '<p><a href="' . esc_url($this->listUrl()) . '">' . esc_html__('Back to Representations', 'civic-engagement') . '</a></p>';

        if (!is_array($rep)) {
            $this->renderNotFound();
            echo '</div>';

            return;
        }

        $contact = $this->contactForRep($rep);
        $activities = $this->activitiesForRep($rep);

        $this->renderSummary($rep);
        $this->renderSnapshot($rep);
        $this->renderContact($contact);
        $this->renderActivities($activities);

        echo '</div>';
    }

    /**
     * Render the main representation details.
     *
     * @param array<string, mixed> $rep Rep row.
     * @return void
     */
    private function renderSummary(array $rep): void
    {
        echo '<h2>' . esc_html__('Representation', 'civic-engagement') . '</h2>';
        echo '<table class="widefat striped"><tbody>';
        $this->renderDetailRow(__('Rep ID', 'civic-engagement'), (string) ($rep['id'] ?? ''));
        $this->renderDetailRow(__('Subject', 'civic-engagement'), (string) ($rep['title'] ?? ''));
        $this->renderDetailRow(__('Message', 'civic-engagement'), (string) ($rep['details'] ?? ''));
        $this->renderDetailRow(__('Status', 'civic-engagement'), (string) ($rep['status'] ?? ''));
        $this->renderDetailRow(__('Created At', 'civic-engagement'), (string) ($rep['created_at'] ?? ''));
        echo '</tbody></table>';
    }

    /**
     * Render submitted snapshot fields.
     *
     * @param array<string, mixed> $rep Rep row.
     * @return void
     */
    private function renderSnapshot(array $rep): void
    {
        echo '<h2>' . esc_html__('Submitted Snapshot', 'civic-engagement') . '</h2>';
        echo '<table class="widefat striped"><tbody>';
        $this->renderDetailRow(__('Name', 'civic-engagement'), (string) ($rep['name_snapshot'] ?? ''));
        $this->renderDetailRow(__('Email', 'civic-engagement'), (string) ($rep['email_snapshot'] ?? ''));
        $this->renderDetailRow(__('Phone', 'civic-engagement'), (string) ($rep['phone_snapshot'] ?? ''));
        $this->renderDetailRow(__('Address', 'civic-engagement'), (string) ($rep['address_snapshot'] ?? ''));
        echo '</tbody></table>';
    }

    /**
     * Render linked latest contact details.
     *
     * @param array<string, mixed>|null $contact Contact row.
     * @return void
     */
    private function renderContact(?array $contact): void
    {
        echo '<h2>' . esc_html__('Linked Contact', 'civic-engagement') . '</h2>';

        if (!is_array($contact)) {
            echo '<div class="notice notice-info inline"><p>' . esc_html__('No linked contact found.', 'civic-engagement') . '</p></div>';

            return;
        }

        echo '<table class="widefat striped"><tbody>';
        $this->renderDetailRow(__('Contact ID', 'civic-engagement'), (string) ($contact['id'] ?? ''));
        $this->renderDetailRow(__('Latest Name', 'civic-engagement'), (string) ($contact['latest_name'] ?? ''));
        $this->renderDetailRow(__('Email', 'civic-engagement'), (string) ($contact['email'] ?? ''));
        $this->renderDetailRow(__('Latest Phone', 'civic-engagement'), (string) ($contact['latest_phone'] ?? ''));
        $this->renderDetailRow(__('Latest Address', 'civic-engagement'), (string) ($contact['latest_address'] ?? ''));
        $this->renderDetailRow(__('Latest Eircode', 'civic-engagement'), (string) ($contact['latest_eircode'] ?? ''));
        $this->renderDetailRow(__('Latest Electoral Area', 'civic-engagement'), (string) ($contact['latest_electoral_area'] ?? ''));
        echo '</tbody></table>';
    }

    /**
     * Render related activities.
     *
     * @param array<int, array<string, mixed>> $activities Activity rows.
     * @return void
     */
    private function renderActivities(array $activities): void
    {
        echo '<h2>' . esc_html__('Related Activities', 'civic-engagement') . '</h2>';
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th scope="col">' . esc_html__('ID', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Type', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Summary', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Created', 'civic-engagement') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        if (empty($activities)) {
            echo '<tr><td colspan="4">' . esc_html__('No related activities found.', 'civic-engagement') . '</td></tr>';
        }

        foreach ($activities as $activity) {
            echo '<tr>';
            echo '<td>' . esc_html((string) ($activity['id'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($activity['activity_type'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($activity['summary'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($activity['created_at'] ?? '')) . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
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
     * Render an admin error when the rep cannot be found.
     *
     * @return void
     */
    private function renderNotFound(): void
    {
        echo '<div class="notice notice-error"><p>' . esc_html__('Representation not found.', 'civic-engagement') . '</p></div>';
    }

    /**
     * Get linked contact for a rep.
     *
     * @param array<string, mixed> $rep Rep row.
     * @return array<string, mixed>|null Contact row or null when absent.
     */
    private function contactForRep(array $rep): ?array
    {
        $contactId = isset($rep['contact_id']) ? (int) $rep['contact_id'] : 0;

        if ($contactId <= 0) {
            return null;
        }

        return $this->contacts->findById($contactId);
    }

    /**
     * Get related activity rows for a rep.
     *
     * @param array<string, mixed> $rep Rep row.
     * @return array<int, array<string, mixed>>
     */
    private function activitiesForRep(array $rep): array
    {
        $contactId = isset($rep['contact_id']) ? (int) $rep['contact_id'] : 0;
        $repId = isset($rep['id']) ? (int) $rep['id'] : 0;

        if ($contactId <= 0 || $repId <= 0) {
            return [];
        }

        $result = $this->activities->getByContactId(
            $contactId,
            [
                'activity_type' => 'rep',
                'related_id' => $repId,
                'per_page' => 20,
            ]
        );

        return isset($result['items']) && is_array($result['items']) ? $result['items'] : [];
    }

    /**
     * Get sanitized requested rep ID.
     *
     * @return int Rep ID.
     */
    private function repId(): int
    {
        if (!isset($_GET['rep_id'])) {
            return 0;
        }

        $repId = wp_unslash($_GET['rep_id']);

        if (is_array($repId) || is_object($repId)) {
            return 0;
        }

        return absint($repId);
    }

    /**
     * Build the list page URL.
     *
     * @return string List URL.
     */
    private function listUrl(): string
    {
        return add_query_arg(
            ['page' => 'civic-platform'],
            admin_url('admin.php')
        );
    }
}
