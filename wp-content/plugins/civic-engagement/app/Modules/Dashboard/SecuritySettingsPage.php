<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Dashboard;

use CivicPlatform\Services\CivicSettingsService;

/**
 * Renders Civic security configuration inside the Civic Manager admin area.
 */
class SecuritySettingsPage
{
    private const ACTION = 'civic_security_settings_save';
    private const NONCE_ACTION = 'civic_security_settings';
    private const NONCE_FIELD = 'civic_security_settings_nonce';

    private CivicSettingsService $settings;

    public function __construct(CivicSettingsService $settings)
    {
        $this->settings = $settings;
    }

    public function render(): void
    {
        $message = null;
        $success = false;

        if ($this->isSubmission()) {
            if (!$this->hasValidNonce()) {
                $message = __('Security check failed. Please try again.', 'civic-engagement');
            } else {
                $success = $this->settings->updateSecuritySettings($this->requestSettings());
                $message = $success
                    ? __('Security settings saved.', 'civic-engagement')
                    : __('Security settings could not be saved.', 'civic-engagement');
            }
        }

        $values = $this->settings->securitySettings();

        echo '<div class="wrap civic-security-settings">';
        echo '<h1>' . esc_html__('Security Settings', 'civic-engagement') . '</h1>';
        echo '<p>' . esc_html__('Configure shared public form security options for the Civic Platform.', 'civic-engagement') . '</p>';

        if (null !== $message) {
            echo '<div class="notice ' . esc_attr($success ? 'notice-success' : 'notice-error') . '"><p>' . esc_html($message) . '</p></div>';
        }

        echo '<form method="post" action="">';
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD);
        echo '<input type="hidden" name="civic_action" value="' . esc_attr(self::ACTION) . '">';
        echo '<table class="form-table" role="presentation"><tbody>';

        echo '<tr>';
        echo '<th scope="row">' . esc_html__('Enable CAPTCHA', 'civic-engagement') . '</th>';
        echo '<td><label><input type="checkbox" name="civic_security[captcha_enabled]" value="1"' . checked(!empty($values['captcha_enabled']), true, false) . '> ' . esc_html__('Require CAPTCHA validation for integrated public forms.', 'civic-engagement') . '</label></td>';
        echo '</tr>';

        $this->renderTextInput('turnstile_site_key', __('Cloudflare Site Key', 'civic-engagement'), $values['turnstile_site_key']);
        $this->renderTextInput('turnstile_secret_key', __('Cloudflare Secret Key', 'civic-engagement'), $values['turnstile_secret_key'], true);

        echo '</tbody></table>';
        submit_button(__('Save Security Settings', 'civic-engagement'));
        echo '</form>';
        echo '</div>';
    }

    private function renderTextInput(string $name, string $label, string $value, bool $password = false): void
    {
        echo '<tr>';
        echo '<th scope="row"><label for="civic-security-' . esc_attr($name) . '">' . esc_html($label) . '</label></th>';
        echo '<td>';
        echo '<input class="regular-text" id="civic-security-' . esc_attr($name) . '" name="civic_security[' . esc_attr($name) . ']" type="' . esc_attr($password ? 'password' : 'text') . '" value="' . esc_attr($value) . '" autocomplete="off">';
        echo '</td>';
        echo '</tr>';
    }

    private function isSubmission(): bool
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : '';

        if ('POST' !== $method) {
            return false;
        }

        $action = $_POST['civic_action'] ?? '';

        return is_scalar($action) && self::ACTION === (string) wp_unslash($action);
    }

    private function hasValidNonce(): bool
    {
        if (!isset($_POST[self::NONCE_FIELD])) {
            return false;
        }

        $nonce = wp_unslash($_POST[self::NONCE_FIELD]);

        if (!is_scalar($nonce)) {
            return false;
        }

        return (bool) wp_verify_nonce(sanitize_text_field((string) $nonce), self::NONCE_ACTION);
    }

    /** @return array<string, mixed> */
    private function requestSettings(): array
    {
        $settings = isset($_POST['civic_security']) ? wp_unslash($_POST['civic_security']) : [];

        if (!is_array($settings)) {
            $settings = [];
        }

        return [
            'captcha_enabled' => !empty($settings['captcha_enabled']),
            'turnstile_site_key' => isset($settings['turnstile_site_key']) && is_scalar($settings['turnstile_site_key'])
                ? (string) $settings['turnstile_site_key']
                : '',
            'turnstile_secret_key' => isset($settings['turnstile_secret_key']) && is_scalar($settings['turnstile_secret_key'])
                ? (string) $settings['turnstile_secret_key']
                : '',
        ];
    }
}
