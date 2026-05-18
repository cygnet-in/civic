<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Reps\Frontend;

/**
 * Registers frontend shortcodes for the Reps module.
 *
 * Shortcode methods remain lightweight and delegate rendering and processing
 * to the form controller.
 */
class RepsShortcodes
{
    /**
     * Rep form controller.
     *
     * @var RepFormController
     */
    private RepFormController $formController;

    /**
     * @param RepFormController $formController Rep form controller.
     */
    public function __construct(RepFormController $formController)
    {
        $this->formController = $formController;
    }

    /**
     * Register Reps module shortcodes.
     *
     * @return void
     */
    public function register(): void
    {
        add_shortcode('civic_rep_form', [$this, 'renderRepForm']);
    }

    /**
     * Render the public representation form shortcode.
     *
     * @param mixed $atts Shortcode attributes.
     * @return string Rendered shortcode output.
     */
    public function renderRepForm($atts = []): string
    {
        if (!is_array($atts)) {
            $atts = [];
        }

        return $this->formController->renderForm($atts);
    }
}
