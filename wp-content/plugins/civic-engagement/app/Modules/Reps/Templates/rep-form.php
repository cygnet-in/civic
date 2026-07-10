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

        <p class="civic-rep-form__field civic-form__field">
            <label for="civic-rep-name"><?php echo esc_html__('Name', 'civic-engagement'); ?></label>
            <input
                id="civic-rep-name"
                name="civic_rep[name]"
                type="text"
                value="<?php echo esc_attr((string) ($values['name'] ?? '')); ?>"
                required
            >
            <?php if (isset($errors['name'])) : ?>
                <span class="civic-rep-form__error civic-form__error"><?php echo esc_html($errors['name']); ?></span>
            <?php endif; ?>
        </p>

        <p class="civic-rep-form__field civic-form__field">
            <label for="civic-rep-email"><?php echo esc_html__('Email', 'civic-engagement'); ?></label>
            <input
                id="civic-rep-email"
                name="civic_rep[email]"
                type="email"
                value="<?php echo esc_attr((string) ($values['email'] ?? '')); ?>"
                required
            >
            <?php if (isset($errors['email'])) : ?>
                <span class="civic-rep-form__error civic-form__error"><?php echo esc_html($errors['email']); ?></span>
            <?php endif; ?>
        </p>

        <p class="civic-rep-form__field civic-form__field">
            <label for="civic-rep-phone"><?php echo esc_html__('Phone', 'civic-engagement'); ?></label>
            <input
                id="civic-rep-phone"
                name="civic_rep[phone]"
                type="text"
                value="<?php echo esc_attr((string) ($values['phone'] ?? '')); ?>"
            >
        </p>

        <p class="civic-rep-form__field civic-form__field">
            <label for="civic-rep-whatsapp"><?php echo esc_html__('WhatsApp', 'civic-engagement'); ?></label>
            <input
                id="civic-rep-whatsapp"
                name="civic_rep[whatsapp]"
                type="text"
                value="<?php echo esc_attr((string) ($values['whatsapp'] ?? '')); ?>"
            >
        </p>

        <p class="civic-rep-form__field civic-form__field civic-form__field--full">
            <label for="civic-rep-address"><?php echo esc_html__('Address', 'civic-engagement'); ?></label>
            <textarea id="civic-rep-address" name="civic_rep[address]" rows="3"><?php echo esc_textarea((string) ($values['address'] ?? '')); ?></textarea>
        </p>

        <p class="civic-rep-form__field civic-form__field">
            <label for="civic-rep-eircode"><?php echo esc_html__('Eircode', 'civic-engagement'); ?></label>
            <input
                id="civic-rep-eircode"
                name="civic_rep[eircode]"
                type="text"
                value="<?php echo esc_attr((string) ($values['eircode'] ?? '')); ?>"
            >
        </p>

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

        <p class="civic-rep-form__field civic-form__field">
            <label for="civic-rep-title"><?php echo esc_html__('Subject', 'civic-engagement'); ?></label>
            <input
                id="civic-rep-title"
                name="civic_rep[title]"
                type="text"
                value="<?php echo esc_attr((string) ($values['title'] ?? '')); ?>"
                required
            >
            <?php if (isset($errors['title'])) : ?>
                <span class="civic-rep-form__error civic-form__error"><?php echo esc_html($errors['title']); ?></span>
            <?php endif; ?>
        </p>

        <p class="civic-rep-form__field civic-form__field civic-form__field--full">
            <label for="civic-rep-details"><?php echo esc_html__('Details', 'civic-engagement'); ?></label>
            <textarea id="civic-rep-details" name="civic_rep[details]" rows="6" required><?php echo esc_textarea((string) ($values['details'] ?? '')); ?></textarea>
            <?php if (isset($errors['details'])) : ?>
                <span class="civic-rep-form__error civic-form__error"><?php echo esc_html($errors['details']); ?></span>
            <?php endif; ?>
        </p>

        <p class="civic-rep-form__field civic-form__field civic-form__field--full">
            <label for="civic-rep-image"><?php echo esc_html__('Image', 'civic-engagement'); ?></label>
            <input id="civic-rep-image" name="civic_rep[image]" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            <?php if (isset($errors['image'])) : ?>
                <span class="civic-rep-form__error civic-form__error"><?php echo esc_html($errors['image']); ?></span>
            <?php endif; ?>
        </p>

        <fieldset class="civic-rep-form__field civic-rep-form__consent civic-form__field civic-form__field--full civic-form__consent">
            <legend><?php echo esc_html__('I agree to be contacted by:', 'civic-engagement'); ?></legend>
            <label><input type="checkbox" name="civic_rep[consent_email]" value="1" <?php checked(!empty($values['consent_email'])); ?>> <?php echo esc_html__('Email', 'civic-engagement'); ?></label>
            <label><input type="checkbox" name="civic_rep[consent_call]" value="1" <?php checked(!empty($values['consent_call'])); ?>> <?php echo esc_html__('Call', 'civic-engagement'); ?></label>
            <label><input type="checkbox" name="civic_rep[consent_sms]" value="1" <?php checked(!empty($values['consent_sms'])); ?>> <?php echo esc_html__('SMS', 'civic-engagement'); ?></label>
            <label><input type="checkbox" name="civic_rep[consent_post]" value="1" <?php checked(!empty($values['consent_post'])); ?>> <?php echo esc_html__('Post', 'civic-engagement'); ?></label>
        </fieldset>

        <input type="hidden" name="civic_rep[map_lat]" value="<?php echo esc_attr((string) ($values['map_lat'] ?? '')); ?>">
        <input type="hidden" name="civic_rep[map_lng]" value="<?php echo esc_attr((string) ($values['map_lng'] ?? '')); ?>">

        <?php echo $captchaWidget; ?>

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
