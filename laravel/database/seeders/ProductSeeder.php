<?php

namespace Database\Seeders;

use App\Domains\Category\Models\Category;
use App\Domains\Product\Models\Attribute;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductAttribute;
use App\Domains\Product\Models\ProductVariant;
use App\Domains\Product\Models\ProductVariantAttribute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Seed the products table with variants and attributes.
     *
     * Idempotent: uses updateOrCreate keyed on slug (products) and SKU (variants).
     */
    public function run(): void
    {
        // Resolve categories seeded by CategorySeeder
        $electronics = Category::where('slug', Str::slug('Electronics'))->first();
        $clothing    = Category::where('slug', Str::slug('Clothing'))->first();
        $homeGarden  = Category::where('slug', Str::slug('Home & Garden'))->first();
        $mobiles     = Category::where('slug', Str::slug('Mobile Phones'))->first();
        $laptops     = Category::where('slug', Str::slug('Laptops'))->first();

        // Define products
        $products = [
            [
                'slug'        => 'classic-cotton-t-shirt',
                'name'        => ['en' => 'Classic Cotton T-Shirt'],
                'description' => ['en' => 'A comfortable everyday cotton t-shirt available in multiple sizes and colours.'],
                'is_active'   => true,
                'categories'  => array_filter([$clothing?->id]),
                'attributes'  => [
                    ['name' => 'Material', 'value' => 'Cotton'],
                    ['name' => 'Care',     'value' => 'Machine wash cold'],
                ],
                'variants'    => [
                    [
                        'sku'            => 'TSHIRT-BLU-M',
                        'regular_price'  => 19.99,
                        'stock_quantity' => 50,
                        'weight'         => 0.2,
                        'is_active'      => true,
                        'attributes'     => [
                            ['name' => 'color', 'value' => 'Blue'],
                            ['name' => 'size',  'value' => 'M'],
                        ],
                    ],
                    [
                        'sku'            => 'TSHIRT-BLU-L',
                        'regular_price'  => 19.99,
                        'stock_quantity' => 40,
                        'weight'         => 0.22,
                        'is_active'      => true,
                        'attributes'     => [
                            ['name' => 'color', 'value' => 'Blue'],
                            ['name' => 'size',  'value' => 'L'],
                        ],
                    ],
                    [
                        'sku'            => 'TSHIRT-RED-M',
                        'regular_price'  => 19.99,
                        'stock_quantity' => 35,
                        'weight'         => 0.2,
                        'is_active'      => true,
                        'attributes'     => [
                            ['name' => 'color', 'value' => 'Red'],
                            ['name' => 'size',  'value' => 'M'],
                        ],
                    ],
                ],
            ],
            [
                'slug'        => 'slim-fit-jeans',
                'name'        => ['en' => 'Slim Fit Jeans'],
                'description' => ['en' => 'Modern slim fit jeans crafted from premium denim.'],
                'is_active'   => true,
                'categories'  => array_filter([$clothing?->id]),
                'attributes'  => [
                    ['name' => 'Material', 'value' => 'Denim'],
                ],
                'variants'    => [
                    [
                        'sku'            => 'JEANS-BLK-30',
                        'regular_price'  => 49.99,
                        'stock_quantity' => 20,
                        'weight'         => 0.6,
                        'is_active'      => true,
                        'attributes'     => [
                            ['name' => 'color', 'value' => 'Black'],
                            ['name' => 'size',  'value' => '30'],
                        ],
                    ],
                    [
                        'sku'            => 'JEANS-BLK-32',
                        'regular_price'  => 49.99,
                        'stock_quantity' => 25,
                        'weight'         => 0.65,
                        'is_active'      => true,
                        'attributes'     => [
                            ['name' => 'color', 'value' => 'Black'],
                            ['name' => 'size',  'value' => '32'],
                        ],
                    ],
                ],
            ],
            [
                'slug'        => 'wireless-bluetooth-headphones',
                'name'        => ['en' => 'Wireless Bluetooth Headphones'],
                'description' => ['en' => 'Over-ear noise-cancelling headphones with 30-hour battery life.'],
                'is_active'   => true,
                'categories'  => array_filter([$electronics?->id]),
                'attributes'  => [],
                'variants'    => [
                    [
                        'sku'            => 'HEADPHONES-BLK',
                        'regular_price'  => 79.99,
                        'stock_quantity' => 30,
                        'weight'         => 0.35,
                        'is_active'      => true,
                        'attributes'     => [
                            ['name' => 'color', 'value' => 'Black'],
                        ],
                    ],
                    [
                        'sku'            => 'HEADPHONES-WHT',
                        'regular_price'  => 79.99,
                        'stock_quantity' => 20,
                        'weight'         => 0.35,
                        'is_active'      => true,
                        'attributes'     => [
                            ['name' => 'color', 'value' => 'White'],
                        ],
                    ],
                ],
            ],
            [
                'slug'        => 'smartphone-x200',
                'name'        => ['en' => 'Smartphone X200'],
                'description' => ['en' => 'Latest flagship smartphone with a 6.5-inch OLED display and triple camera.'],
                'is_active'   => true,
                'categories'  => array_filter([$mobiles?->id, $electronics?->id]),
                'attributes'  => [],
                'variants'    => [
                    [
                        'sku'            => 'X200-128GB-BLK',
                        'regular_price'  => 699.99,
                        'stock_quantity' => 15,
                        'weight'         => 0.18,
                        'is_active'      => true,
                        'attributes'     => [
                            ['name' => 'storage', 'value' => '128GB'],
                            ['name' => 'color',   'value' => 'Midnight Black'],
                        ],
                    ],
                    [
                        'sku'            => 'X200-256GB-SLV',
                        'regular_price'  => 799.99,
                        'stock_quantity' => 10,
                        'weight'         => 0.18,
                        'is_active'      => true,
                        'attributes'     => [
                            ['name' => 'storage', 'value' => '256GB'],
                            ['name' => 'color',   'value' => 'Silver'],
                        ],
                    ],
                ],
            ],
            [
                'slug'        => 'garden-tool-set',
                'name'        => ['en' => 'Garden Tool Set'],
                'description' => ['en' => 'Complete 5-piece garden tool set with ergonomic handles.'],
                'is_active'   => true,
                'categories'  => array_filter([$homeGarden?->id]),
                'attributes'  => [
                    ['name' => 'Material', 'value' => 'Stainless Steel'],
                ],
                'variants'    => [
                    [
                        'sku'            => 'GARDENTOOL-5PC',
                        'regular_price'  => 34.99,
                        'stock_quantity' => 60,
                        'weight'         => 1.2,
                        'is_active'      => true,
                        'attributes'     => [
                            ['name' => 'size', 'value' => '5-piece'],
                        ],
                    ],
                    [
                        'sku'            => 'GARDENTOOL-10PC',
                        'regular_price'  => 59.99,
                        'stock_quantity' => 30,
                        'weight'         => 2.1,
                        'is_active'      => true,
                        'attributes'     => [
                            ['name' => 'size', 'value' => '10-piece'],
                        ],
                    ],
                ],
            ],
            [
                'slug'        => 'ultrabook-pro-15',
                'name'        => ['en' => 'Ultrabook Pro 15'],
                'description' => ['en' => 'Thin and light 15-inch laptop with Intel Core i7 and 16GB RAM.'],
                'is_active'   => true,
                'categories'  => array_filter([$laptops?->id, $electronics?->id]),
                'attributes'  => [
                    ['name' => 'Processor', 'value' => 'Intel Core i7'],
                ],
                'variants'    => [
                    [
                        'sku'            => 'ULTRABOOK-512-SLV',
                        'regular_price'  => 1199.99,
                        'stock_quantity' => 8,
                        'weight'         => 1.6,
                        'is_active'      => true,
                        'attributes'     => [
                            ['name' => 'storage', 'value' => '512GB SSD'],
                            ['name' => 'color',   'value' => 'Silver'],
                        ],
                    ],
                    [
                        'sku'            => 'ULTRABOOK-1TB-SPC',
                        'regular_price'  => 1399.99,
                        'stock_quantity' => 5,
                        'weight'         => 1.6,
                        'is_active'      => true,
                        'attributes'     => [
                            ['name' => 'storage', 'value' => '1TB SSD'],
                            ['name' => 'color',   'value' => 'Space Gray'],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($products as $productData) {
            // Create/update the product
            $product = Product::updateOrCreate(
                ['slug' => $productData['slug']],
                [
                    'name'        => $productData['name'],
                    'description' => $productData['description'],
                    'is_active'   => $productData['is_active'],
                ]
            );

            // Sync categories
            if (!empty($productData['categories'])) {
                $product->categories()->sync($productData['categories']);
            }

            // Create/update product-level attributes
            foreach ($productData['attributes'] as $attrData) {
                $attribute = Attribute::firstOrCreate(['name' => $attrData['name']]);

                $existingProductAttr = ProductAttribute::where('product_id', $product->id)
                    ->where('attribute_id', $attribute->id)
                    ->first();

                if ($existingProductAttr) {
                    $existingProductAttr->update(['value' => $attrData['value']]);
                } else {
                    ProductAttribute::create([
                        'product_id'   => $product->id,
                        'attribute_id' => $attribute->id,
                        'value'        => $attrData['value'],
                    ]);
                }
            }

            // Create/update variants
            foreach ($productData['variants'] as $variantData) {
                $variant = $product->variants()->updateOrCreate(
                    ['sku' => $variantData['sku']],
                    [
                        'regular_price'  => $variantData['regular_price'],
                        'stock_quantity' => $variantData['stock_quantity'],
                        'weight'         => $variantData['weight'],
                        'is_active'      => $variantData['is_active'],
                    ]
                );

                // Create variant attributes (delete & re-create for idempotency)
                $variant->attributes()->delete();

                foreach ($variantData['attributes'] as $attrData) {
                    ProductVariantAttribute::create([
                        'product_variant_id' => $variant->id,
                        'name'               => $attrData['name'],
                        'value'              => $attrData['value'],
                    ]);
                }
            }
        }
    }
}
