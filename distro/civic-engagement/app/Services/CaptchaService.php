<?php

declare(strict_types=1);

namespace CivicPlatform\Services;

/**
 * Shared CAPTCHA integration for public Civic forms.
 *
 * The first provider is Cloudflare Turnstile. Public forms can use this
 * service to render the widget and validate submitted Turnstile tokens without
 * duplicating provider-specific logic.
 */
class CaptchaService
{
    private const TURNSTILE_SCRIPT_HANDLE = 'civic-turnstile';
    private const TURNSTILE_SCRIPT_URL = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
    private const TURNSTILE_VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    private const TURNSTILE_RESPONSE_FIELD = 'cf-turnstile-response';

    private CivicSettingsService $settings;

    public function __construct(?CivicSettingsService $settings = null)
    {
        $this->settings = $settings ?? new CivicSettingsService();
    }

    public function isEnabled(): bool
    {
        $settings = $this->settings->securitySettings();

        return !empty($settings['captcha_enabled']);
    }

    public function isConfigured(): bool
    {
        $settings = $this->settings->securitySettings();

        return '' !== $settings['turnstile_site_key'] && '' !== $settings['turnstile_secret_key'];
    }

    /**
     * Render a Turnstile widget wrapped in Civic Form classes.
     *
     * @param string $context Optional module-specific class suffix.
     * @return string Rendered widget markup, or an empty string when disabled.
     */
    public function renderWidget(string $context = ''): string
    {
        if (!$this->isEnabled()) {
            return '';
        }

        $settings = $this->settings->securitySettings();
        $siteKey = $settings['turnstile_site_key'];

        if ('' === $siteKey) {
            return '<p class="civic-form__message civic-form__message--error">' . esc_html__('CAPTCHA is enabled but not configured.', 'civic-engagement') . '</p>';
        }

        $this->enqueueTurnstileScript();

        $classes = ['civic-form__field', 'civic-form__field--full', 'civic-form__captcha'];

        if ('' !== trim($context)) {
            $classes[] = sanitize_html_class($context . '__captcha');
        }

        return '<div class="' . esc_attr(implode(' ', $classes)) . '">'
            . '<div class="cf-turnstile" data-sitekey="' . esc_attr($siteKey) . '"></div>'
            . '</div>';
    }

    /**
     * Validate a submitted Turnstile token.
     *
     * Disabled CAPTCHA is treated as successful so forms can call this method
     * unconditionally once they are integrated.
     *
     * @param string $token Submitted Turnstile token.
     * @param string|null $remoteIp Optional visitor IP address.
     * @return array{success: bool, error: string|null, provider_response: array<string, mixed>|null}
     */
    public function verifyToken(string $token, ?string $remoteIp = null): array
    {
        if (!$this->isEnabled()) {
            return $this->result(true, null, null);
        }

        $settings = $this->settings->securitySettings();
        $secret = $settings['turnstile_secret_key'];

        if ('' === $secret) {
            return $this->result(false, 'captcha_not_configured', null);
        }

        $token = trim($token);

        if ('' === $token) {
            return $this->result(false, 'captcha_missing', null);
        }

        $body = [
            'secret' => $secret,
            'response' => $token,
        ];

        if (null !== $remoteIp && '' !== trim($remoteIp)) {
            $body['remoteip'] = trim($remoteIp);
        }

        $response = wp_remote_post(
            self::TURNSTILE_VERIFY_URL,
            [
                'timeout' => 10,
                'body' => $body,
            ]
        );

        if (is_wp_error($response)) {
            return $this->result(false, 'captcha_verification_failed', ['message' => $response->get_error_message()]);
        }

        $decoded = json_decode((string) wp_remote_retrieve_body($response), true);

        if (!is_array($decoded)) {
            return $this->result(false, 'captcha_invalid_response', null);
        }

        if (!empty($decoded['success'])) {
            return $this->result(true, null, $decoded);
        }

        return $this->result(false, 'captcha_failed', $decoded);
    }

    /**
     * Validate the Turnstile token from a request array.
     *
     * @param array<string, mixed> $request Request data, usually $_POST.
     * @return array{success: bool, error: string|null, provider_response: array<string, mixed>|null}
     */
    public function validateRequest(array $request): array
    {
        $token = $request[self::TURNSTILE_RESPONSE_FIELD] ?? '';
        $remoteIp = $_SERVER['REMOTE_ADDR'] ?? null;

        if (is_scalar($token)) {
            $token = wp_unslash((string) $token);
        }

        return $this->verifyToken(is_scalar($token) ? (string) $token : '', is_scalar($remoteIp) ? (string) $remoteIp : null);
    }

    /**
     * Convert a validation result into a public form message.
     *
     * @param array{success: bool, error: string|null, provider_response: array<string, mixed>|null} $result
     * @return string User-facing validation message.
     */
    public function failureMessage(array $result): string
    {
        $error = (string) ($result['error'] ?? '');

        if ('captcha_not_configured' === $error) {
            return __('CAPTCHA is not configured. Please contact the site administrator.', 'civic-engagement');
        }

        if ('captcha_missing' === $error) {
            return __('Please complete the CAPTCHA challenge.', 'civic-engagement');
        }

        return __('CAPTCHA validation failed. Please try again.', 'civic-engagement');
    }

    public function enqueueTurnstileScript(): void
    {
        wp_enqueue_script(
            self::TURNSTILE_SCRIPT_HANDLE,
            self::TURNSTILE_SCRIPT_URL,
            [],
            null,
            true
        );
    }

    /**
     * @param array<string, mixed>|null $providerResponse
     * @return array{success: bool, error: string|null, provider_response: array<string, mixed>|null}
     */
    private function result(bool $success, ?string $error, ?array $providerResponse): array
    {
        return [
            'success' => $success,
            'error' => $error,
            'provider_response' => $providerResponse,
        ];
    }
}
