<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\MpesaC2bCallback;
use App\Models\SuspenseAccount;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MpesaC2bCallbackTest extends TestCase
{
    use DatabaseTransactions;

    private function setupLoan(string $phone = '254712345678'): Loan
    {
        $branch = Branch::create([
            'name' => 'Main Branch',
            'code' => 'MAIN01',
        ]);

        $officer = User::factory()->create([
            'branch_id' => $branch->id,
        ]);

        $product = LoanProduct::create([
            'name' => 'Test Product',
            'code' => 'TEST01',
            'interest_method' => 'flat',
            'interest_rate' => 10,
            'min_term_weeks' => 4,
            'max_term_weeks' => 12,
            'min_amount' => 1000,
            'max_amount' => 100000,
            'processing_fee_rate' => 0,
            'insurance_fee_rate' => 0,
            'late_penalty_rate' => 0,
        ]);

        $customer = Customer::create([
            'full_name' => 'Test Customer',
            'phone_number' => $phone,
            'id_number' => '12345678',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'branch_id' => $branch->id,
            'relationship_officer_id' => $officer->id,
            'next_of_kin_name' => 'Kin',
            'next_of_kin_phone' => '254700000000',
            'next_of_kin_relationship' => 'spouse',
        ]);

        $loan = Loan::create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'relationship_officer_id' => $officer->id,
            'principal_amount' => 10000,
            'interest_amount' => 1000,
            'total_repayable' => 11000,
            'term_weeks' => 10,
            'weekly_installment' => 1100,
            'purpose' => 'business',
            'status' => 'disbursed',
            'application_date' => today(),
            'disbursement_date' => today(),
            'first_due_date' => today()->addWeek(),
            'outstanding_balance' => 10000,
            'disbursed_by' => $officer->id,
            'disbursed_at' => now(),
        ]);

        $loan->generateSchedule();

        return $loan;
    }

    public function test_c2b_confirmation_applies_repayment_when_account_number_is_phone(): void
    {
        $loan = $this->setupLoan('254712345678');
        $customer = $loan->customer;

        $payload = [
            'TransactionType' => 'Pay Bill',
            'TransID' => 'TEST123456',
            'TransTime' => now()->format('YmdHis'),
            'TransAmount' => '2000.00',
            'BusinessShortCode' => '123456',
            'BillRefNumber' => '254712345678',
            'MSISDN' => '254712345678',
            'FirstName' => 'Test',
            'LastName' => 'Customer',
        ];

        $response = $this->postJson('/mpesa/c2b/confirmation', $payload);

        $response->assertOk()
            ->assertJson(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);

        $this->assertDatabaseHas('mpesa_c2b_callbacks', [
            'transaction_id' => 'TEST123456',
            'customer_id' => $customer->id,
            'loan_id' => $loan->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('loan_repayments', [
            'loan_id' => $loan->id,
            'customer_id' => $customer->id,
            'amount' => 2000,
            'status' => 'confirmed',
        ]);

        $this->assertDatabaseHas('transactions', [
            'loan_id' => $loan->id,
            'customer_id' => $customer->id,
            'transaction_type' => 'loan_repayment',
            'source' => 'mpesa',
            'external_reference' => 'TEST123456',
        ]);

        $this->assertEquals(8100.00, (float) $loan->fresh()->outstanding_balance);
    }

    public function test_c2b_confirmation_suspends_payment_when_no_customer_found(): void
    {
        $payload = [
            'TransactionType' => 'Pay Bill',
            'TransID' => 'TEST999999',
            'TransTime' => now()->format('YmdHis'),
            'TransAmount' => '1500.00',
            'BusinessShortCode' => '123456',
            'BillRefNumber' => '254700000000',
            'MSISDN' => '254700000000',
            'FirstName' => 'Unknown',
            'LastName' => 'User',
        ];

        $response = $this->postJson('/mpesa/c2b/confirmation', $payload);

        $response->assertOk()
            ->assertJson(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);

        $this->assertDatabaseHas('mpesa_c2b_callbacks', [
            'transaction_id' => 'TEST999999',
            'status' => 'suspended',
        ]);

        $this->assertDatabaseHas('suspense_accounts', [
            'external_reference' => 'TEST999999',
            'amount' => 1500,
            'status' => 'unmatched',
        ]);
    }

    public function test_c2b_validation_always_accepts(): void
    {
        $response = $this->postJson('/mpesa/c2b/validation', [
            'BillRefNumber' => '254712345678',
            'MSISDN' => '254712345678',
        ]);

        $response->assertOk()
            ->assertJson(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    public function test_c2b_confirmation_is_idempotent(): void
    {
        $loan = $this->setupLoan('254712345678');
        $customer = $loan->customer;

        $payload = [
            'TransactionType' => 'Pay Bill',
            'TransID' => 'TESTIDEMP1',
            'TransTime' => now()->format('YmdHis'),
            'TransAmount' => '1000.00',
            'BusinessShortCode' => '123456',
            'BillRefNumber' => '254712345678',
            'MSISDN' => '254712345678',
        ];

        $this->postJson('/mpesa/c2b/confirmation', $payload)->assertOk();
        $this->postJson('/mpesa/c2b/confirmation', $payload)->assertOk();

        $this->assertEquals(1, MpesaC2bCallback::where('transaction_id', 'TESTIDEMP1')->count());
        $this->assertEquals(1, Transaction::where('external_reference', 'TESTIDEMP1')->count());
    }
}
