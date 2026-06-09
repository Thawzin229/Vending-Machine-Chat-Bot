<?php

namespace App\Ai\Tools;

use App\Models\Product;
use Laravel\Ai\Contracts\Tool;

class CheckProductAvailability implements Tool
{
    /**
     * Get the name of the tool
     */
    public function name(): string
    {
        return 'check_product_availability';
    }
    
    /**
     * Get the description of the tool
     */
    public function description(): string
    {
        return 'Check if a product (especially Coke/Coca-Cola products) is available in stock and return the available quantity from the products database';
    }
    
    /**
     * Get the parameters schema for this tool
     */
    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'product_name' => [
                    'type' => 'string',
                    'description' => 'The name of the product to check (e.g., Coke, Coca-Cola, Coke Zero, Cherry Coke, Pepsi, etc.)'
                ],
                'quantity_needed' => [
                    'type' => 'integer',
                    'description' => 'Optional: Specific quantity the user wants to check if available'
                ]
            ],
            'required' => ['product_name']
        ];
    }
    
    /**
     * Execute the tool
     */
    public function __invoke(array $parameters): array
    {
        $productName = $parameters['product_name'];
        $quantityNeeded = $parameters['quantity_needed'] ?? null;
        
        // Search for the product in products table (case-insensitive)
        $product = Product::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($productName) . '%'])
            ->first();
        
        // If product not found
        if (!$product) {
            return [
                'success' => false,
                'available' => false,
                'product_name' => $productName,
                'message' => "Sorry, '{$productName}' is not found in our inventory.",
                'quantity_available' => 0,
            ];
        }
        
        $available = $product->quantity_available > 0;
        
        // If user specified a quantity they need
        if ($quantityNeeded && $quantityNeeded > 0) {
            $hasEnough = $product->quantity_available >= $quantityNeeded;
            
            return [
                'success' => true,
                'available' => $available,
                'has_enough' => $hasEnough,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'price' => $product->price,
                'quantity_available' => $product->quantity_available,
                'quantity_needed' => $quantityNeeded,
                'message' => $hasEnough 
                    ? "Yes! We have {$product->quantity_available} units of {$product->name} available at ₱{$product->price} each. That's enough for your request of {$quantityNeeded} units."
                    : "We only have {$product->quantity_available} units of {$product->name} available, but you need {$quantityNeeded}. Not enough stock.",
                'stock_status' => $this->getStockStatus($product->quantity_available)
            ];
        }
        
        // Simple availability check
        return [
            'success' => true,
            'available' => $available,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $product->price,
            'quantity_available' => $product->quantity_available,
            'message' => $available 
                ? "Yes, {$product->name} is currently in stock! Available quantity: {$product->quantity_available} units at ₱{$product->price} each."
                : "Sorry, {$product->name} is currently out of stock.",
            'stock_status' => $this->getStockStatus($product->quantity_available),
        ];
    }
    
    /**
     * Get stock status text
     */
    private function getStockStatus(int $quantity): string
    {
        if ($quantity <= 0) {
            return 'out_of_stock';
        }
        
        if ($quantity < 10) {
            return 'low_stock';
        }
        
        return 'in_stock';
    }
}