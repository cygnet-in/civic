<?php

declare(strict_types=1);

namespace CivicPlatform\Modules\Users\Admin;

use CivicPlatform\Services\ContactService;

/**
 * Renders the contact listing, consent filters, and CSV export.
 */
class ContactsListPage
{
    private const CAPABILITY = 'manage_civic_contacts';
    private const PAGE_SLUG = 'civic-contacts';

    private ContactService $contacts;

    public function __construct(ContactService $contacts)
    {
        $this->contacts = $contacts;
    }

    /**
     * Render the contacts admin page.
     *
     * @return void
     */
    public function render(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'civic-engagement'));
        }

        $filters = $this->filters();
        $page = $this->currentPage();
        $result = '' === $filters['search']
            ? $this->contacts->getPaginated(array_merge($filters, ['page' => $page, 'per_page' => 20]))
            : $this->contacts->search($filters['search'], array_merge($filters, ['page' => $page, 'per_page' => 20]));
        $items = isset($result['items']) && is_array($result['items']) ? $result['items'] : [];
        $totalPages = isset($result['total_pages']) ? (int) $result['total_pages'] : 1;

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Contacts', 'civic-engagement') . '</h1>';
        $this->renderFilters($filters);
        $this->renderTable($items);
        $this->renderPagination($filters, $page, $totalPages);
        echo '</div>';
    }

    /**
     * Stream a filtered contact CSV export.
     *
     * @return void
     */
    public function export(): void
    {
        $filters = $this->filters();
        $items = $this->contacts->getForExport($filters);

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=civic-contacts.csv');

        $output = fopen('php://output', 'w');

        if (false === $output) {
            exit;
        }

        fputcsv($output, ['Email', 'Name', 'Phone', 'WhatsApp', 'Address', 'Eircode', 'Electoral Area', 'Email Consent', 'Call Consent', 'SMS Consent', 'Post Consent', 'Consent Updated At']);

        foreach ($items as $item) {
            fputcsv($output, [
                (string) ($item['email'] ?? ''),
                (string) ($item['latest_name'] ?? ''),
                (string) ($item['latest_phone'] ?? ''),
                (string) ($item['latest_whatsapp'] ?? ''),
                (string) ($item['latest_address'] ?? ''),
                (string) ($item['latest_eircode'] ?? ''),
                (string) ($item['latest_electoral_area'] ?? ''),
                !empty($item['consent_email']) ? 'Yes' : 'No',
                !empty($item['consent_call']) ? 'Yes' : 'No',
                !empty($item['consent_sms']) ? 'Yes' : 'No',
                !empty($item['consent_post']) ? 'Yes' : 'No',
                (string) ($item['consent_updated_at'] ?? ''),
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Render search, consent filters, and export action.
     *
     * @param array<string, mixed> $filters Current filters.
     * @return void
     */
    private function renderFilters(array $filters): void
    {
        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="' . esc_attr(self::PAGE_SLUG) . '">';
        echo '<p class="search-box"><label class="screen-reader-text" for="civic-contacts-search">' . esc_html__('Search Contacts', 'civic-engagement') . '</label>';
        echo '<input type="search" id="civic-contacts-search" name="s" value="' . esc_attr((string) $filters['search']) . '"> ';
        submit_button(__('Search Contacts', 'civic-engagement'), '', '', false);
        echo '</p>';

        foreach ($this->consentLabels() as $field => $label) {
            echo '<label for="civic-contacts-' . esc_attr($field) . '">' . esc_html($label) . ': </label>';
            echo '<select id="civic-contacts-' . esc_attr($field) . '" name="' . esc_attr($field) . '">';
            echo '<option value="">' . esc_html__('Any', 'civic-engagement') . '</option>';
            echo '<option value="1"' . selected((string) $filters[$field], '1', false) . '>' . esc_html__('Yes', 'civic-engagement') . '</option>';
            echo '<option value="0"' . selected((string) $filters[$field], '0', false) . '>' . esc_html__('No', 'civic-engagement') . '</option>';
            echo '</select> ';
        }

        echo '</form>';
        echo '<p><a class="button" href="' . esc_url($this->exportUrl($filters)) . '">' . esc_html__('Export Contacts', 'civic-engagement') . '</a></p>';
    }

    /**
     * Render contact rows.
     *
     * @param array<int, array<string, mixed>> $items Contact rows.
     * @return void
     */
    private function renderTable(array $items): void
    {
        echo '<table class="widefat fixed striped"><thead><tr>';
        echo '<th scope="col">' . esc_html__('Email', 'civic-engagement') . '</th>';
        echo '<th scope="col">' . esc_html__('Name', 'civic-engagement') . '</th>';

        foreach ($this->consentLabels() as $label) {
            echo '<th scope="col">' . esc_html($label) . '</th>';
        }

        echo '<th scope="col">' . esc_html__('Consent Updated', 'civic-engagement') . '</th>';
        echo '</tr></thead><tbody>';

        if (empty($items)) {
            echo '<tr><td colspan="7">' . esc_html__('No contacts found.', 'civic-engagement') . '</td></tr>';
        }

        foreach ($items as $item) {
            echo '<tr>';
            echo '<td>' . esc_html((string) ($item['email'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($item['latest_name'] ?? '')) . '</td>';

            foreach (array_keys($this->consentLabels()) as $field) {
                echo '<td>' . esc_html(!empty($item[$field]) ? __('Yes', 'civic-engagement') : __('No', 'civic-engagement')) . '</td>';
            }

            echo '<td>' . esc_html((string) ($item['consent_updated_at'] ?? '')) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    /**
     * Render simple pagination.
     *
     * @param array<string, mixed> $filters Current filters.
     * @param int $page Current page.
     * @param int $totalPages Total pages.
     * @return void
     */
    private function renderPagination(array $filters, int $page, int $totalPages): void
    {
        if ($totalPages <= 1) {
            return;
        }

        echo '<p class="tablenav-pages">';

        if ($page > 1) {
            echo '<a class="button" href="' . esc_url($this->pageUrl($filters, $page - 1)) . '">' . esc_html__('Previous', 'civic-engagement') . '</a> ';
        }

        echo esc_html(sprintf(__('Page %1$d of %2$d', 'civic-engagement'), $page, $totalPages));

        if ($page < $totalPages) {
            echo ' <a class="button" href="' . esc_url($this->pageUrl($filters, $page + 1)) . '">' . esc_html__('Next', 'civic-engagement') . '</a>';
        }

        echo '</p>';
    }

    /**
     * Get sanitized filters from the request.
     *
     * @return array<string, mixed>
     */
    private function filters(): array
    {
        $filters = ['search' => ''];

        if (isset($_GET['s']) && !is_array($_GET['s']) && !is_object($_GET['s'])) {
            $filters['search'] = sanitize_text_field((string) wp_unslash($_GET['s']));
        }

        foreach (array_keys($this->consentLabels()) as $field) {
            $filters[$field] = '';

            if (!isset($_GET[$field]) || is_array($_GET[$field]) || is_object($_GET[$field])) {
                continue;
            }

            $value = (string) wp_unslash($_GET[$field]);
            $filters[$field] = in_array($value, ['0', '1'], true) ? $value : '';
        }

        return $filters;
    }

    /**
     * Get consent field labels.
     *
     * @return array<string, string>
     */
    private function consentLabels(): array
    {
        return [
            'consent_email' => __('Email Consent', 'civic-engagement'),
            'consent_call' => __('Call Consent', 'civic-engagement'),
            'consent_sms' => __('SMS Consent', 'civic-engagement'),
            'consent_post' => __('Post Consent', 'civic-engagement'),
        ];
    }

    /**
     * Build an export URL.
     *
     * @param array<string, mixed> $filters Current filters.
     * @return string
     */
    private function exportUrl(array $filters): string
    {
        return wp_nonce_url(
            add_query_arg(array_merge(['page' => self::PAGE_SLUG, 'civic_contact_export' => 1], $filters), admin_url('admin.php')),
            'civic_contact_export'
        );
    }

    /**
     * Build a pagination URL.
     *
     * @param array<string, mixed> $filters Current filters.
     * @param int $page Page number.
     * @return string
     */
    private function pageUrl(array $filters, int $page): string
    {
        return add_query_arg(array_merge(['page' => self::PAGE_SLUG, 'paged' => max(1, $page)], $filters), admin_url('admin.php'));
    }

    /**
     * Get the current page number.
     *
     * @return int
     */
    private function currentPage(): int
    {
        if (!isset($_GET['paged']) || is_array($_GET['paged']) || is_object($_GET['paged'])) {
            return 1;
        }

        return max(1, absint(wp_unslash($_GET['paged'])));
    }
}
