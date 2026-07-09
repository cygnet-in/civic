<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Reps\Admin;

use CivicPlatform\Helpers\DateHelper;
use CivicPlatform\Helpers\StatusLabelHelper;
use CivicPlatform\Modules\Schedules\Repository\ScheduleRepository;
use CivicPlatform\Services\ActivityService;
use CivicPlatform\Services\RepService;

/**
 * Renders a single representation detail page.
 *
 * This page handles request sanitization and presentation only. Rep and
 * activity data are loaded through services.
 */
class RepDetailPage
{
    /**
     * Administrative update action.
     */
    private const ACTION = 'civic_rep_administration_update';

    /**
     * Nonce action.
     */
    private const NONCE_ACTION = 'civic_rep_administration';

    /**
     * Nonce field name.
     */
    private const NONCE_FIELD = 'civic_rep_administration_nonce';

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
     * Activity service.
     *
     * @var ActivityService
     */
    private ActivityService $activities;

    /**
     * Date helper.
     *
     * @var DateHelper
     */
    private DateHelper $dates;

    private ?ScheduleRepository $schedules;

    /**
     * @param RepService $reps Reps service.
     * @param ActivityService $activities Activity service.
     * @param DateHelper $dates Date helper.
     */
    public function __construct(
        RepService $reps,
        ActivityService $activities,
        DateHelper $dates,
        ?ScheduleRepository $schedules = null
    ) {
        $this->reps = $reps;
        $this->activities = $activities;
        $this->dates = $dates;
        $this->schedules = $schedules;
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

        $response = $this->processAdministrationUpdate($repId);

        if (!empty($response['success'])) {
            $rep = $this->reps->findById($repId) ?: $rep;
        }

        $activities = $this->activitiesForRep($rep);

        $this->renderMessage($response);
        $this->renderSummary($rep);
        $this->renderScheduleAction($rep);
        $this->renderImage($rep);
        $this->renderAdministrationForm($rep);
        $this->renderSnapshot($rep);
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
        $this->renderDetailRow(__('Status', 'civic-engagement'), StatusLabelHelper::format($rep['status'] ?? ''));
        $this->renderDetailRow(__('Created At', 'civic-engagement'), $this->dates->formatDateTime($rep['created_at'] ?? null));
        echo '</tbody></table>';
    }

    /**
     * Render the schedule conversion action.
     *
     * @param array<string, mixed> $rep Rep row.
     * @return void
     */
    private function renderScheduleAction(array $rep): void
    {
        $repId = isset($rep['id']) ? (int) $rep['id'] : 0;

        if ($repId <= 0) {
            return;
        }

        $schedule = $this->scheduleForRep($rep);

        if (is_array($schedule)) {
            $scheduleId = isset($schedule['id']) ? (int) $schedule['id'] : 0;
            $scheduleTitle = (string) ($schedule['title'] ?? '');

            echo '<p>';
            echo esc_html(sprintf(__('Converted to Schedule #%d', 'civic-engagement'), $scheduleId));

            if ('' !== $scheduleTitle) {
                echo ' ' . esc_html(sprintf(__('"%s"', 'civic-engagement'), $scheduleTitle));
            }

            if ($scheduleId > 0 && current_user_can('manage_civic_schedules')) {
                echo ' <a class="button" href="' . esc_url($this->scheduleViewUrl($scheduleId)) . '">' . esc_html__('View Schedule', 'civic-engagement') . '</a>';
            }

            echo '</p>';

            return;
        }

        if (!current_user_can('manage_civic_schedules')) {
            return;
        }

        echo '<p><a class="button button-primary" href="' . esc_url($this->convertToScheduleUrl($repId)) . '">' . esc_html__('Convert to Schedule', 'civic-engagement') . '</a></p>';
    }

    /**
     * Render mutable administrative representation fields.
     *
     * @param array<string, mixed> $rep Rep row.
     * @return void
     */
    private function renderAdministrationForm(array $rep): void
    {
        $repId = isset($rep['id']) ? (int) $rep['id'] : 0;
        $status = (string) ($rep['status'] ?? 'new');

        echo '<h2>' . esc_html__('Administration', 'civic-engagement') . '</h2>';
        echo '<form method="post">';
        wp_nonce_field(self::NONCE_ACTION . $repId, self::NONCE_FIELD);
        echo '<input type="hidden" name="civic_action" value="' . esc_attr(self::ACTION) . '">';
        echo '<table class="form-table" role="presentation"><tbody>';
        echo '<tr><th scope="row"><label for="civic-rep-status">' . esc_html__('Status', 'civic-engagement') . '</label></th>';
        echo '<td><select id="civic-rep-status" name="civic_rep_administration[status]">';

        foreach (['new', 'pending', 'in_progress', 'scheduled', 'resolved', 'closed'] as $option) {
            echo '<option value="' . esc_attr($option) . '"' . selected($status, $option, false) . '>' . esc_html(ucwords(str_replace('_', ' ', $option))) . '</option>';
        }

        echo '</select></td></tr>';
        echo '<tr><th scope="row"><label for="civic-rep-internal-comment">' . esc_html__('Internal Comment', 'civic-engagement') . '</label></th>';
        echo '<td><textarea class="large-text" id="civic-rep-internal-comment" name="civic_rep_administration[internal_comment]" rows="4">' . esc_textarea((string) ($rep['internal_comment'] ?? '')) . '</textarea></td></tr>';
        echo '</tbody></table>';
        submit_button(__('Save Administration Details', 'civic-engagement'));
        echo '</form>';
    }

