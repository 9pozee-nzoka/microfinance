<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    /**
     * Export data as a streamed CSV download.
     *
     * @param array  $headers
     * @param array  $rows
     * @param string $filename
     */
    public function downloadCsv(array $headers, array $rows, string $filename): StreamedResponse
    {
        $headersHttp = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return Response::stream(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 200, $headersHttp);
    }

    /**
     * Export data as an Excel (.xlsx) download.
     *
     * @param array  $headers
     * @param array  $rows
     * @param string $filename
     * @param string $sheetTitle
     */
    public function downloadExcel(array $headers, array $rows, string $filename, string $sheetTitle = 'Report'): StreamedResponse
    {
        return Response::stream(function () use ($headers, $rows, $sheetTitle) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle(substr($sheetTitle, 0, 31));

            // Header row
            $col = 1;
            foreach ($headers as $header) {
                $cell = $sheet->getCell([$col, 1]);
                $cell->setValue($header);
                $cell->getStyle()->getFont()->setBold(true);
                $cell->getStyle()->getFill()->setFillType('solid')->getStartColor()->setRGB('E3F2FD');
                $col++;
            }

            // Data rows
            $rowIndex = 2;
            foreach ($rows as $row) {
                $col = 1;
                foreach ($row as $value) {
                    $sheet->setCellValue([$col, $rowIndex], $value);
                    $col++;
                }
                $rowIndex++;
            }

            // Auto-width columns
            foreach (range(1, count($headers)) as $colIndex) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex))->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export a Blade view as a landscape PDF download.
     *
     * @param string $view
     * @param array  $data
     * @param string $filename
     */
    public function downloadPdf(string $view, array $data, string $filename)
    {
        $pdf = Pdf::loadView($view, $data)
            ->setPaper('a4', config('reports.export.pdf_orientation', 'landscape'));

        return $pdf->download($filename);
    }

    /**
     * Export data as a Word (.docx) download.
     *
     * @param array  $headers
     * @param array  $rows
     * @param string $filename
     * @param string $title
     */
    public function downloadWord(array $headers, array $rows, string $filename, string $title = 'Report'): StreamedResponse
    {
        return Response::stream(function () use ($headers, $rows, $title) {
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();

            $section->addText($title, ['bold' => true, 'size' => 16, 'color' => '26C6DA']);
            $section->addText('Generated on ' . now()->format('d/m/Y H:i:s'), ['italic' => true, 'size' => 9]);
            $section->addTextBreak(1);

            $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'CCCCCC', 'cellMargin' => 80]);

            // Header row
            $table->addRow();
            foreach ($headers as $header) {
                $table->addCell(2000, ['bgColor' => '26C6DA'])->addText($header, ['bold' => true, 'color' => 'FFFFFF', 'size' => 9]);
            }

            // Data rows
            foreach ($rows as $row) {
                $table->addRow();
                foreach ($row as $cell) {
                    $value = is_numeric($cell) && ! is_int($cell) ? number_format($cell, 2) : (string) $cell;
                    $table->addCell(2000)->addText($value, ['size' => 9]);
                }
            }

            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Resolve date range from the request. Defaults to current month.
     */
    public function dateRange(Request $request): array
    {
        $from = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::now()->startOfMonth();

        $to = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::now()->endOfDay();

        return [$from, $to];
    }

    /**
     * Normalise a string for use in a filename.
     */
    public function safeFileName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
    }
}
