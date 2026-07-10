<?php

declare(strict_types=1);

namespace CivicPlatform\Services\Export;

/**
 * Contract for export providers.
 */
interface ExporterInterface
{
    /**
     * Export rows using configured columns.
     *
     * @param array<int, array<string, mixed>> $rows Data rows.
     * @param array<int, array<string, mixed>> $columns Column definitions.
     * @return string Binary export content.
     */
    public function export(array $rows, array $columns): string;

    public function contentType(): string;

    public function extension(): string;
}
