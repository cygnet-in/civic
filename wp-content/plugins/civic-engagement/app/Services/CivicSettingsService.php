<?php

declare(strict_types=1);

namespace CivicPlatform\Services;

/**
 * Reads and writes lightweight Civic platform configuration.
 */
class CivicSettingsService
{
    private const SECURITY_OPTION = 'civic_security_settings';

    /**
     * Get saved security settings.
     *
     * @return array{captcha_enabled: bool, turnstile_site_key: string, turnstile_secret_key: string}
     */
    public function securitySettings(): array
    {
        $settings = get_option(self::SECURITY_OPTION, []);

        if (!is_array($settings)) {
            $settings = [];
        }

        return [
            'captcha_enabled' => !empty($settings['captcha_enabled']),
            'turnstile_site_key' => $this->stringValue($settings['turnstile_site_key'] ?? ''),
            'turnstile_secret_key' => $this->stringValue($settings['turnstile_secret_key'] ?? ''),
        ];
    }

    /**
     * Persist security settings.
     *
     * @param array<string, mixed> $settings Raw settings.
     * @return bool True when WordPress accepts the update.
     */
    public function updateSecuritySettings(array $settings): bool
    {
        update_option(
            self::SECURITY_OPTION,
            [
                'captcha_enabled' => !empty($settings['captcha_enabled']) ? 1 : 0,
                'turnstile_site_key' => sanitize_text_field($this->stringValue($settings['turnstile_site_key'] ?? '')),
                'turnstile_secret_key' => sanitize_text_field($this->stringValue($settings['turnstile_secret_key'] ?? '')),
            ],
            false
        );

        return true;
    }

    private function stringValue($value): string
    {
        if (is_scalar($value)) {
            return trim((string) $value);
        }

        return '';
    }
}
