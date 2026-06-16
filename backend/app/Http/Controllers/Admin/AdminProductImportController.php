<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenSpout\Reader\XLSX\Reader;
use Throwable;

class AdminProductImportController extends Controller
{
    /**
     * Columns the uploaded sheet MUST contain (row 1 = header).
     * `image` also accepts the legacy `image_url` header.
     */
    private const REQUIRED_COLUMNS = ['name', 'category', 'description', 'image', 'price'];

    /** Accepted header aliases, keyed by canonical column name. */
    private const COLUMN_ALIASES = [
        'image' => ['image', 'image_url'],
    ];

    /**
     * POST /api/v1/admin/products/import
     *
     * Accepts an .xlsx file and bulk-inserts valid product rows.
     * Required columns (row 1 = header):
     *   name | category | description | image | price
     * Optional columns:
     *   stock | compare_price | sku
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
            [$headers, $rows, $errors] = $this->parseXlsx($path);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Failed to parse the uploaded file: ' . $e->getMessage(),
            ], 422);
        }

        // ── Header-level validation ──────────────────────────────────────────
        // Reject the whole sheet up-front if a required column is missing, so the
        // admin gets one clear message instead of an error on every row.
        $missing = $this->missingRequiredColumns($headers ?? []);
        if (! empty($missing)) {
            return response()->json([
                'message' => 'The uploaded sheet is missing required column(s): '
                    . implode(', ', $missing)
                    . '. The sheet must contain these columns: '
                    . implode(', ', self::REQUIRED_COLUMNS) . '.',
            ], 422);
        }

        $inserted = 0;
        $failed   = $errors; // pre-populated with parse-level errors

        foreach ($rows as $rowIndex => $row) {
            $reasons = $this->validateRow($row);

            if (! empty($reasons)) {
                $failed[] = ['row' => $rowIndex + 2, 'reasons' => $reasons]; // +2: 1-based + header
                continue;
            }

            try {
                $name        = trim((string) $row['name']);
                $description = trim((string) $row['description']);
                $image       = trim((string) $this->imageValue($row));

                Product::create([
                    'category_id'    => $this->resolveCategoryId(trim((string) $row['category'])),
                    'name_en'        => $name,
                    'name_ar'        => $name,        // fallback — admin can edit later
                    'description_en' => $description,
                    'description_ar' => $description, // fallback — admin can edit later
                    'price'          => (float) $row['price'],
                    'stock'          => $this->intOrDefault($row['stock'] ?? null, 0),
                    'image'          => $image,
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
     * Parse the xlsx file and return [headers, rows, errors].
     * Rows is an array of associative arrays keyed by header names (lowercased).
     * Errors are pre-populated for entirely unreadable rows.
     *
     * @return array{0: string[]|null, 1: array<int,array<string,mixed>>, 2: array<int,array{row:int,reasons:string[]}>}
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
                $values = array_values($row->toArray());
                $rowNum++;

                // First data row = headers
                if ($rowNum === 1) {
                    $headers = array_map(
                        static fn ($value) => strtolower(trim((string) $value)),
                        $values
                    );
                    continue;
                }

                if (! $headers) {
                    $errors[] = ['row' => $rowNum, 'reasons' => ['Could not read header row.']];
                    continue;
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

        return [$headers, $rows, $errors];
    }

    /**
     * Return the list of required columns absent from the header row.
     *
     * @param  string[]  $headers
     * @return string[]
     */
    private function missingRequiredColumns(array $headers): array
    {
        $missing = [];

        foreach (self::REQUIRED_COLUMNS as $column) {
            $accepted = self::COLUMN_ALIASES[$column] ?? [$column];

            if (! array_intersect($accepted, $headers)) {
                $missing[] = $column;
            }
        }

        return $missing;
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
        if (! isset($row['name']) || trim((string) $row['name']) === '') {
            $reasons[] = 'name is required.';
        } elseif (mb_strlen(trim((string) $row['name'])) > 255) {
            $reasons[] = 'name must not exceed 255 characters.';
        }

        // category — required, non-empty string
        if (! isset($row['category']) || trim((string) $row['category']) === '') {
            $reasons[] = 'category is required.';
        } elseif (mb_strlen(trim((string) $row['category'])) > 255) {
            $reasons[] = 'category must not exceed 255 characters.';
        }

        // description — required, non-empty string
        if (! isset($row['description']) || trim((string) $row['description']) === '') {
            $reasons[] = 'description is required.';
        }

        // image — required, must be a valid URL
        $image = $this->imageValue($row);
        if ($image === null || trim((string) $image) === '') {
            $reasons[] = 'image is required.';
        } elseif (! filter_var(trim((string) $image), FILTER_VALIDATE_URL)) {
            $reasons[] = 'image must be a valid URL.';
        }

        // price — required, numeric, >= 0
        if (! isset($row['price']) || $row['price'] === null || $row['price'] === '') {
            $reasons[] = 'price is required.';
        } elseif (! is_numeric($row['price']) || (float) $row['price'] < 0) {
            $reasons[] = 'price must be a non-negative number.';
        }

        // stock — optional, but must be a non-negative integer when provided
        if (isset($row['stock']) && $row['stock'] !== null && $row['stock'] !== '') {
            if (! is_numeric($row['stock']) || (int) $row['stock'] < 0) {
                $reasons[] = 'stock must be a non-negative integer.';
            }
        }

        return $reasons;
    }

    /**
     * Read the image value, accepting either the `image` or legacy `image_url` header.
     *
     * @param  array<string,mixed>  $row
     */
    private function imageValue(array $row): mixed
    {
        return $row['image'] ?? $row['image_url'] ?? null;
    }

    /**
     * Resolve a category name to its id, creating the category if it does not
     * already exist. Matching is case-insensitive on the English name or slug.
     */
    private function resolveCategoryId(string $name): int
    {
        $category = Category::query()
            ->whereRaw('LOWER(name_en) = ?', [mb_strtolower($name)])
            ->orWhere('slug', Str::slug($name))
            ->first();

        if (! $category) {
            $category = Category::create([
                'name_en'   => $name,
                'name_ar'   => $name, // fallback — admin can edit later
                'is_active' => true,
            ]);
        }

        return $category->id;
    }

    private function intOrDefault(mixed $value, int $default): int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return $default;
        }

        return (int) $value;
    }
}
