<?php

declare(strict_types=1);

namespace CivicPlatform\Helpers;

/**
 * Shared HTML renderers for stable public Civic form components.
 */
class FormRenderer
{
    /** @param array<string, string> $errors */
    public static function textInput(
        string $moduleClass,
        string $id,
        string $name,
        string $label,
        string $value,
        array $errors = [],
        string $errorKey = '',
        bool $required = false,
        string $type = 'text'
    ): string {
        $html = '<p class="' . esc_attr($moduleClass . '__field') . ' civic-form__field">';
        $html .= '<label for="' . esc_attr($id) . '">' . esc_html($label) . '</label>';
        $html .= '<input id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" type="' . esc_attr($type) . '" value="' . esc_attr($value) . '"' . ($required ? ' required' : '') . '>';
        $html .= self::validationMessage($moduleClass, '' !== $errorKey ? $errorKey : $id, $errors);
        $html .= '</p>';

        return $html;
    }

    /** @param array<string, string> $errors */
    public static function textarea(
        string $moduleClass,
        string $id,
        string $name,
        string $label,
        string $value,
        array $errors = [],
        string $errorKey = '',
        bool $required = false,
        int $rows = 4,
        bool $fullWidth = true
    ): string {
        $classes = [$moduleClass . '__field', 'civic-form__field'];

        if ($fullWidth) {
            $classes[] = 'civic-form__field--full';
        }

        $html = '<p class="' . esc_attr(implode(' ', $classes)) . '">';
        $html .= '<label for="' . esc_attr($id) . '">' . esc_html($label) . '</label>';
        $html .= '<textarea id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" rows="' . esc_attr((string) $rows) . '"' . ($required ? ' required' : '') . '>' . esc_textarea($value) . '</textarea>';
        $html .= self::validationMessage($moduleClass, '' !== $errorKey ? $errorKey : $id, $errors);
        $html .= '</p>';

        return $html;
    }

    /** @param array<string, string> $errors */
    public static function addressTextarea(
        string $moduleClass,
        string $id,
        string $name,
        string $value,
        array $errors = [],
        string $errorKey = 'address'
    ): string {
        return self::textarea(
            $moduleClass,
            $id,
            $name,
            __('Address', 'civic-engagement'),
            $value,
            $errors,
            $errorKey,
            false,
            3,
            false
        );
    }

    /** @param array<string, string> $errors */
    public static function imageUpload(
        string $moduleClass,
        string $id,
        string $name,
        string $label,
        array $errors = [],
        string $errorKey = 'image'
    ): string {
        $html = '<p class="' . esc_attr($moduleClass . '__field') . ' civic-form__field civic-form__field--full">';
        $html .= '<label for="' . esc_attr($id) . '">' . esc_html($label) . '</label>';
        $html .= '<input id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" type="file" accept="image/*">';
        $html .= self::validationMessage($moduleClass, $errorKey, $errors);
        $html .= '</p>';

        return $html;
    }

    /** @param array<string, string> $errors */
    public static function validationMessage(string $moduleClass, string $key, array $errors): string
    {
        if (empty($errors[$key])) {
            return '';
        }

        return '<br><span class="' . esc_attr($moduleClass . '__error') . ' civic-form__error">' . esc_html($errors[$key]) . '</span>';
    }

    /** @param array<string, mixed> $values */
    public static function communicationPreferences(string $moduleClass, string $fieldPrefix, array $values): string
    {
        $html = '<fieldset class="' . esc_attr($moduleClass . '__field ' . $moduleClass . '__consent') . ' civic-form__field civic-form__field--full civic-form__consent">';
        $html .= '<legend>' . esc_html__('I agree to be contacted by:', 'civic-engagement') . '</legend>';

        foreach (self::communicationOptions() as $key => $label) {
            $field = 'consent_' . $key;
            $checked = !array_key_exists($field, $values) || !empty($values[$field]);
            $id = sanitize_html_class($moduleClass . '-' . $field);
            $html .= '<label class="civic-form__button-checkbox" for="' . esc_attr($id) . '">';
            $html .= '<input id="' . esc_attr($id) . '" type="checkbox" name="' . esc_attr($fieldPrefix . '[' . $field . ']') . '" value="1"' . checked($checked, true, false) . '> ';
            $html .= '<span>' . esc_html($label) . '</span>';
            $html .= '</label> ';
        }

        $html .= '</fieldset>';

        return $html;
    }

    public static function privacyConsent(string $moduleClass, string $fieldPrefix): string
    {
        $privacyUrl = self::privacyPolicyUrl();
        $link = '' !== $privacyUrl
            ? '<a href="' . esc_url($privacyUrl) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Privacy Policy', 'civic-engagement') . '</a>'
            : esc_html__('Privacy Policy', 'civic-engagement');

        $html = '<p class="' . esc_attr($moduleClass . '__field ' . $moduleClass . '__privacy-consent') . ' civic-form__field civic-form__field--full civic-form__consent">';
        $html .= '<label><input type="checkbox" name="' . esc_attr($fieldPrefix . '[privacy_consent]') . '" value="1" required> ';
        $html .= sprintf(
            /* translators: %s: Privacy Policy link. */
            esc_html__('I have read and agree to the %s.', 'civic-engagement'),
            $link
        );
        $html .= '</label>';
        $html .= '</p>';

        return $html;
    }

    /** @return array<string, string> */
    private static function communicationOptions(): array
    {
        return [
            'email' => __('Email', 'civic-engagement'),
            'call' => __('Call', 'civic-engagement'),
            'sms' => __('SMS', 'civic-engagement'),
            'post' => __('Post', 'civic-engagement'),
        ];
    }

    private static function privacyPolicyUrl(): string
    {
        if (function_exists('get_privacy_policy_url')) {
            $url = get_privacy_policy_url();

            if (is_string($url) && '' !== $url) {
                return $url;
            }
        }

        $pageId = (int) get_option('wp_page_for_privacy_policy');

        if ($pageId <= 0) {
            return '';
        }

        $url = get_permalink($pageId);

        return is_string($url) ? $url : '';
    }
}
