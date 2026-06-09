<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'Coca-Cola Classic',
                'price' => 25.00,
                'quantity_available' => 150,
            ],
            [
                'name' => 'Coke Zero Sugar',
                'price' => 25.00,
                'quantity_available' => 75,
            ],
            [
                'name' => 'Cherry Coke',
                'price' => 28.00,
                'quantity_available' => 0,
            ],
            [
                'name' => 'Coke Vanilla',
                'price' => 28.00,
                'quantity_available' => 25,
            ],
            [
                'name' => 'Coca-Cola Mini (6-pack)',
                'price' => 120.00,
                'quantity_available' => 200,
            ],
            [
                'name' => 'Pepsi Cola',
                'price' => 24.00,
                'quantity_available' => 100,
            ],
            [
                'name' => 'Sprite',
                'price' => 25.00,
                'quantity_available' => 80,
            ],
        ];
        
        foreach ($products as $product) {
            Product::create($product);
        }
    }
}