<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LoanProduct;
use Illuminate\Support\Facades\DB;

class UpdateLoanProductProcessingFeesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder updates existing loan products with default processing fees
     * based on their characteristics (amount ranges, terms, etc.)
     */
    public function run(): void
    {
        // Update all loan products that don't have a processing fee set
        $products = LoanProduct::whereNull('processing_fee')
            ->orWhere('processing_fee', 0)
            ->get();

        if ($products->isEmpty()) {
            $this->command->info('No loan products need processing fee updates.');
            return;
        }

        $this->command->info("Updating {$products->count()} loan product(s) with default processing fees...");

        foreach ($products as $product) {
            // Determine processing fee based on product characteristics
            $processingFee = $this->determineProcessingFee($product);
            
            $product->update(['processing_fee' => $processingFee]);
            
            $this->command->info("✓ {$product->name}: KSH {$processingFee}");
        }

        $this->command->info('Processing fee updates completed successfully!');
    }

    /**
     * Determine appropriate processing fee based on loan product characteristics
     */
    private function determineProcessingFee(LoanProduct $product): float
    {
        // Base processing fee
        $baseFee = 500.00;

        // Adjust based on loan amount range
        $avgAmount = ($product->min_amount + $product->max_amount) / 2;

        if ($avgAmount >= 50000) {
            // Large loans: KSH 1,000 - 2,000
            $baseFee = 1000.00;
            if ($avgAmount >= 100000) {
                $baseFee = 2000.00;
            }
        } elseif ($avgAmount >= 20000) {
            // Medium loans: KSH 700 - 1,000
            $baseFee = 700.00;
            if ($avgAmount >= 30000) {
                $baseFee = 1000.00;
            }
        } elseif ($avgAmount >= 10000) {
            // Small-medium loans: KSH 500 - 700
            $baseFee = 500.00;
            if ($avgAmount >= 15000) {
                $baseFee = 700.00;
            }
        } else {
            // Micro loans: KSH 300 - 500
            $baseFee = 300.00;
            if ($avgAmount >= 5000) {
                $baseFee = 500.00;
            }
        }

        // Adjust for longer term loans (more processing required)
        if ($product->max_term_weeks > 26) { // More than 6 months
            $baseFee += 200.00;
        } elseif ($product->max_term_weeks > 12) { // More than 3 months
            $baseFee += 100.00;
        }

        return $baseFee;
    }
}
