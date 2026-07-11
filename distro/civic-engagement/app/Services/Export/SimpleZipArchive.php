<?php

declare(strict_types=1);

namespace CivicPlatform\Services\Export;

/**
 * Minimal ZIP writer used for dependency-free XLSX generation.
 */
class SimpleZipArchive
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $files = [];

    public function addFile(string $name, string $content): void
    {
        $this->files[] = [
            'name' => str_replace('\\', '/', ltrim($name, '/')),
            'content' => $content,
        ];
    }

    public function content(): string
    {
        $body = '';
        $centralDirectory = '';
        $offset = 0;
        $timestamp = $this->dosTimestamp();

        foreach ($this->files as $file) {
            $name = (string) $file['name'];
            $content = (string) $file['content'];
            $compressed = gzdeflate($content);
            $method = 8;

            if (false === $compressed || strlen($compressed) >= strlen($content)) {
                $compressed = $content;
                $method = 0;
            }

            $crc = crc32($content);
            $compressedSize = strlen($compressed);
            $uncompressedSize = strlen($content);
            $nameLength = strlen($name);

            $localHeader = pack(
                'VvvvvvVVVvv',
                0x04034b50,
                20,
                0,
                $method,
                $timestamp['time'],
                $timestamp['date'],
                $crc,
                $compressedSize,
                $uncompressedSize,
                $nameLength,
                0
            );

            $body .= $localHeader . $name . $compressed;

            $centralDirectory .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
                20,
                20,
                0,
                $method,
                $timestamp['time'],
                $timestamp['date'],
                $crc,
                $compressedSize,
                $uncompressedSize,
                $nameLength,
                0,
                0,
                0,
                0,
                0,
                $offset
            ) . $name;

            $offset += strlen($localHeader) + $nameLength + $compressedSize;
        }

        $centralDirectoryOffset = strlen($body);
        $centralDirectorySize = strlen($centralDirectory);
        $fileCount = count($this->files);

        $end = pack(
            'VvvvvVVv',
            0x06054b50,
            0,
            0,
            $fileCount,
            $fileCount,
            $centralDirectorySize,
            $centralDirectoryOffset,
            0
        );

        return $body . $centralDirectory . $end;
    }

    /**
     * @return array{time: int, date: int}
     */
    private function dosTimestamp(): array
    {
        $parts = getdate();
        $year = max(1980, (int) $parts['year']);

        return [
            'time' => ((int) $parts['hours'] << 11) | ((int) $parts['minutes'] << 5) | ((int) floor((int) $parts['seconds'] / 2)),
            'date' => (($year - 1980) << 9) | ((int) $parts['mon'] << 5) | (int) $parts['mday'],
        ];
    }
}
