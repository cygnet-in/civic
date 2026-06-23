<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Account;

/** Renders and processes the dedicated Civic change-password page. */
class ChangePasswordPage
{
    private const ACTION = 'civic_change_password';
    private const NONCE_ACTION = 'civic_change_password';
    private const NONCE_FIELD = 'civic_change_password_nonce';

    public function render(): void
    {
        $response = $this->processSubmission();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Change Password', 'civic-engagement') . '</h1>';
        $this->renderMessage($response);
        $this->renderForm($response);
        echo '</div>';
    }

    /** @return array{success: bool, message: string, errors: array<string, string>} */
    private function processSubmission(): array
    {
        if ('POST' !== strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? ''))) {
            return $this->response(false, '', []);
        }

        $action = isset($_POST['civic_action']) ? wp_unslash($_POST['civic_action']) : '';
        if (is_array($action) || is_object($action) || self::ACTION !== $action) {
            return $this->response(false, '', []);
        }

        if (!$this->hasValidNonce()) {
            return $this->response(false, __('Security check failed. Please try again.', 'civic-engagement'), []);
        }

        $data = $this->requestData();
        $currentPassword = $this->passwordValue($data, 'current_password');
        $newPassword = $this->passwordValue($data, 'new_password');
        $confirmPassword = $this->passwordValue($data, 'confirm_password');
        $errors = [];

        if ('' === $currentPassword || '' === $newPassword || '' === $confirmPassword) {
            $errors['password'] = __('All password fields are required.', 'civic-engagement');
        }

        $user = wp_get_current_user();
        if (empty($errors) && (!$user instanceof \WP_User || !wp_check_password($currentPassword, $user->user_pass, $user->ID))) {
            $errors['current_password'] = __('Your current password is incorrect.', 'civic-engagement');
        }

        if (empty($errors) && $newPassword !== $confirmPassword) {
            $errors['confirm_password'] = __('The new password confirmation does not match.', 'civic-engagement');
        }

        if (!empty($errors)) {
            return $this->response(false, __('Please correct the highlighted error.', 'civic-engagement'), $errors);
        }

        $updated = wp_update_user(['ID' => $user->ID, 'user_pass' => $newPassword]);
        if (is_wp_error($updated)) {
            return $this->response(false, __('Your password could not be updated. Please try again.', 'civic-engagement'), ['password' => __('Password update failed.', 'civic-engagement')]);
        }

        return $this->response(true, __('Your password has been updated.', 'civic-engagement'), []);
    }

    /** @param array{success: bool, message: string, errors: array<string, string>} $response */
    private function renderMessage(array $response): void
    {
        if ('' === $response['message']) {
            return;
        }

        echo '<div class="notice ' . esc_attr($response['success'] ? 'notice-success' : 'notice-error') . '"><p>' . esc_html($response['message']) . '</p></div>';
    }

    /** @param array{success: bool, message: string, errors: array<string, string>} $response */
    private function renderForm(array $response): void
    {
        echo '<form method="post">';
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD);
        echo '<input type="hidden" name="civic_action" value="' . esc_attr(self::ACTION) . '">';
        echo '<table class="form-table" role="presentation"><tbody>';
        $this->renderPasswordField('current_password', __('Current Password', 'civic-engagement'), $response['errors']);
        $this->renderPasswordField('new_password', __('New Password', 'civic-engagement'), $response['errors']);
        $this->renderPasswordField('confirm_password', __('Confirm Password', 'civic-engagement'), $response['errors']);
        echo '</tbody></table>';
        submit_button(__('Update Password', 'civic-engagement'));
        echo '</form>';
    }

    /** @param array<string, string> $errors */
    private function renderPasswordField(string $key, string $label, array $errors): void
    {
        echo '<tr><th scope="row"><label for="civic-account-' . esc_attr($key) . '">' . esc_html($label) . '</label></th><td>';
        echo '<input class="regular-text" id="civic-account-' . esc_attr($key) . '" name="civic_account[' . esc_attr($key) . ']" type="password" autocomplete="' . esc_attr('current_password' === $key ? 'current-password' : 'new-password') . '" required>';
        if (isset($errors[$key]) || ('password' === $key && isset($errors['password']))) {
            echo '<p class="description">' . esc_html($errors[$key] ?? $errors['password']) . '</p>';
        }
        echo '</td></tr>';
    }

    /** @return array<string, mixed> */
    private function requestData(): array
    {
        $data = isset($_POST['civic_account']) ? wp_unslash($_POST['civic_account']) : [];
        return is_array($data) ? $data : [];
    }

    /** @param array<string, mixed> $data */
    private function passwordValue(array $data, string $key): string
    {
        $value = $data[$key] ?? '';
        return is_array($value) || is_object($value) ? '' : (string) $value;
    }

    private function hasValidNonce(): bool
    {
        $nonce = isset($_POST[self::NONCE_FIELD]) ? wp_unslash($_POST[self::NONCE_FIELD]) : '';
        return !is_array($nonce) && !is_object($nonce) && (bool) wp_verify_nonce((string) $nonce, self::NONCE_ACTION);
    }

    /** @param array<string, string> $errors @return array{success: bool, message: string, errors: array<string, string>} */
    private function response(bool $success, string $message, array $errors): array
    {
        return ['success' => $success, 'message' => $message, 'errors' => $errors];
    }
}
