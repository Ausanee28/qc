<?php

namespace App\Support;

use XMLWriter;

class SimpleXlsxExporter
{
    public function store(iterable $rows, string $destination, array $columnWidths = [], string $sheetName = 'Report'): void
    {
        $tempSheet = tempnam(sys_get_temp_dir(), 'qc-sheet-');

        if ($tempSheet === false) {
            throw new \RuntimeException('Unable to create a temporary worksheet file.');
        }

        try {
            $this->writeWorksheet($rows, $tempSheet, $columnWidths);

            $this->writeZipArchive($destination, [
                '[Content_Types].xml' => $this->contentTypesXml(),
                '_rels/.rels' => $this->rootRelationshipsXml(),
                'xl/workbook.xml' => $this->workbookXml($sheetName),
                'xl/_rels/workbook.xml.rels' => $this->workbookRelationshipsXml(),
                'xl/styles.xml' => $this->stylesXml(),
                'xl/worksheets/sheet1.xml' => file_get_contents($tempSheet) ?: '',
            ]);
        } finally {
            if (is_file($tempSheet)) {
                @unlink($tempSheet);
            }
        }
    }

    private function writeWorksheet(iterable $rows, string $destination, array $columnWidths): void
    {
        $writer = new XMLWriter();
        $writer->openUri($destination);
        $writer->startDocument('1.0', 'UTF-8', 'yes');
        $writer->startElement('worksheet');
        $writer->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        if ($columnWidths !== []) {
            $writer->startElement('cols');

            foreach (array_values($columnWidths) as $index => $width) {
                $writer->startElement('col');
                $writer->writeAttribute('min', (string) ($index + 1));
                $writer->writeAttribute('max', (string) ($index + 1));
                $writer->writeAttribute('width', (string) $width);
                $writer->writeAttribute('customWidth', '1');
                $writer->endElement();
            }

            $writer->endElement();
        }

        $writer->startElement('sheetData');

        $rowNumber = 1;

        foreach ($rows as $row) {
            $writer->startElement('row');
            $writer->writeAttribute('r', (string) $rowNumber);

            foreach (array_values($row) as $columnIndex => $value) {
                $cellReference = $this->columnName($columnIndex + 1) . $rowNumber;

                $writer->startElement('c');
                $writer->writeAttribute('r', $cellReference);
                $writer->writeAttribute('t', 'inlineStr');

                if ($rowNumber === 1) {
                    $writer->writeAttribute('s', '1');
                }

                $writer->startElement('is');
                $writer->writeElement('t', $this->sanitizeCellValue($value));
                $writer->endElement();
                $writer->endElement();
            }

            $writer->endElement();
            $rowNumber++;
        }

        $writer->endElement();
        $writer->endElement();
        $writer->endDocument();
        $writer->flush();
    }

    private function columnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function sanitizeCellValue(mixed $value): string
    {
        $string = (string) ($value ?? '');

        return preg_replace('/[^\P{C}\t\n\r]/u', '', $string) ?? '';
    }

    private function writeZipArchive(string $destination, array $entries): void
    {
        $handle = fopen($destination, 'wb');

        if ($handle === false) {
            throw new \RuntimeException('Unable to create the Excel workbook.');
        }

        $centralDirectory = '';
        $offset = 0;
        $entryCount = 0;

        foreach ($entries as $name => $data) {
            $entryCount++;
            $filename = str_replace('\\', '/', $name);
            $filenameLength = strlen($filename);
            $size = strlen($data);
            $crc = hexdec(hash('crc32b', $data));

            $localHeader = pack(
                'VvvvvvVVVvv',
                0x04034b50,
                20,
                0,
                0,
                0,
                0,
                $crc,
                $size,
                $size,
                $filenameLength,
                0
            );

            fwrite($handle, $localHeader);
            fwrite($handle, $filename);
            fwrite($handle, $data);

            $centralDirectory .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
                20,
                20,
                0,
                0,
                0,
                0,
                $crc,
                $size,
                $size,
                $filenameLength,
                0,
                0,
                0,
                0,
                0,
                $offset
            ) . $filename;

            $offset += strlen($localHeader) + $filenameLength + $size;
        }

        $centralSize = strlen($centralDirectory);
        fwrite($handle, $centralDirectory);

        $endOfCentralDirectory = pack(
            'VvvvvVVv',
            0x06054b50,
            0,
            0,
            $entryCount,
            $entryCount,
            $centralSize,
            $offset,
            0
        );

        fwrite($handle, $endOfCentralDirectory);
        fclose($handle);
    }

    private function contentTypesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>
XML;
    }

    private function rootRelationshipsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML;
    }

    private function workbookXml(string $sheetName): string
    {
        $safeName = htmlspecialchars($this->sanitizeSheetName($sheetName), ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="{$safeName}" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>
XML;
    }

    private function workbookRelationshipsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML;
    }

    private function stylesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="2">
    <font>
      <sz val="11"/>
      <name val="Calibri"/>
    </font>
    <font>
      <b/>
      <sz val="11"/>
      <name val="Calibri"/>
    </font>
  </fonts>
  <fills count="2">
    <fill>
      <patternFill patternType="none"/>
    </fill>
    <fill>
      <patternFill patternType="gray125"/>
    </fill>
  </fills>
  <borders count="1">
    <border>
      <left/>
      <right/>
      <top/>
      <bottom/>
      <diagonal/>
    </border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="2">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>
  </cellXfs>
  <cellStyles count="1">
    <cellStyle name="Normal" xfId="0" builtinId="0"/>
  </cellStyles>
</styleSheet>
XML;
    }

    private function sanitizeSheetName(string $sheetName): string
    {
        $value = preg_replace('/[\[\]\*\/\\\\\?:]/', ' ', trim($sheetName)) ?? 'Report';
        $value = trim($value);

        if ($value === '') {
            $value = 'Report';
        }

        return mb_substr($value, 0, 31);
    }
}
