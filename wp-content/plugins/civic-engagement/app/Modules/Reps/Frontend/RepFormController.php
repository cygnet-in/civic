<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Reps\Frontend;

use CivicPlatform\Repositories\ElectoralAreaRepository;
use CivicPlatform\Services\CaptchaService;
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
     * Allowed image MIME types for a representation upload.
     *
     * @var array<string, string>
     */
    private const IMAGE_MIMES = [
        'jpg|jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

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
     * Shared CAPTCHA service.
     *
     * @var CaptchaService
     */
    private CaptchaService $captcha;

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
        ?string $templatePath = null,
        ?CaptchaService $captcha = null
    ) {
        $this->reps = $reps;
        $this->electoralAreas = $electoralAreas;
        $this->captcha = $captcha ?? new CaptchaService();
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
        $captchaWidget = $this->captcha->renderWidget('civic-rep-form');

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
        $captcha = $this->captcha->validateRequest($_POST);

        if (empty($captcha['success'])) {
            $errors['captcha'] = $this->captcha->failureMessage($captcha);
        }

        if (empty($errors)) {
            $upload = $this->handleImageUpload();

            if (null !== $upload['error']) {
                $errors['image'] = $upload['error'];
            } elseif ($upload['attachment_id'] > 0) {
                $values['image_attachment_id'] = $upload['attachment_id'];
            }
        }

        if (!empty($errors)) {
            $message = isset($errors['captcha'])
                ? $errors['captcha']
                : 'Please check the highlighted fields.';

            return $this->buildResponse(true, false, $message, $values, $errors, 'validation_failed');
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
            'image_attachment_id' => 0,
            'map_lat' => $this->sanitizeCoordinate($this->requestValue($requestData, 'map_lat')),
            'map_lng' => $this->sanitizeCoordinate($this->requestValue($requestData, 'map_lng')),
            'consent_email' => !empty($requestData['consent_email']) ? 1 : 0,
            'consent_call' => !empty($requestData['consent_call']) ? 1 : 0,
            'consent_sms' => !empty($requestData['consent_sms']) ? 1 : 0,
            'consent_post' => !empty($requestData['consent_post']) ? 1 : 0,
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
     * Validate and upload the optional representation image to the Media Library.
     *
     * @return array{attachment_id: int, error: string|null}
     */
    private function handleImageUpload(): array
    {
        $file = $this->imageUploadFile();

        if (null === $file || UPLOAD_ERR_NO_FILE === $file['error']) {
            return ['attachment_id' => 0, 'error' => null];
        }

        if (UPLOAD_ERR_OK !== $file['error']) {
            return ['attachment_id' => 0, 'error' => __('The image could not be uploaded. Please try again.', 'civic-engagement')];
        }

        if ('' === $file['name'] || '' === $file['tmp_name'] || $file['size'] <= 0 || !is_uploaded_file($file['tmp_name'])) {
            return ['attachment_id' => 0, 'error' => __('The selected image is invalid.', 'civic-engagement')];
        }

        $fileType = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], self::IMAGE_MIMES);

        if (empty($fileType['ext']) || empty($fileType['type'])) {
            return ['attachment_id' => 0, 'error' => __('Please upload a JPG, PNG, or WebP image.', 'civic-engagement')];
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $upload = wp_handle_upload($file, ['test_form' => false, 'mimes' => self::IMAGE_MIMES]);

        if (!is_array($upload) || !empty($upload['error']) || empty($upload['file'])) {
            return ['attachment_id' => 0, 'error' => __('The image could not be uploaded. Please try again.', 'civic-engagement')];
        }

        $attachmentType = wp_check_filetype(basename((string) $upload['file']), self::IMAGE_MIMES);
        $attachmentId = wp_insert_attachment(
            [
                'post_mime_type' => $attachmentType['type'] ?? '',
                'post_title' => sanitize_file_name(pathinfo((string) $file['name'], PATHINFO_FILENAME)),
                'post_status' => 'inherit',
            ],
            (string) $upload['file']
        );

        if (is_wp_error($attachmentId) || $attachmentId <= 0) {
            return ['attachment_id' => 0, 'error' => __('The image could not be saved. Please try again.', 'civic-engagement')];
        }

        $metadata = wp_generate_attachment_metadata($attachmentId, (string) $upload['file']);

        if (is_array($metadata)) {
            wp_update_attachment_metadata($attachmentId, $metadata);
        }

        return ['attachment_id' => (int) $attachmentId, 'error' => null];
    }

    /**
     * Get the nested image upload from the namespaced representation request.
     *
     * @return array{name: string, tmp_name: string, error: int, size: int}|null
     */
    private function imageUploadFile(): ?array
    {
        if (!isset($_FILES['civic_rep']) || !is_array($_FILES['civic_rep'])) {
            return null;
        }

        $files = $_FILES['civic_rep'];

        foreach (['name', 'tmp_name', 'error', 'size'] as $key) {
            if (!isset($files[$key]) || !is_array($files[$key]) || !array_key_exists('image', $files[$key])) {
                return null;
            }
        }

        $name = $files['name']['image'];
        $tmpName = $files['tmp_name']['image'];
        $error = $files['error']['image'];
        $size = $files['size']['image'];

        if (is_array($name) || is_object($name) || is_array($tmpName) || is_object($tmpName) || !is_numeric($error) || !is_numeric($size)) {
            return null;
        }

        return [
            'name' => (string) $name,
            'tmp_name' => (string) $tmpName,
            'error' => (int) $error,
            'size' => (int) $size,
        ];
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
            'image_attachment_id' => 0,
            'map_lat' => '',
            'map_lng' => '',
            'consent_email' => 0,
            'consent_call' => 0,
            'consent_sms' => 0,
            'consent_post' => 0,
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
