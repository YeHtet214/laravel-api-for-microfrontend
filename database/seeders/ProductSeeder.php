<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantAttribute;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::first();
        $adminId = $admin ? $admin->id : null;

        // 1. Categories
        $categories = [
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'description' => 'Gadgets, devices, and accessories.',
                'status' => 'active',
            ],
            [
                'name' => 'Apparel',
                'slug' => 'apparel',
                'description' => 'Clothing, shoes, and fashion accessories.',
                'status' => 'active',
            ],
            [
                'name' => 'Home & Kitchen',
                'slug' => 'home-kitchen',
                'description' => 'Essential items for your home and kitchen.',
                'status' => 'active',
            ],
        ];

        foreach ($categories as $catData) {
            $category = Category::updateOrCreate(['slug' => $catData['slug']], $catData);

            // 2. Products for each category
            if ($category->slug === 'electronics') {
                $this->createElectronics($category, $adminId);
            } elseif ($category->slug === 'apparel') {
                $this->createApparel($category, $adminId);
            }
        }
    }

    private function createElectronics($category, $adminId)
    {
        // Simple product without variants
        Product::updateOrCreate(
            ['slug' => 'smartphone-x'],
            [
                'category_id' => $category->id,
                'name' => 'Smartphone X',
                'description' => 'A powerful smartphone with a great camera.',
                'sku' => 'ELEC-SM-X',
                'base_price' => 799.00,
                'stock_quantity' => 50,
                'status' => 'active',
                'has_variants' => false,
                'created_by' => $adminId,
            ]
        );

        // Product with variants
        $laptop = Product::updateOrCreate(
            ['slug' => 'pro-laptop-14'],
            [
                'category_id' => $category->id,
                'name' => 'Pro Laptop 14',
                'description' => 'High-performance laptop for professionals.',
                'sku' => 'ELEC-LP-PRO14',
                'base_price' => 1299.00,
                'stock_quantity' => 0, // Stock managed by variants
                'status' => 'active',
                'has_variants' => true,
                'created_by' => $adminId,
            ]
        );

        $variants = [
            [
                'name' => '8GB RAM / 256GB SSD',
                'sku' => 'ELEC-LP-PRO14-8-256',
                'price' => 1299.00,
                'stock_quantity' => 10,
                'status' => 'active',
                'attributes' => [
                    ['attribute_name' => 'RAM', 'attribute_value' => '8GB'],
                    ['attribute_name' => 'Storage', 'attribute_value' => '256GB SSD'],
                ],
            ],
            [
                'name' => '16GB RAM / 512GB SSD',
                'sku' => 'ELEC-LP-PRO14-16-512',
                'price' => 1499.00,
                'stock_quantity' => 5,
                'status' => 'active',
                'attributes' => [
                    ['attribute_name' => 'RAM', 'attribute_value' => '16GB'],
                    ['attribute_name' => 'Storage', 'attribute_value' => '512GB SSD'],
                ],
            ],
        ];

        foreach ($variants as $vData) {
            $attributes = $vData['attributes'];
            unset($vData['attributes']);
            $vData['product_id'] = $laptop->id;
            
            $variant = ProductVariant::updateOrCreate(['sku' => $vData['sku']], $vData);
            
            foreach ($attributes as $attr) {
                VariantAttribute::updateOrCreate(
                    [
                        'product_variant_id' => $variant->id,
                        'attribute_name' => $attr['attribute_name']
                    ],
                    $attr
                );
            }
        }
    }

    private function createApparel($category, $adminId)
    {
        $tshirt = Product::updateOrCreate(
            ['slug' => 'classic-cotton-tshirt'],
            [
                'category_id' => $category->id,
                'name' => 'Classic Cotton T-Shirt',
                'description' => 'Comfortable 100% cotton t-shirt.',
                'sku' => 'APP-TS-CLASSIC',
                'base_price' => 19.99,
                'stock_quantity' => 0,
                'status' => 'active',
                'has_variants' => true,
                'created_by' => $adminId,
            ]
        );

        $variants = [
            ['color' => 'Red', 'size' => 'M', 'sku' => 'APP-TS-RED-M'],
            ['color' => 'Red', 'size' => 'L', 'sku' => 'APP-TS-RED-L'],
            ['color' => 'Blue', 'size' => 'M', 'sku' => 'APP-TS-BLUE-M'],
            ['color' => 'Blue', 'size' => 'L', 'sku' => 'APP-TS-BLUE-L'],
        ];

        foreach ($variants as $v) {
            $variant = ProductVariant::updateOrCreate(
                ['sku' => $v['sku']],
                [
                    'product_id' => $tshirt->id,
                    'name' => "{$v['color']} - {$v['size']}",
                    'price' => 19.99,
                    'stock_quantity' => 20,
                    'status' => 'active',
                ]
            );

            VariantAttribute::updateOrCreate(
                ['product_variant_id' => $variant->id, 'attribute_name' => 'Color'],
                ['attribute_value' => $v['color']]
            );

            VariantAttribute::updateOrCreate(
                ['product_variant_id' => $variant->id, 'attribute_name' => 'Size'],
                ['attribute_value' => $v['size']]
            );
        }
    }
}
