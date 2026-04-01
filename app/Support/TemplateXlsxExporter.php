<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class TemplateXlsxExporter
{
    private const NS_MAIN = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    private const NS_REL_OFFICE = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const NS_REL_PACKAGE = 'http://schemas.openxmlformats.org/package/2006/relationships';

    public function store(iterable $rows, string $templatePath, string $destination, array $options = []): void
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Template export requires PHP ZIP extension (ext-zip).');
        }

        if (!is_file($templatePath)) {
            throw new RuntimeException('Excel template file was not found.');
        }

        if (!copy($templatePath, $destination)) {
            throw new RuntimeException('Unable to create the template-based Excel export file.');
        }

        $zip = new ZipArchive();

        if ($zip->open($destination) !== true) {
            throw new RuntimeException('Unable to open the template workbook.');
        }

        try {
            $marker = trim((string) ($options['marker'] ?? '{{DATA_TABLE}}'));
            $sheetName = trim((string) ($options['sheet_name'] ?? ''));
            $startCell = strtoupper(trim((string) ($options['start_cell'] ?? '')));

            $sharedStrings = $this->readSharedStrings($zip);
            $sheets = $this->resolveSheets($zip);

            $targetSheetPath = null;
            $targetSheetDom = null;
            $markerInfo = null;

            if ($startCell !== '' && $this->splitCellReference($startCell)[0] !== '') {
                $selectedSheet = $sheetName !== ''
                    ? $this->findSheetByName($sheets, $sheetName)
                    : ($sheets[0] ?? null);

                if ($sheetName !== '' && $selectedSheet === null) {
                    throw new RuntimeException($this->sheetNotFoundMessage($sheetName, $sheets));
                }

                if ($selectedSheet === null) {
                    throw new RuntimeException('Template workbook has no worksheet.');
                }

                $sheetXml = $zip->getFromName($selectedSheet['path']);

                if ($sheetXml === false) {
                    throw new RuntimeException('Unable to open the selected worksheet in template.');
                }

                $targetSheetDom = $this->createXmlDom($sheetXml);
                $markerInfo = $this->markerInfoFromCellReference($targetSheetDom, $startCell);
                $targetSheetPath = $selectedSheet['path'];
            } else {
                if ($sheetName !== '') {
                    $selectedSheet = $this->findSheetByName($sheets, $sheetName);

                    if ($selectedSheet === null) {
                        throw new RuntimeException($this->sheetNotFoundMessage($sheetName, $sheets));
                    }

                    $candidateSheets = [$selectedSheet];
                } else {
                    $candidateSheets = $sheets;
                }

                foreach ($candidateSheets as $sheet) {
                    $sheetXml = $zip->getFromName($sheet['path']);

                    if ($sheetXml === false) {
                        continue;
                    }

                    $sheetDom = $this->createXmlDom($sheetXml);
                    $found = $this->findMarkerCell($sheetDom, $sharedStrings, $marker);

                    if ($found === null) {
                        continue;
                    }

                    $targetSheetPath = $sheet['path'];
                    $targetSheetDom = $sheetDom;
                    $markerInfo = $found;
                    break;
                }

                if ($targetSheetPath === null || $targetSheetDom === null || $markerInfo === null) {
                    throw new RuntimeException("Template marker {$marker} was not found in workbook.");
                }
            }

            if ($targetSheetPath === null || $targetSheetDom === null || $markerInfo === null) {
                if ($startCell !== '') {
                    throw new RuntimeException("Template start cell {$startCell} could not be resolved.");
                }

                throw new RuntimeException("Template marker {$marker} was not found in workbook.");
            }

            $this->injectRowsIntoSheet($targetSheetDom, $rows, $markerInfo);
            $sheetXml = $targetSheetDom->saveXML();

            if ($sheetXml === false) {
                throw new RuntimeException('Unable to serialize the updated worksheet.');
            }

            $zip->addFromString($targetSheetPath, $sheetXml);
        } finally {
            $zip->close();
        }
    }

    private function resolveSheets(ZipArchive $zip): array
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relationsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relationsXml === false) {
            return [['name' => 'Sheet1', 'path' => 'xl/worksheets/sheet1.xml']];
        }

        $workbookDom = $this->createXmlDom($workbookXml);
        $workbookXpath = new DOMXPath($workbookDom);
        $workbookXpath->registerNamespace('m', self::NS_MAIN);
        $workbookXpath->registerNamespace('r', self::NS_REL_OFFICE);

        $relsDom = $this->createXmlDom($relationsXml);
        $relsXpath = new DOMXPath($relsDom);
        $relsXpath->registerNamespace('p', self::NS_REL_PACKAGE);

        $targetsById = [];
        $relationNodes = $relsXpath->query('/p:Relationships/p:Relationship');

        if ($relationNodes !== false) {
            foreach ($relationNodes as $relationNode) {
                $id = (string) ($relationNode->attributes?->getNamedItem('Id')?->nodeValue ?? '');
                $target = (string) ($relationNode->attributes?->getNamedItem('Target')?->nodeValue ?? '');

                if ($id === '' || $target === '') {
                    continue;
                }

                $targetsById[$id] = ltrim($target, '/');
            }
        }

        $sheets = [];
        $sheetNodes = $workbookXpath->query('/m:workbook/m:sheets/m:sheet');

        if ($sheetNodes !== false) {
            foreach ($sheetNodes as $sheetNode) {
                $relationId = (string) ($sheetNode->attributes?->getNamedItemNS(self::NS_REL_OFFICE, 'id')?->nodeValue ?? '');
                $sheetName = (string) ($sheetNode->attributes?->getNamedItem('name')?->nodeValue ?? 'Sheet');

                if ($relationId === '' || !isset($targetsById[$relationId])) {
                    continue;
                }

                $sheets[] = [
                    'name' => $sheetName,
                    'path' => 'xl/' . ltrim($targetsById[$relationId], '/'),
                ];
            }
        }

        return $sheets !== [] ? $sheets : [['name' => 'Sheet1', 'path' => 'xl/worksheets/sheet1.xml']];
    }

    private function readSharedStrings(ZipArchive $zip): array
    {
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');

        if ($sharedStringsXml === false) {
            return [];
        }

        $dom = $this->createXmlDom($sharedStringsXml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('m', self::NS_MAIN);

        $values = [];
        $stringItems = $xpath->query('/m:sst/m:si');

        if ($stringItems === false) {
            return $values;
        }

        foreach ($stringItems as $item) {
            $textNodes = $xpath->query('.//m:t', $item);
            $value = '';

            if ($textNodes !== false) {
                foreach ($textNodes as $textNode) {
                    $value .= $textNode->textContent;
                }
            }

            $values[] = $value;
        }

        return $values;
    }

    private function findSheetByName(array $sheets, string $sheetName): ?array
    {
        $target = $this->normalizeSheetName($sheetName);

        foreach ($sheets as $sheet) {
            $name = $this->normalizeSheetName((string) ($sheet['name'] ?? ''));

            if ($name === $target) {
                return $sheet;
            }
        }

        return null;
    }

    private function normalizeSheetName(string $name): string
    {
        $value = trim($name);

        return mb_strtolower($value, 'UTF-8');
    }

    private function sheetNotFoundMessage(string $sheetName, array $sheets): string
    {
        $available = array_values(array_filter(array_map(
            fn ($sheet) => trim((string) ($sheet['name'] ?? '')),
            $sheets
        )));

        $listed = $available !== [] ? implode(', ', $available) : 'none';

        return "Sheet \"{$sheetName}\" not found in template. Available sheets: {$listed}";
    }

    private function markerInfoFromCellReference(DOMDocument $sheetDom, string $cellReference): array
    {
        [$columnName, $rowNumber] = $this->splitCellReference($cellReference);

        if ($columnName === '' || $rowNumber <= 0) {
            throw new RuntimeException("Invalid start cell reference: {$cellReference}");
        }

        $xpath = new DOMXPath($sheetDom);
        $xpath->registerNamespace('m', self::NS_MAIN);
        $rowNode = $xpath->query("/m:worksheet/m:sheetData/m:row[@r='{$rowNumber}']")->item(0);
        $styles = $rowNode instanceof DOMElement ? $this->extractRowStyles($rowNode) : [];

        return [
            'row' => $rowNumber,
            'column' => $this->columnIndex($columnName),
            'styles' => $styles,
        ];
    }

    private function findMarkerCell(DOMDocument $sheetDom, array $sharedStrings, string $marker): ?array
    {
        $xpath = new DOMXPath($sheetDom);
        $xpath->registerNamespace('m', self::NS_MAIN);

        $cells = $xpath->query('/m:worksheet/m:sheetData/m:row/m:c');

        if ($cells === false) {
            return null;
        }

        $needle = trim($marker);

        foreach ($cells as $cell) {
            if (!$cell instanceof DOMElement) {
                continue;
            }

            $value = trim($this->readCellValue($cell, $xpath, $sharedStrings));

            if ($value !== $needle) {
                continue;
            }

            $cellRef = (string) $cell->getAttribute('r');
            [$columnName, $rowNumber] = $this->splitCellReference($cellRef);

            if ($columnName === '' || $rowNumber <= 0) {
                continue;
            }

            $rowNode = $cell->parentNode;

            if (!$rowNode instanceof DOMElement || $rowNode->tagName !== 'row') {
                continue;
            }

            return [
                'row' => $rowNumber,
                'column' => $this->columnIndex($columnName),
                'styles' => $this->extractRowStyles($rowNode),
            ];
        }

        return null;
    }

    private function injectRowsIntoSheet(DOMDocument $sheetDom, iterable $rows, array $markerInfo): void
    {
        $xpath = new DOMXPath($sheetDom);
        $xpath->registerNamespace('m', self::NS_MAIN);

        $sheetData = $xpath->query('/m:worksheet/m:sheetData')->item(0);

        if (!$sheetData instanceof DOMElement) {
            throw new RuntimeException('Template worksheet has no <sheetData> section.');
        }

        $startRow = (int) $markerInfo['row'];
        $startColumn = (int) $markerInfo['column'];
        $rowStyles = is_array($markerInfo['styles']) ? $markerInfo['styles'] : [];

        $rowNodesByNumber = [];
        $maxRow = 1;
        $maxColumn = 1;

        foreach ($xpath->query('./m:row', $sheetData) ?: [] as $rowNode) {
            if (!$rowNode instanceof DOMElement) {
                continue;
            }

            $rowNumber = (int) $rowNode->getAttribute('r');

            if ($rowNumber <= 0) {
                continue;
            }

            $rowNodesByNumber[$rowNumber] = $rowNode;
            $maxRow = max($maxRow, $rowNumber);

            foreach ($xpath->query('./m:c', $rowNode) ?: [] as $cellNode) {
                if (!$cellNode instanceof DOMElement) {
                    continue;
                }

                [$columnName] = $this->splitCellReference((string) $cellNode->getAttribute('r'));

                if ($columnName !== '') {
                    $maxColumn = max($maxColumn, $this->columnIndex($columnName));
                }
            }
        }

        $rowsArray = [];

        foreach ($rows as $row) {
            $rowsArray[] = array_values(is_array($row) ? $row : iterator_to_array($row));
        }

        if ($rowsArray === []) {
            if (isset($rowNodesByNumber[$startRow])) {
                $this->clearRowCellsInRange($rowNodesByNumber[$startRow], $startColumn, $startColumn + 32);
            }

            $this->rebuildRowsAndDimension($sheetDom, $sheetData, $rowNodesByNumber, $maxColumn, $maxRow);
            return;
        }

        $insertedMaxColumn = $startColumn;

        foreach ($rowsArray as $offset => $rowValues) {
            $targetRowNumber = $startRow + $offset;
            $targetRowNode = $rowNodesByNumber[$targetRowNumber] ?? null;

            if (!$targetRowNode instanceof DOMElement) {
                $targetRowNode = $sheetDom->createElementNS(self::NS_MAIN, 'row');
                $targetRowNode->setAttribute('r', (string) $targetRowNumber);
                $rowNodesByNumber[$targetRowNumber] = $targetRowNode;
            }

            $this->clearRowCellsInRange(
                $targetRowNode,
                $startColumn,
                $startColumn + max(1, count($rowValues)) - 1
            );

            foreach ($rowValues as $index => $value) {
                $columnIndex = $startColumn + $index;
                $cellRef = $this->columnName($columnIndex) . $targetRowNumber;
                $styleId = $rowStyles[$columnIndex] ?? ($rowStyles[$startColumn] ?? null);
                $cellNode = $this->createInlineCell($sheetDom, $cellRef, $value, $styleId);
                $this->insertCellSorted($targetRowNode, $cellNode);
                $insertedMaxColumn = max($insertedMaxColumn, $columnIndex);
            }

            $maxRow = max($maxRow, $targetRowNumber);
        }

        $maxColumn = max($maxColumn, $insertedMaxColumn);
        $this->rebuildRowsAndDimension($sheetDom, $sheetData, $rowNodesByNumber, $maxColumn, $maxRow);
    }

    private function rebuildRowsAndDimension(DOMDocument $sheetDom, DOMElement $sheetData, array $rowNodesByNumber, int $maxColumn, int $maxRow): void
    {
        foreach (iterator_to_array($sheetData->childNodes) as $child) {
            if ($child instanceof DOMElement && $child->tagName === 'row') {
                $sheetData->removeChild($child);
            }
        }

        ksort($rowNodesByNumber);

        foreach ($rowNodesByNumber as $rowNumber => $rowNode) {
            if (!$rowNode instanceof DOMElement) {
                continue;
            }

            $rowNode->setAttribute('r', (string) $rowNumber);
            $sheetData->appendChild($rowNode);
        }

        $xpath = new DOMXPath($sheetDom);
        $xpath->registerNamespace('m', self::NS_MAIN);
        $dimensionNode = $xpath->query('/m:worksheet/m:dimension')->item(0);
        $range = 'A1:' . $this->columnName(max(1, $maxColumn)) . max(1, $maxRow);

        if ($dimensionNode instanceof DOMElement) {
            $dimensionNode->setAttribute('ref', $range);
            return;
        }

        $worksheet = $sheetDom->documentElement;

        if (!$worksheet instanceof DOMElement) {
            return;
        }

        $dimension = $sheetDom->createElementNS(self::NS_MAIN, 'dimension');
        $dimension->setAttribute('ref', $range);
        $worksheet->insertBefore($dimension, $worksheet->firstChild);
    }

    private function createInlineCell(DOMDocument $dom, string $cellRef, mixed $value, ?string $styleId): DOMElement
    {
        $cell = $dom->createElementNS(self::NS_MAIN, 'c');
        $cell->setAttribute('r', $cellRef);
        $cell->setAttribute('t', 'inlineStr');

        if ($styleId !== null && $styleId !== '') {
            $cell->setAttribute('s', $styleId);
        }

        $inlineString = $dom->createElementNS(self::NS_MAIN, 'is');
        $textNode = $dom->createElementNS(self::NS_MAIN, 't');
        $text = $this->sanitizeCellValue($value);

        if ($text !== trim($text)) {
            $textNode->setAttribute('xml:space', 'preserve');
        }

        $textNode->nodeValue = $text;
        $inlineString->appendChild($textNode);
        $cell->appendChild($inlineString);

        return $cell;
    }

    private function extractRowStyles(DOMElement $rowNode): array
    {
        $styles = [];

        foreach (iterator_to_array($rowNode->childNodes) as $childNode) {
            if (!$childNode instanceof DOMElement || $childNode->tagName !== 'c') {
                continue;
            }

            [$columnName] = $this->splitCellReference((string) $childNode->getAttribute('r'));

            if ($columnName === '') {
                continue;
            }

            $styleId = (string) $childNode->getAttribute('s');

            if ($styleId === '') {
                continue;
            }

            $styles[$this->columnIndex($columnName)] = $styleId;
        }

        return $styles;
    }

    private function clearRowCellsInRange(DOMElement $rowNode, int $startColumn, int $endColumn): void
    {
        foreach (iterator_to_array($rowNode->childNodes) as $childNode) {
            if (!$childNode instanceof DOMElement || $childNode->tagName !== 'c') {
                continue;
            }

            [$columnName] = $this->splitCellReference((string) $childNode->getAttribute('r'));

            if ($columnName === '') {
                continue;
            }

            $columnIndex = $this->columnIndex($columnName);

            if ($columnIndex >= $startColumn && $columnIndex <= $endColumn) {
                $rowNode->removeChild($childNode);
            }
        }
    }

    private function insertCellSorted(DOMElement $rowNode, DOMElement $newCell): void
    {
        [$newColumnName] = $this->splitCellReference((string) $newCell->getAttribute('r'));
        $newColumnIndex = $newColumnName === '' ? PHP_INT_MAX : $this->columnIndex($newColumnName);

        foreach (iterator_to_array($rowNode->childNodes) as $existingCell) {
            if (!$existingCell instanceof DOMElement || $existingCell->tagName !== 'c') {
                continue;
            }

            [$existingColumnName] = $this->splitCellReference((string) $existingCell->getAttribute('r'));
            $existingColumnIndex = $existingColumnName === '' ? PHP_INT_MAX : $this->columnIndex($existingColumnName);

            if ($newColumnIndex < $existingColumnIndex) {
                $rowNode->insertBefore($newCell, $existingCell);
                return;
            }
        }

        $rowNode->appendChild($newCell);
    }

    private function readCellValue(DOMElement $cellNode, DOMXPath $xpath, array $sharedStrings): string
    {
        $type = (string) $cellNode->getAttribute('t');

        if ($type === 'inlineStr') {
            $texts = $xpath->query('.//m:is//m:t', $cellNode);
            $value = '';

            if ($texts !== false) {
                foreach ($texts as $textNode) {
                    $value .= $textNode->textContent;
                }
            }

            return $value;
        }

        $valueNode = $xpath->query('./m:v', $cellNode)->item(0);

        if (!$valueNode instanceof DOMNode) {
            return '';
        }

        $raw = (string) $valueNode->textContent;

        if ($type === 's') {
            $index = (int) $raw;
            return $sharedStrings[$index] ?? '';
        }

        return $raw;
    }

    private function splitCellReference(string $reference): array
    {
        if (!preg_match('/^([A-Z]+)(\d+)$/', strtoupper($reference), $matches)) {
            return ['', 0];
        }

        return [$matches[1], (int) $matches[2]];
    }

    private function columnIndex(string $columnName): int
    {
        $columnName = strtoupper($columnName);
        $length = strlen($columnName);
        $index = 0;

        for ($i = 0; $i < $length; $i++) {
            $index = ($index * 26) + (ord($columnName[$i]) - 64);
        }

        return $index;
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

    private function createXmlDom(string $xml): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;

        if (!$dom->loadXML($xml)) {
            throw new RuntimeException('Unable to parse template XML.');
        }

        return $dom;
    }
}
