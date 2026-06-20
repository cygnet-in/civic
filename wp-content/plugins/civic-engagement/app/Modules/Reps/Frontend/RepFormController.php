<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Reps\Frontend;

use CivicPlatform\Repositories\ElectoralAreaRepository;
use CivicPlatform\Services\RepService;

/**
 * Handles public representation form rendering and submission processing.
 *
 * This controller validates request intent, sanitizes submitted values, and
 * delegates the workflow to RepService. Persistence and business workflow
 * orchestration stay outside the frontend layer.
 */
class RepFormController
{
    /**
     * Form action value.
     */
    private const ACTION = 'civic_rep_form_submit';

    /**
     * Nonce action.
     */
    private const NONCE_ACTION = 'civic_rep_form';

    /**
     * Nonce field name.
     */
    private const NONCE_FIELD = 'civic_rep_form_nonce';

    /**
     * Rep workflow service.
     *
     * @var RepService
     */
    private RepService $reps;

    /**
     * Electoral area repository.
     *
     * @var ElectoralAreaRepository
     */
    private ElectoralAreaRepository $electoralAreas;

    /**
     * Template path.
     *
     * @var string
     */
    private string $templatePath;

    /**
     * @param RepService $reps Rep workflow service.
     * @param ElectoralAreaRepository $electoralAreas Electoral area repository.
     * @param string|null $templatePath Optional template path override.
     */
    public function __construct(
        RepService $reps,
        ElectoralAreaRepository $electoralAreas,
        ?string $templatePath = null
    ) {
        $this->reps = $reps;
        $this->electoralAreas = $electoralAreas;
        $this->templatePath = $templatePath
            ?? dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR . 'rep-form.php';
    }

    /**
     * Render the representation form template.
     *
     * @param array<string, mixed> $atts Shortcode attributes.
     * @return string Rendered form markup.
     */
    public function renderForm(array $atts = []): string
    {
        $response = $this->processSubmission();
        $values = $response['values'];
        $electoralAreas = $this->electoralAreas->getAllActive();
        $formAction = self::ACTION;
        $nonceAction = self::NONCE_ACTION;
        $nonceField = self::NONCE_FIELD;

        unset($atts);

        ob_start();

        if (is_readable($this->templatePath)) {
            include $this->templatePath;
        }

        return (string) ob_get_clean();
    }

    /**
     * Process a submitted representation form.
     *
     * @return array<string, mixed> Structured form response.
     */
    public function processSubmission(): array
    {
        if (!$this->isSubmission()) {
            return $this->buildResponse(false, false, null, $this->defaultValues(), [], null);
        }

        if (!$this->hasValidNonce()) {
            return $this->buildResponse(true, false, 'Security check failed. Please try again.', $this->defaultValues(), [], 'invalid_nonce');
        }

        $values = $this->sanitizeRequestValues();
        $errors = $this->validateValues($values);

        if (!empty($errors)) {
            return $this->buildResponse(true, false, 'Please check the highlighted fields.', $values, $errors, 'validation_failed');
        }
    
        $result = $this->reps->submitRep($values);

        if (empty($result['success'])) {
            return $this->buildResponse(true, false, 'We could not submit your representation. Please try again.', $values, [], (string) ($result['error'] ?? 'submission_failed'));
        }

        return $this->buildResponse(true, true, 'Thank you. Your representation has been submitted.', $this->defaultValues(), [], null, $result);
    }

    /**
     * Check whether the current request is a rep form submission.
     *
     * @return bool True when the form was submitted.
     */
    private function isSubmission(): bool
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : '';

        if ('POST' !== $method) {
            return false;
        }

        $action = isset($_POST['civic_action']) ? $this->unslash($_POST['civic_action']) : '';

