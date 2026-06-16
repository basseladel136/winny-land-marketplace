<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // The only categories Winny Land currently carries.
        $categories = [
            ['name_en' => 'Perfumes',    'name_ar' => 'عطور',        'sort_order' => 1],
            ['name_en' => 'Plush Toys',  'name_ar' => 'دمى قطيفة',   'sort_order' => 2],
            ['name_en' => 'Stationery',  'name_ar' => 'قرطاسية',     'sort_order' => 3],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['name_en' => $cat['name_en']],
                array_merge($cat, ['is_active' => true])
            );
        }

        // Prune any category that is no longer offered. Products that pointed at
        // a removed category have their category_id set to null via the FK's
        // nullOnDelete rule, so no products are deleted.
        $keep = array_column($categories, 'name_en');
        Category::whereNotIn('name_en', $keep)->delete();

        $this->command->info('Categories synced: ' . implode(', ', $keep) . '.');
    }
}
