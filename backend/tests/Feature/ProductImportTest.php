<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * Build a temporary .xlsx file from an array of rows and return an
     * UploadedFile pointing at it.
     *
     * @param  array<int,array<int,mixed>>  $rows
     */
    private function xlsx(array $rows): UploadedFile
    {
        $path   = tempnam(sys_get_temp_dir(), 'import_') . '.xlsx';
        $writer = new Writer();
        $writer->openToFile($path);
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return new UploadedFile(
            $path,
            'products.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true // test mode — skip the "uploaded via HTTP" check
        );
    }

    public function test_import_succeeds_with_all_required_columns(): void
    {
        $file = $this->xlsx([
            ['name', 'category', 'description', 'image', 'price'],
            ['Plush Bear', 'Toys', 'A soft cuddly bear', 'https://example.com/bear.jpg', 149.99],
        ]);

        $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/products/import', ['file' => $file])
            ->assertOk()
            ->assertJsonPath('inserted', 1)
            ->assertJsonPath('failed', []);

        $product = Product::where('name_en', 'Plush Bear')->firstOrFail();
        $this->assertSame('A soft cuddly bear', $product->description_en);
        $this->assertSame('https://example.com/bear.jpg', $product->image);
        $this->assertNotNull($product->category_id);
        // Category is auto-created from the sheet value.
        $this->assertDatabaseHas('categories', ['name_en' => 'Toys']);
    }

    public function test_import_resolves_existing_category_without_duplicating(): void
    {
        $category = Category::create(['name_en' => 'Toys', 'name_ar' => 'Toys', 'is_active' => true]);

        $file = $this->xlsx([
            ['name', 'category', 'description', 'image', 'price'],
            ['Plush Bear', 'toys', 'A soft cuddly bear', 'https://example.com/bear.jpg', 149.99],
        ]);

        $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/products/import', ['file' => $file])
            ->assertOk()
            ->assertJsonPath('inserted', 1);

        $this->assertSame(1, Category::where('name_en', 'Toys')->count());
        $this->assertSame($category->id, Product::where('name_en', 'Plush Bear')->first()->category_id);
    }

    public function test_import_rejects_sheet_missing_a_required_column(): void
    {
        // No "category" column.
        $file = $this->xlsx([
            ['name', 'description', 'image', 'price'],
            ['Plush Bear', 'A soft cuddly bear', 'https://example.com/bear.jpg', 149.99],
        ]);

        $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/products/import', ['file' => $file])
            ->assertStatus(422)
            ->assertJson(fn ($json) => $json->where(
                'message',
                fn ($m) => str_contains($m, 'category') && str_contains($m, 'must contain')
            ));

        $this->assertSame(0, Product::count());
    }

    public function test_import_skips_rows_missing_required_values(): void
    {
        $file = $this->xlsx([
            ['name', 'category', 'description', 'image', 'price'],
            ['Good Product', 'Toys', 'Nice', 'https://example.com/a.jpg', 10],
            ['No Image', 'Toys', 'Missing image', '', 20],          // image missing
            ['No Desc', 'Toys', '', 'https://example.com/c.jpg', 30], // description missing
        ]);

        $response = $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/products/import', ['file' => $file])
            ->assertOk()
            ->assertJsonPath('inserted', 1);

        $failed = $response->json('failed');
        $this->assertCount(2, $failed);
    }

    public function test_stock_defaults_to_zero_when_omitted(): void
    {
        $file = $this->xlsx([
            ['name', 'category', 'description', 'image', 'price'],
            ['Plush Bear', 'Toys', 'A soft cuddly bear', 'https://example.com/bear.jpg', 149.99],
        ]);

        $this->actingAs($this->admin())
            ->postJson('/api/v1/admin/products/import', ['file' => $file])
            ->assertOk();

        $this->assertSame(0, Product::where('name_en', 'Plush Bear')->first()->stock);
    }
}
