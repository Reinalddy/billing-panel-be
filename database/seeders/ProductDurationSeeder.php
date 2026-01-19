<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductDuration;

class ProductDurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();

        foreach ($products as $product) {
            // 1 bulan
            ProductDuration::create([
                'product_id'       => $product->id,
                'duration_month'   => 1,
                'price_per_month'  => $product->price,
                'is_active'        => true,
            ]);

            // 3 bulan (diskon dikit)
            ProductDuration::create([
                'product_id'       => $product->id,
                'duration_month'   => 3,
                'price_per_month'  => $product->price * 0.95,
                'is_active'        => true,
            ]);

            // 6 bulan (diskon lebih besar)
            ProductDuration::create([
                'product_id'       => $product->id,
                'duration_month'   => 6,
                'price_per_month'  => $product->price * 0.90,
                'is_active'        => true,
            ]);

            // 12 bulan (best deal)
            ProductDuration::create([
                'product_id'       => $product->id,
                'duration_month'   => 12,
                'price_per_month'  => $product->price * 0.85,
                'is_active'        => true,
            ]);
        }
    }
}
