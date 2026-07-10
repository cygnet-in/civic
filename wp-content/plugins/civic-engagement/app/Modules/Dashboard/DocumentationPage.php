<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Dashboard;

/**
 * Renders a lightweight Civic Manager user manual inside wp-admin.
 */
class DocumentationPage
{
    public function render(): void
    {
        echo '<div class="wrap civic-admin-documentation">';
        echo '<h1>' . esc_html__('Civic Manager Documentation', 'civic-engagement') . '</h1>';
        echo '<p class="description">' . esc_html__('Use this guide as a quick reference for the Civic Platform administration tools.', 'civic-engagement') . '</p>';

        $sections = [
            [
                'title' => __('Dashboard', 'civic-engagement'),
                'items' => [
                    __('Review headline counts for representations, consultations, responses, events, registrations, schedules and contacts.', 'civic-engagement'),
                    __('Use recent activity sections to jump into the newest public participation records.', 'civic-engagement'),
                ],
            ],
            [
                'title' => __('Representations', 'civic-engagement'),
                'items' => [
                    __('Review public representation submissions and uploaded images.', 'civic-engagement'),
                    __('Update administrative status and internal comments.', 'civic-engagement'),
                    __('Convert a representation into a schedule item when follow-up action is required.', 'civic-engagement'),
                ],
            ],
            [
                'title' => __('Consultations', 'civic-engagement'),
                'items' => [
                    __('Create, edit and publish public consultations.', 'civic-engagement'),
                    __('Manage custom response fields and review submitted responses.', 'civic-engagement'),
                    __('Use lifecycle settings to keep closed consultations publicly viewable but read-only.', 'civic-engagement'),
                ],
            ],
            [
                'title' => __('Events', 'civic-engagement'),
                'items' => [
                    __('Create public events and configure registration fields.', 'civic-engagement'),
                    __('Review registrations and export participant data when needed.', 'civic-engagement'),
                    __('Closed or archived events remain viewable but no longer accept registrations.', 'civic-engagement'),
                ],
            ],
            [
                'title' => __('Schedules', 'civic-engagement'),
                'items' => [
                    __('Create public schedule items and manage visibility, priority and status.', 'civic-engagement'),
                    __('Add schedule notes and media where useful.', 'civic-engagement'),
                    __('Archive completed or cancelled schedule items to keep active listings focused.', 'civic-engagement'),
                ],
            ],
            [
                'title' => __('Contacts and Activities', 'civic-engagement'),
                'items' => [
                    __('Use Contacts to review people who have interacted with public workflows.', 'civic-engagement'),
                    __('Use Activities to inspect contact-centric participation history.', 'civic-engagement'),
                    __('Email remains the primary public identity key across submissions.', 'civic-engagement'),
                ],
            ],
            [
                'title' => __('System', 'civic-engagement'),
                'items' => [
                    __('Use Documentation for this administration guide.', 'civic-engagement'),
                    __('Use Security to configure shared public form CAPTCHA settings.', 'civic-engagement'),
                    __('Use Account to change your password or log out.', 'civic-engagement'),
                ],
            ],
        ];

        echo '<div class="civic-admin-manual">';
        foreach ($sections as $section) {
            echo '<section class="civic-admin-manual__section">';
            echo '<h2>' . esc_html($section['title']) . '</h2>';
            echo '<ul>';
            foreach ($section['items'] as $item) {
                echo '<li>' . esc_html($item) . '</li>';
            }
            echo '</ul>';
            echo '</section>';
        }
        echo '</div>';
        echo '</div>';
    }
}
