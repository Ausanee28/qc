<?php

namespace App\Support;

use XMLWriter;

class SimpleExcelXmlExporter
{
    public function store(iterable $rows, string $destination, array $columnWidths = [], string $sheetName = 'Report'): void
    {
        $writer = new XMLWriter();
        $writer->openUri($destination);
        $writer->startDocument('1.0', 'UTF-8');
        $writer->writePi('mso-application', 'progid="Excel.Sheet"');

        $writer->startElement('Workbook');
        $writer->writeAttribute('xmlns', 'urn:schemas-microsoft-com:office:spreadsheet');
        $writer->writeAttribute('xmlns:o', 'urn:schemas-microsoft-com:office:office');
        $writer->writeAttribute('xmlns:x', 'urn:schemas-microsoft-com:office:excel');
        $writer->writeAttribute('xmlns:ss', 'urn:schemas-microsoft-com:office:spreadsheet');
        $writer->writeAttribute('xmlns:html', 'http://www.w3.org/TR/REC-html40');

        $this->writeStyles($writer);

        $writer->startElement('Worksheet');
        $writer->writeAttribute('ss:Name', $this->sanitizeSheetName($sheetName));

        $writer->startElement('Table');

        foreach (array_values($columnWidths) as $width) {
            $writer->startElement('Column');
            $writer->writeAttribute('ss:AutoFitWidth', '0');
            $writer->writeAttribute('ss:Width', (string) $width);
            $writer->endElement();
        }

        $rowNumber = 1;

        foreach ($rows as $row) {
            $writer->startElement('Row');

            foreach (array_values($row) as $value) {
                $writer->startElement('Cell');

                if ($rowNumber === 1) {
                    $writer->writeAttribute('ss:StyleID', 'Header');
                }

                $writer->startElement('Data');
                $writer->writeAttribute('ss:Type', 'String');
                $writer->text($this->sanitizeCellValue($value));
                $writer->endElement();
                $writer->endElement();
            }

            $writer->endElement();
            $rowNumber++;
        }

        $writer->endElement();
        $writer->endElement();
        $writer->endElement();
        $writer->endDocument();
        $writer->flush();
    }

    private function writeStyles(XMLWriter $writer): void
    {
        $writer->startElement('Styles');

        $writer->startElement('Style');
        $writer->writeAttribute('ss:ID', 'Default');
        $writer->writeAttribute('ss:Name', 'Normal');
        $writer->startElement('Alignment');
        $writer->writeAttribute('ss:Vertical', 'Center');
        $writer->endElement();
        $writer->startElement('Font');
        $writer->writeAttribute('ss:FontName', 'Calibri');
        $writer->writeAttribute('ss:Size', '11');
        $writer->endElement();
        $writer->endElement();

        $writer->startElement('Style');
        $writer->writeAttribute('ss:ID', 'Header');
        $writer->startElement('Font');
        $writer->writeAttribute('ss:FontName', 'Calibri');
        $writer->writeAttribute('ss:Size', '11');
        $writer->writeAttribute('ss:Bold', '1');
        $writer->endElement();
        $writer->endElement();

        $writer->endElement();
    }

    private function sanitizeCellValue(mixed $value): string
    {
        $string = (string) ($value ?? '');

        return preg_replace('/[^\P{C}\t\n\r]/u', '', $string) ?? '';
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
