<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name_en' => 'Electronics',    'name_ar' => 'إلكترونيات',     'sort_order' => 1],
            ['name_en' => 'Clothing',        'name_ar' => 'ملابس',           'sort_order' => 2],
            ['name_en' => 'Home & Garden',   'name_ar' => 'المنزل والحديقة', 'sort_order' => 3],
            ['name_en' => 'Sports',          'name_ar' => 'رياضة',           'sort_order' => 4],
            ['name_en' => 'Books',           'name_ar' => 'كتب',             'sort_order' => 5],
            ['name_en' => 'Toys & Games',    'name_ar' => 'ألعاب',           'sort_order' => 6],
            ['name_en' => 'Beauty',          'name_ar' => 'جمال',            'sort_order' => 7],
            ['name_en' => 'Food & Beverages','name_ar' => 'طعام ومشروبات',   'sort_order' => 8],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['name_en' => $cat['name_en']],
                array_merge($cat, ['is_active' => true])
            );
        }

        $this->command->info('Categories seeded successfully.');
    }
}
