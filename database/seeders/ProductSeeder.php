<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductGroup;
use App\Models\Uom;
use App\Models\PackingType;

class ProductSeeder extends Seeder
{
    public function run()
    {
        // Get existing categories, groups, uoms, and packing types
        $categories = ProductCategory::all();
        $groups = ProductGroup::all();
        $uoms = Uom::all();
        $packingTypes = PackingType::all();

        // If no data exists, create some basic ones
        if ($categories->isEmpty()) {
            $categories = collect([
                ProductCategory::create(['name' => 'Food Items', 'status' => 1]),
                ProductCategory::create(['name' => 'Beverages', 'status' => 1]),
                ProductCategory::create(['name' => 'Electronics', 'status' => 1]),
                ProductCategory::create(['name' => 'Household', 'status' => 1]),
            ]);
        }

        if ($groups->isEmpty()) {
            $groups = collect([
                ProductGroup::create(['name' => 'Group A', 'status' => 1]),
                ProductGroup::create(['name' => 'Group B', 'status' => 1]),
                ProductGroup::create(['name' => 'Group C', 'status' => 1]),
            ]);
        }

        if ($uoms->isEmpty()) {
            $uoms = collect([
                Uom::create(['name' => 'Kilogram', 'status' => 1]),
                Uom::create(['name' => 'Piece', 'status' => 1]),
                Uom::create(['name' => 'Box', 'status' => 1]),
            ]);
        }

        if ($packingTypes->isEmpty()) {
            $packingTypes = collect([
                PackingType::create(['name' => 'Carton', 'status' => 1]),
                PackingType::create(['name' => 'Bag', 'status' => 1]),
                PackingType::create(['name' => 'Bottle', 'status' => 1]),
            ]);
        }

        // Sample products data
        $products = [
            ['code' => 'RICE001', 'name' => 'Basmati Rice Premium', 'pack_size' => 25],
            ['code' => 'RICE002', 'name' => 'Basmati Rice Super', 'pack_size' => 50],
            ['code' => 'WHEAT001', 'name' => 'Wheat Flour', 'pack_size' => 10],
            ['code' => 'SUGAR001', 'name' => 'White Sugar', 'pack_size' => 50],
            ['code' => 'SALT001', 'name' => 'Iodized Salt', 'pack_size' => 1],
            ['code' => 'OIL001', 'name' => 'Cooking Oil', 'pack_size' => 5],
            ['code' => 'TEA001', 'name' => 'Green Tea', 'pack_size' => 100],
            ['code' => 'TEA002', 'name' => 'Black Tea', 'pack_size' => 200],
            ['code' => 'MILK001', 'name' => 'Full Cream Milk', 'pack_size' => 1],
            ['code' => 'MILK002', 'name' => 'Skimmed Milk', 'pack_size' => 1],
            ['code' => 'JUICE001', 'name' => 'Orange Juice', 'pack_size' => 1],
            ['code' => 'JUICE002', 'name' => 'Mango Juice', 'pack_size' => 1],
            ['code' => 'WATER001', 'name' => 'Mineral Water', 'pack_size' => 1],
            ['code' => 'SODA001', 'name' => 'Cola Drink', 'pack_size' => 1],
            ['code' => 'SOAP001', 'name' => 'Laundry Soap', 'pack_size' => 1],
            ['code' => 'SOAP002', 'name' => 'Hand Wash', 'pack_size' => 500],
            ['code' => 'CLEAN001', 'name' => 'Floor Cleaner', 'pack_size' => 1],
            ['code' => 'CLEAN002', 'name' => 'Glass Cleaner', 'pack_size' => 500],
            ['code' => 'PASTA001', 'name' => 'Spaghetti Pasta', 'pack_size' => 500],
            ['code' => 'PASTA002', 'name' => 'Macaroni Pasta', 'pack_size' => 500],
            ['code' => 'SAUCE001', 'name' => 'Tomato Ketchup', 'pack_size' => 1],
            ['code' => 'SAUCE002', 'name' => 'Chili Sauce', 'pack_size' => 500],
            ['code' => 'SPICE001', 'name' => 'Red Chili Powder', 'pack_size' => 100],
            ['code' => 'SPICE002', 'name' => 'Turmeric Powder', 'pack_size' => 100],
            ['code' => 'SPICE003', 'name' => 'Cumin Seeds', 'pack_size' => 50],
            ['code' => 'BISCUIT01', 'name' => 'Chocolate Biscuits', 'pack_size' => 100],
            ['code' => 'BISCUIT02', 'name' => 'Cream Biscuits', 'pack_size' => 100],
            ['code' => 'CHIPS001', 'name' => 'Potato Chips', 'pack_size' => 50],
            ['code' => 'CHIPS002', 'name' => 'Corn Chips', 'pack_size' => 50],
            ['code' => 'NOODLE01', 'name' => 'Instant Noodles', 'pack_size' => 75],
            ['code' => 'BREAD001', 'name' => 'White Bread', 'pack_size' => 400],
            ['code' => 'BREAD002', 'name' => 'Brown Bread', 'pack_size' => 400],
            ['code' => 'BUTTER01', 'name' => 'Salted Butter', 'pack_size' => 200],
            ['code' => 'CHEESE01', 'name' => 'Cheddar Cheese', 'pack_size' => 200],
            ['code' => 'YOGURT01', 'name' => 'Plain Yogurt', 'pack_size' => 500],
            ['code' => 'HONEY001', 'name' => 'Pure Honey', 'pack_size' => 500],
            ['code' => 'JAM001', 'name' => 'Strawberry Jam', 'pack_size' => 500],
            ['code' => 'JAM002', 'name' => 'Mixed Fruit Jam', 'pack_size' => 500],
            ['code' => 'CEREAL01', 'name' => 'Corn Flakes', 'pack_size' => 500],
            ['code' => 'CEREAL02', 'name' => 'Oats', 'pack_size' => 500],
        ];

        foreach ($products as $index => $productData) {
            Product::create([
                'item_code' => $productData['code'],
                'name' => $productData['name'],
                'product_category_id' => $categories->random()->id,
                'product_group_id' => $groups->random()->id,
                'uom_id' => $uoms->random()->id,
                'packing_type_id' => $packingTypes->random()->id,
                'pack_size' => $productData['pack_size'],
                'status' => $index % 10 == 0 ? 0 : 1, // Every 10th product inactive
            ]);
        }
    }
}
