<?php

declare(strict_types=1);

namespace CivicPlatform\Services\Export;

/**
 * Coordinates admin export generation and download responses.
 */
class ExportManager
{
    /**
     * @var array<string, ExporterInterface>
     */
    private array $exporters = [];

    public function __construct(?ExporterInterface $xlsxExporter = null)
    {
        $this->register('xlsx', $xlsxExporter ?? new XlsxExporter());
    }

    public function register(string $format, ExporterInterface $exporter): void
    {
        $this->exporters[strtolower($format)] = $exporter;
    }

    /**
     * Generate export content.
     *
     * @param array<int, array<string, mixed>> $rows Data rows.
     * @param array<int, array<string, mixed>> $columns Column definitions.
     * @param string $format Export format.
     * @return string Binary export content.
     */
    public function generate(array $rows, array $columns, string $format = 'xlsx'): string
    {
        return $this->exporter($format)->export($rows, $columns);
    }

    /**
     * Stream an export as a browser download.
     *
     * @param array<int, array<string, mixed>> $rows Data rows.
     * @param array<int, array<string, mixed>> $columns Column definitions.
     * @param string $filename Filename without or with extension.
     * @param string $format Export format.
     * @return void
     */
    public function download(array $rows, array $columns, string $filename, string $format = 'xlsx'): void
    {
        $exporter = $this->exporter($format);
        $content = $exporter->export($rows, $columns);
        $filename = $this->filename($filename, $exporter->extension());

        nocache_headers();
        header('Content-Type: ' . $exporter->contentType());
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));

        echo $content;
        exit;
    }

    private function exporter(string $format): ExporterInterface
    {
        $format = strtolower($format);

        if (!isset($this->exporters[$format])) {
            throw new \InvalidArgumentException('Unsupported export format: ' . $format);
        }

        return $this->exporters[$format];
    }

    private function filename(string $filename, string $extension): string
    {
        $filename = trim($filename);

        if (function_exists('sanitize_file_name')) {
            $filename = sanitize_file_name($filename);
        }

        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $filename) ?: 'export';
        $filename = trim($filename, '.-_');

        if ('' === $filename) {
            $filename = 'export';
        }

        $suffix = '.' . ltrim($extension, '.');

        if (substr(strtolower($filename), -strlen($suffix)) !== strtolower($suffix)) {
            $filename .= $suffix;
        }

        return $filename;
    }
}