    /**
     * Render the uploaded representation image when one is attached.
     *
     * @param array<string, mixed> $rep Rep row.
     * @return void
     */
    private function renderImage(array $rep): void
    {
        $attachmentId = isset($rep['image_attachment_id']) ? (int) $rep['image_attachment_id'] : 0;

        if ($attachmentId <= 0) {
            return;
        }

        $thumbnailUrl = wp_get_attachment_image_url($attachmentId, 'thumbnail');
        $fullUrl = wp_get_attachment_url($attachmentId);

        if (!is_string($thumbnailUrl) || '' === $thumbnailUrl || !is_string($fullUrl) || '' === $fullUrl) {
            return;
        }

        echo '<h2>' . esc_html__('Image', 'civic-engagement') . '</h2>';
        echo '<p><a href="' . esc_url($fullUrl) . '" target="_blank" rel="noopener">';
        echo '<img src="' . esc_url($thumbnailUrl) . '" alt="' . esc_attr__('Uploaded representation image', 'civic-engagement') . '">';
        echo '</a></p>';
    }

    /**
     * Process an administrative metadata update.
     *
     * @param int $repId Rep ID.
     * @return array{success: bool, message: string}
     */
    private function processAdministrationUpdate(int $repId): array
    {
        if ('POST' !== strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? ''))) {
            return ['success' => false, 'message' => ''];
        }

        $action = isset($_POST['civic_action']) ? wp_unslash($_POST['civic_action']) : '';

        if (is_array($action) || is_object($action) || self::ACTION !== $action) {
            return ['success' => false, 'message' => ''];
        }

        if (!$this->hasValidNonce($repId)) {
            return ['success' => false, 'message' => __('Security check failed. Please try again.', 'civic-engagement')];
        }

        $data = isset($_POST['civic_rep_administration']) ? wp_unslash($_POST['civic_rep_administration']) : [];
        $data = is_array($data) ? $data : [];
        $status = isset($data['status']) && !is_array($data['status']) && !is_object($data['status'])
            ? sanitize_key((string) $data['status'])
            : '';
        $internalComment = isset($data['internal_comment']) && !is_array($data['internal_comment']) && !is_object($data['internal_comment'])
            ? sanitize_textarea_field((string) $data['internal_comment'])
            : '';

        if (!in_array($status, ['new', 'pending', 'in_progress', 'scheduled', 'resolved', 'closed'], true)) {
            return ['success' => false, 'message' => __('Status is invalid.', 'civic-engagement')];
        }

        if (!$this->reps->updateAdministrativeDetails($repId, $status, $internalComment)) {
            return ['success' => false, 'message' => __('The representation could not be updated.', 'civic-engagement')];
        }

        return ['success' => true, 'message' => __('Administration details updated successfully.', 'civic-engagement')];
    }

    /**
     * Validate the administration form nonce.
     *
     * @param int $repId Rep ID.
     * @return bool True when valid.
     */
    private function hasValidNonce(int $repId): bool
    {
        if (!isset($_POST[self::NONCE_FIELD])) {
            return false;
        }

        $nonce = wp_unslash($_POST[self::NONCE_FIELD]);

        if (is_array($nonce) || is_object($nonce)) {
            return false;
        }

        return (bool) wp_verify_nonce(sanitize_text_field((string) $nonce), self::NONCE_ACTION . $repId);
    }

    /**
     * Render an administration update message.
     *
     * @param array{success: bool, message: string} $response Update response.
     * @return void
     */
    private function renderMessage(array $response): void
    {
        if ('' === $response['message']) {
            return;
        }

        $class = $response['success'] ? 'notice notice-success' : 'notice notice-error';

        echo '<div class="' . esc_attr($class) . '"><p>' . esc_html($response['message']) . '</p></div>';
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
        $this->renderDetailRow(__('Eircode', 'civic-engagement'), (string) ($rep['eircode_snapshot'] ?? ''));
        $this->renderDetailRow(__('Electoral Area', 'civic-engagement'), (string) ($rep['electoral_area_snapshot'] ?? ''));
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
            echo '<td>' . esc_html($this->dates->formatDateTime($activity['created_at'] ?? null)) . '</td>';
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

    /**
     * Build the schedule create URL prefilled from this representation.
     *
     * @param int $repId Representation ID.
     * @return string Schedule create URL.
     */
    private function convertToScheduleUrl(int $repId): string
    {
        return add_query_arg(
            [
                'page' => 'civic-schedule-edit',
                'source_type' => 'rep',
                'source_id' => $repId,
            ],
            admin_url('admin.php')
        );
    }

    /**
     * Get the schedule already linked to a representation.
     *
     * @param array<string, mixed> $rep Rep row.
     * @return array<string, mixed>|null Schedule row or null.
     */
    private function scheduleForRep(array $rep): ?array
    {
        if (null === $this->schedules) {
            return null;
        }

        $scheduleId = isset($rep['schedule_id']) ? (int) $rep['schedule_id'] : 0;

        if ($scheduleId > 0) {
            $schedule = $this->schedules->findById($scheduleId);

            if (is_array($schedule)) {
                return $schedule;
            }
        }

        $repId = isset($rep['id']) ? (int) $rep['id'] : 0;

        return $repId > 0 ? $this->schedules->findBySource('rep', $repId) : null;
    }

    /**
     * Build the schedule view URL.
     *
     * @param int $scheduleId Schedule ID.
     * @return string Schedule view URL.
     */
    private function scheduleViewUrl(int $scheduleId): string
    {
        return add_query_arg(
            [
                'page' => 'civic-schedule-edit',
                'schedule_id' => $scheduleId,
                'mode' => 'view',
            ],
            admin_url('admin.php')
        );
    }
}