        return self::ACTION === $action;
    }

    /**
     * Validate the submitted nonce.
     *
     * @return bool True when the nonce is valid.
     */
    private function hasValidNonce(): bool
    {
        if (!isset($_POST[self::NONCE_FIELD])) {
            return false;
        }

        $nonce = $this->sanitizeText($this->unslash($_POST[self::NONCE_FIELD]));

        return '' !== $nonce && (bool) wp_verify_nonce($nonce, self::NONCE_ACTION);
    }

    /**
     * Sanitize submitted request values.
     *
     * @return array<string, mixed> Sanitized workflow data.
     */
    private function sanitizeRequestValues(): array
    {
        $requestData = $this->requestData();

        return [
            'name' => $this->sanitizeText($this->requestValue($requestData, 'name')),
            'email' => $this->sanitizeEmail($this->requestValue($requestData, 'email')),
            'phone' => $this->sanitizeText($this->requestValue($requestData, 'phone')),
            'whatsapp' => $this->sanitizeText($this->requestValue($requestData, 'whatsapp')),
            'address' => $this->sanitizeTextarea($this->requestValue($requestData, 'address')),
            'eircode' => $this->sanitizeText($this->requestValue($requestData, 'eircode')),
            'electoral_area_id' => absint($this->requestValue($requestData, 'electoral_area_id')),
            'electoral_area' => $this->electoralAreaName(
                absint($this->requestValue($requestData, 'electoral_area_id'))
            ),
            'title' => $this->sanitizeText($this->requestValue($requestData, 'title')),
            'details' => $this->sanitizeTextarea($this->requestValue($requestData, 'details')),
            'map_lat' => $this->sanitizeCoordinate($this->requestValue($requestData, 'map_lat')),
            'map_lng' => $this->sanitizeCoordinate($this->requestValue($requestData, 'map_lng')),
        ];
    }

    /**
     * Validate sanitized form values.
     *
     * @param array<string, mixed> $values Sanitized values.
     * @return array<string, string> Validation errors keyed by field.
     */
    private function validateValues(array $values): array
    {
        $errors = [];

        if ('' === $values['name']) {
            $errors['name'] = 'Name is required.';
        }

        if ('' === $values['email']) {
            $errors['email'] = 'Email is required.';
        } elseif (function_exists('is_email') && !is_email((string) $values['email'])) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        if ('' === $values['title']) {
            $errors['title'] = 'Subject is required.';
        }

        if ('' === $values['details']) {
            $errors['details'] = 'Details are required.';
        }

        return $errors;
    }

    /**
     * Get structured representation request data.
     *
     * @return array<string, mixed> Unslashed request data.
     */
    private function requestData(): array
    {
        if (!isset($_POST['civic_rep'])) {
            return [];
        }

        $data = wp_unslash($_POST['civic_rep']);

        return is_array($data) ? $data : [];
    }

    /**
     * Get a scalar request value from the structured payload.
     *
     * @param array<string, mixed> $data Request data.
     * @param string $key Request key.
     * @return string Request value.
     */
    private function requestValue(array $data, string $key): string
    {
        if (!isset($data[$key])) {
            return '';
        }

        $value = $data[$key];

        if (is_array($value) || is_object($value)) {
            return '';
        }

        return (string) $value;
    }

    /**
     * Return default form values.
     *
     * @return array<string, mixed>
     */
    private function defaultValues(): array
    {
        return [
            'name' => '',
            'email' => '',
            'phone' => '',
            'whatsapp' => '',
            'address' => '',
            'eircode' => '',
            'electoral_area_id' => 0,
            'electoral_area' => '',
            'title' => '',
            'details' => '',
            'map_lat' => '',
            'map_lng' => '',
        ];
    }

    /**
     * Sanitize a text value.
     *
     * @param string $value Raw value.
     * @return string Sanitized value.
     */
    private function sanitizeText(string $value): string
    {
        return sanitize_text_field($value);
    }

    /**
     * Sanitize a textarea value.
     *
     * @param string $value Raw value.
     * @return string Sanitized value.
     */
    private function sanitizeTextarea(string $value): string
    {
        return sanitize_textarea_field($value);
    }

    /**
     * Sanitize an email value.
     *
     * @param string $value Raw value.
     * @return string Sanitized email.
     */
    private function sanitizeEmail(string $value): string
    {
        return sanitize_email($value);
    }

    /**
     * Sanitize an optional coordinate value.
     *
     * @param string $value Raw value.
     * @return string Sanitized coordinate or empty string.
     */
    private function sanitizeCoordinate(string $value): string
    {
        $value = trim($value);

        if ('' === $value || !is_numeric($value)) {
            return '';
        }

        return (string) (float) $value;
    }

    /**
     * Resolve an electoral area display name.
     *
     * @param int $id Electoral area ID.
     * @return string Electoral area name, or empty string when invalid/inactive.
     */
    private function electoralAreaName(int $id): string
    {
        $area = $this->electoralAreas->findById($id);

        if (!is_array($area) || empty($area['is_active'])) {
            return '';
        }

        return trim((string) ($area['name'] ?? ''));
    }

    /**
     * Unslash a request value.
     *
     * @param mixed $value Raw request value.
     * @return mixed Unslashed value.
     */
    private function unslash($value)
    {
        return wp_unslash($value);
    }

    /**
     * Build a consistent form response.
     *
     * @param bool $submitted Whether a submission was received.
     * @param bool $success Whether the submission succeeded.
     * @param string|null $message User-facing message.
     * @param array<string, mixed> $values Form values.
     * @param array<string, string> $errors Validation errors.
     * @param string|null $error Error code.
     * @param array<string, mixed>|null $workflowResult RepService result.
     * @return array<string, mixed>
     */
    private function buildResponse(
        bool $submitted,
        bool $success,
        ?string $message,
        array $values,
        array $errors,
        ?string $error,
        ?array $workflowResult = null
    ): array {
        return [
            'submitted' => $submitted,
            'success' => $success,
            'message' => $message,
            'values' => $values,
            'errors' => $errors,
            'error' => $error,
            'result' => $workflowResult,
        ];
    }
}
