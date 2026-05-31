<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenSpout\Reader\XLSX\Reader;
use Throwable;

class AdminProductImportController extends Controller
{
    /**
     * POST /api/v1/admin/products/import
     *
     * Accepts an .xlsx file and bulk-inserts valid product rows.
     * Expected columns (row 1 = header):
     *   name | description | price | stock | image_url
     *
     * Returns a summary:
     *   { inserted: int, failed: [{ row: int, reasons: string[] }] }
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'], // 5 MB limit
        ]);

        $path = $request->file('file')->getRealPath();

        try {
            [$rows, $errors] = $this->parseXlsx($path);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Failed to parse the uploaded file: ' . $e->getMessage(),
            ], 422);
        }

        $inserted = 0;
        $failed   = $errors; // pre-populated with parse-level errors

        foreach ($rows as $rowIndex => $row) {
            $reasons = $this->validateRow($row);

            if (!empty($reasons)) {
                $failed[] = ['row' => $rowIndex + 2, 'reasons' => $reasons]; // +2: 1-based + header
                continue;
            }

            try {
                $name = trim($row['name']);

                Product::create([
                    'name_en'        => $name,
                    'name_ar'        => $name,                            // fallback — admin can edit later
                    'description_en' => isset($row['description']) ? trim($row['description']) : null,
                    'description_ar' => null,
                    'price'          => (float) $row['price'],
                    'stock'          => (int)   $row['stock'],
                    'image'          => isset($row['image_url']) && $row['image_url'] !== '' ? trim($row['image_url']) : null,
                    'is_active'      => true,
                    'is_featured'    => false,
                ]);

                $inserted++;
            } catch (Throwable $e) {
                $failed[] = [
                    'row'     => $rowIndex + 2,
                    'reasons' => ['DB error: ' . $e->getMessage()],
                ];
            }
        }

        return response()->json([
            'inserted' => $inserted,
            'failed'   => $failed,
            'message'  => "Import complete. {$inserted} product(s) inserted, " . count($failed) . " row(s) skipped.",
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Parse the xlsx file and return [rows, errors].
     * Rows is an array of associative arrays keyed by header names (lowercased).
     * Errors are pre-populated for entirely unreadable rows.
     *
     * @return array{0: array<int,array<string,mixed>>, 1: array<int,array{row:int,reasons:string[]}>}
     */
    private function parseXlsx(string $path): array
    {
        $reader = new Reader();
        $reader->open($path);

        $headers = null;
        $rows    = [];
        $errors  = [];
        $rowNum  = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->getCells();
                $rowNum++;

                // First data row = headers
                if ($rowNum === 1) {
                    $headers = [];
                    foreach ($cells as $cell) {
                        $headers[] = strtolower(trim((string) $cell->getValue()));
                    }
                    continue;
                }

                if (!$headers) {
                    $errors[] = ['row' => $rowNum, 'reasons' => ['Could not read header row.']];
                    continue;
                }

                $values = [];
                foreach ($cells as $cell) {
                    $values[] = $cell->getValue();
                }

                // Pad or trim to match header count
                while (count($values) < count($headers)) {
                    $values[] = null;
                }

                $assoc = array_combine($headers, array_slice($values, 0, count($headers)));
                $rows[] = $assoc;
            }

            // Only process the first sheet
            break;
        }

        $reader->close();

        return [$rows, $errors];
    }

    /**
     * Validate a single row. Returns an array of human-readable error strings.
     *
     * @param  array<string,mixed>  $row
     * @return string[]
     */
    private function validateRow(array $row): array
    {
        $reasons = [];

        // name — required, non-empty string
        if (!isset($row['name']) || trim((string) $row['name']) === '') {
            $reasons[] = 'name is required.';
        } elseif (mb_strlen(trim((string) $row['name'])) > 255) {
            $reasons[] = 'name must not exceed 255 characters.';
        }

        // price — required, numeric, >= 0
        if (!isset($row['price']) || $row['price'] === null || $row['price'] === '') {
            $reasons[] = 'price is required.';
        } elseif (!is_numeric($row['price']) || (float) $row['price'] < 0) {
            $reasons[] = 'price must be a non-negative number.';
        }

        // stock — required, integer, >= 0
        if (!isset($row['stock']) || $row['stock'] === null || $row['stock'] === '') {
            $reasons[] = 'stock is required.';
        } elseif (!is_numeric($row['stock']) || (int) $row['stock'] < 0) {
            $reasons[] = 'stock must be a non-negative integer.';
        }

        // image_url — optional but must be a valid URL if provided
        if (isset($row['image_url']) && $row['image_url'] !== null && trim((string) $row['image_url']) !== '') {
            if (!filter_var(trim((string) $row['image_url']), FILTER_VALIDATE_URL)) {
                $reasons[] = 'image_url must be a valid URL.';
            }
        }

        return $reasons;
    }
}
