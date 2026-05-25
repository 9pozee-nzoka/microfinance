<?php
// database/migrations/2026_05_24_000004_create_loans_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_number')->unique(); // LN-2026-000001
            
            // Relationships
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('product_id')->constrained('loan_products');
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('relationship_officer_id')->constrained('users');
            
            // Loan Details
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('interest_amount', 15, 2);
            $table->decimal('processing_fee', 15, 2)->default(0);
            $table->decimal('insurance_fee', 15, 2)->default(0);
            $table->decimal('total_repayable', 15, 2);
            $table->integer('term_weeks');
            $table->decimal('weekly_installment', 15, 2);
            
            // Purpose & Documentation
            $table->enum('purpose', ['business', 'education', 'medical', 'agriculture', 'home_improvement', 'other']);
            $table->text('purpose_description')->nullable();
            $table->string('collateral_description')->nullable();
            $table->string('collateral_value')->nullable();
            
            // Status Workflow
            $table->enum('status', [
                'pending',           // Submitted, awaiting review
                'under_review',      // Officer reviewing
                'partially_approved',// Partial approval
                'approved',          // Fully approved, awaiting disbursement
                'rejected',          // Rejected
                'disbursed',         // Money sent to customer
                'active',            // Being repaid
                'completed',         // Fully repaid
                'defaulted',         // >90 days overdue
                'written_off',       // Bad debt written off
                'restructured'       // Terms modified
            ])->default('pending');
            
            // Approval Chain
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('disbursed_by')->nullable()->constrained('users');
            $table->timestamp('disbursed_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Disbursement
            $table->enum('disbursement_method', ['mpesa', 'bank_transfer', 'cash', 'internal'])->nullable();
            $table->string('disbursement_reference')->nullable();
            $table->string('mpesa_receipt_number')->nullable();
            
            // Performance Tracking
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->decimal('total_paid_principal', 15, 2)->default(0);
            $table->decimal('total_paid_interest', 15, 2)->default(0);
            $table->decimal('outstanding_balance', 15, 2);
            $table->decimal('arrears_amount', 15, 2)->default(0);
            $table->integer('days_in_arrears')->default(0);
            $table->enum('risk_category', ['low', 'medium', 'high', 'watch', 'default'])->default('low');
            
            // Dates
            $table->date('application_date');
            $table->date('disbursement_date')->nullable();
            $table->date('first_due_date')->nullable();
            $table->date('maturity_date')->nullable();
            $table->date('last_payment_date')->nullable();
            $table->date('next_due_date')->nullable();
            
            // Restructuring
            $table->boolean('is_restructured')->default(false);
            $table->foreignId('original_loan_id')->nullable()->constrained('loans');
            $table->text('restructure_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['status', 'branch_id']);
            $table->index(['customer_id', 'status']);
            $table->index('loan_number');
            $table->index('disbursement_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};