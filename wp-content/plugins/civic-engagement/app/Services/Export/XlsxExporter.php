<?php

declare(strict_types=1);

namespace CivicPlatform\Services\Export;

/**
 * Generates native XLSX workbooks using minimal OpenXML parts.
 */
class XlsxExporter implements ExporterInterface
{
    public function export(array $rows, array $columns): string
    {
        $zip = new SimpleZipArchive();
        $createdAt = gmdate('Y-m-d\TH:i:s\Z');

        $zip->addFile('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFile('_rels/.rels', $this->relsXml());
        $zip->addFile('docProps/app.xml', $this->appXml());
        $zip->addFile('docProps/core.xml', $this->coreXml($createdAt));
        $zip->addFile('xl/workbook.xml', $this->workbookXml());
        $zip->addFile('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        $zip->addFile('xl/styles.xml', $this->stylesXml());
        $zip->addFile('xl/worksheets/sheet1.xml', $this->worksheetXml($rows, $columns));

        return $zip->content();
    }

    public function contentType(): string
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

    public function extension(): string
    {
        return 'xlsx';
    }

    private function worksheetXml(array $rows, array $columns): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $xml .= '<sheetData>';
        $xml .= $this->rowXml(1, array_map(static fn(array $column): string => (string) ($column['label'] ?? $column['key'] ?? ''), $columns));

        $rowNumber = 2;
        foreach ($rows as $row) {
            $values = [];

            foreach ($columns as $column) {
                $values[] = $this->columnValue($row, $column);
            }

            $xml .= $this->rowXml($rowNumber, $values);
            $rowNumber++;
        }

        $xml .= '</sheetData></worksheet>';

        return $xml;
    }

    /**
     * @param array<int, mixed> $values
     */
    private function rowXml(int $rowNumber, array $values): string
    {
        $xml = '<row r="' . $rowNumber . '">';
        $columnIndex = 1;

        foreach ($values as $value) {
            $cell = $this->columnName($columnIndex) . $rowNumber;
            $text = $this->stringValue($value);
            $xml .= '<c r="' . $cell . '" t="inlineStr"><is><t';

            if ($text !== trim($text)) {
                $xml .= ' xml:space="preserve"';
            }

            $xml .= '>' . $this->escape($text) . '</t></is></c>';
            $columnIndex++;
        }

        return $xml . '</row>';
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $column
     */
    private function columnValue(array $row, array $column)
    {
        if (isset($column['callback']) && is_callable($column['callback'])) {
            return $column['callback']($row, $column);
        }

        $key = (string) ($column['key'] ?? '');

        return '' === $key ? '' : ($row[$key] ?? '');
    }

    private function columnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = (int) floor($index / 26);
        }

        return $name;
    }

    private function stringValue($value): string
    {
        if (null === $value) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return '';
    }

    private function escape(string $value): string
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? '';

        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private function relsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Export" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>'
            . '</styleSheet>';
    }

    private function appXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"'
            . ' xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>Civic Platform</Application>'
            . '</Properties>';
    }

    private function coreXml(string $createdAt): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties"'
            . ' xmlns:dc="http://purl.org/dc/elements/1.1/"'
            . ' xmlns:dcterms="http://purl.org/dc/terms/"'
            . ' xmlns:dcmitype="http://purl.org/dc/dcmitype/"'
            . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:creator>Civic Platform</dc:creator>'
            . '<cp:lastModifiedBy>Civic Platform</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $createdAt . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $createdAt . '</dcterms:modified>'
            . '</cp:coreProperties>';
    }
}
