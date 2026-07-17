<?php

/**
 * Representation submission form template.
 *
 * Available variables:
 *
 * @var array<string, mixed> $response Form response state.
 * @var array<string, mixed> $values Submitted/default values.
 * @var array<int, array<string, mixed>> $electoralAreas Active electoral areas.
 * @var string $formAction Hidden action value.
 * @var string $nonceAction Nonce action.
 * @var string $nonceField Nonce field name.
 * @var string $captchaWidget Rendered CAPTCHA widget markup.
 */

if (!defined('ABSPATH')) {
    exit;
}

$errors = isset($response['errors']) && is_array($response['errors']) ? $response['errors'] : [];
$message = isset($response['message']) ? (string) $response['message'] : '';
$messageClass = !empty($response['success']) ? 'civic-rep-form__message--success civic-form__message--success' : 'civic-rep-form__message--error civic-form__message--error';
$formRenderer = \CivicPlatform\Helpers\FormRenderer::class;
?>

<div class="civic-rep-form civic-form">
    <h2 class="civic-rep-form__title civic-form__title"><?php echo esc_html__('Submit a Representation', 'civic-engagement'); ?></h2>

    <?php if ('' !== $message) : ?>
        <div class="civic-rep-form__message civic-form__message <?php echo esc_attr($messageClass); ?>" role="status">
            <?php echo esc_html($message); ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(get_permalink()); ?>" class="civic-rep-form__form civic-form__form" enctype="multipart/form-data">
        <?php wp_nonce_field($nonceAction, $nonceField); ?>
        <input type="hidden" name="civic_action" value="<?php echo esc_attr($formAction); ?>">

        <?php echo $formRenderer::textInput('civic-rep-form', 'civic-rep-name', 'civic_rep[name]', __('Name', 'civic-engagement'), (string) ($values['name'] ?? ''), $errors, 'name', true); ?>
        <?php echo $formRenderer::textInput('civic-rep-form', 'civic-rep-email', 'civic_rep[email]', __('Email', 'civic-engagement'), (string) ($values['email'] ?? ''), $errors, 'email', true, 'email'); ?>
        <?php echo $formRenderer::textInput('civic-rep-form', 'civic-rep-phone', 'civic_rep[phone]', __('Phone', 'civic-engagement'), (string) ($values['phone'] ?? '')); ?>
        <?php echo $formRenderer::textInput('civic-rep-form', 'civic-rep-whatsapp', 'civic_rep[whatsapp]', __('WhatsApp', 'civic-engagement'), (string) ($values['whatsapp'] ?? '')); ?>
        <?php echo $formRenderer::addressTextarea('civic-rep-form', 'civic-rep-address', 'civic_rep[address]', (string) ($values['address'] ?? ''), $errors); ?>
        <?php echo $formRenderer::textInput('civic-rep-form', 'civic-rep-eircode', 'civic_rep[eircode]', __('Eircode', 'civic-engagement'), (string) ($values['eircode'] ?? '')); ?>

        <p class="civic-rep-form__field civic-form__field">
            <label for="civic-rep-electoral-area"><?php echo esc_html__('Electoral Area', 'civic-engagement'); ?></label>
            <select
                id="civic-rep-electoral-area"
                name="civic_rep[electoral_area_id]"
            >
                <option value=""><?php echo esc_html__('Select an electoral area', 'civic-engagement'); ?></option>
                <?php foreach ($electoralAreas as $area) : ?>
                    <?php $areaId = isset($area['id']) ? (int) $area['id'] : 0; ?>
                    <option value="<?php echo esc_attr((string) $areaId); ?>" <?php selected((int) ($values['electoral_area_id'] ?? 0), $areaId); ?>>
                        <?php echo esc_html((string) ($area['name'] ?? '')); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <?php echo $formRenderer::textInput('civic-rep-form', 'civic-rep-title', 'civic_rep[title]', __('Subject', 'civic-engagement'), (string) ($values['title'] ?? ''), $errors, 'title', true); ?>
        <?php echo $formRenderer::textarea('civic-rep-form', 'civic-rep-details', 'civic_rep[details]', __('Details', 'civic-engagement'), (string) ($values['details'] ?? ''), $errors, 'details', true, 6); ?>
        <?php echo $formRenderer::imageUpload('civic-rep-form', 'civic-rep-image', 'civic_rep[image]', __('Image', 'civic-engagement'), $errors); ?>

        <input type="hidden" name="civic_rep[map_lat]" value="<?php echo esc_attr((string) ($values['map_lat'] ?? '')); ?>">
        <input type="hidden" name="civic_rep[map_lng]" value="<?php echo esc_attr((string) ($values['map_lng'] ?? '')); ?>">

        <?php echo $captchaWidget; ?>

        <?php echo $formRenderer::communicationPreferences('civic-rep-form', 'civic_rep', $values); ?>
        <?php echo $formRenderer::privacyConsent('civic-rep-form', 'civic_rep'); ?>

        <p class="civic-rep-form__actions civic-form__actions">
            <button type="submit"><?php echo esc_html__('Submit Representation', 'civic-engagement'); ?></button>
        </p>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var phone = document.getElementById('civic-rep-phone');
    var whatsapp = document.getElementById('civic-rep-whatsapp');

    if (!phone || !whatsapp) {
        return;
    }

    var whatsappEdited = whatsapp.value !== '' && whatsapp.value !== phone.value;

    if (!whatsappEdited && whatsapp.value === '' && phone.value !== '') {
        whatsapp.value = phone.value;
    }

    whatsapp.addEventListener('input', function () {
        whatsappEdited = true;
    });

    phone.addEventListener('input', function () {
        if (!whatsappEdited) {
            whatsapp.value = phone.value;
        }
    });
});
</script>
