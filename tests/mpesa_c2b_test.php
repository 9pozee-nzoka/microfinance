<?php
/**
 * M-Pesa C2B matching test script
 * Run: php tests/mpesa_c2b_test.php
 */

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\User;
use App\Models\MpesaC2bCallback;
use App\Models\SuspenseAccount;

echo "\n========================================\n";
echo "  M-PESA C2B MATCHING TEST SUITE\n";
echo "========================================\n\n";

// ── Setup: get or create test data ────────────────────────────
$customer = Customer::first();
if (!$customer) {
    echo "ERROR: No customers in DB. Please register a customer first.\n";
    exit(1);
}

$product = LoanProduct::first();
$user    = User::first();

// Create a fresh active loan with arrears for testing
Loan::whereIn('status', ['active','disbursed'])
    ->where('customer_id', $customer->id)
    ->update(['status' => 'completed', 'outstanding_balance' => 0]);

$loan = Loan::create([
    'customer_id'             => $customer->id,
    'product_id'              => $product->id,
    'branch_id'               => $customer->branch_id,
    'relationship_officer_id' => $user->id,
    'principal_amount'        => 5000,
    'interest_amount'         => 1000,
    'processing_fee'          => 700,
    'insurance_fee'           => 0,
    'total_repayable'         => 6000,
    'term_weeks'              => 4,
    'weekly_installment'      => 1500,
    'purpose'                 => 'business',
    'outstanding_balance'     => 5000,
    'arrears_amount'          => 3000,
    'days_in_arrears'         => 7,
    'application_date'        => now()->toDateString(),
    'disbursement_date'       => now()->subWeeks(3)->toDateString(),
    'first_due_date'          => now()->subWeeks(2)->toDateString(),
    'next_due_date'           => now()->toDateString(),
    'status'                  => 'active',
    'risk_category'           => 'medium',
]);

echo "Test setup:\n";
echo "  Customer : {$customer->full_name}\n";
echo "  Phone    : {$customer->phone_number}\n";
echo "  Loan     : {$loan->loan_number}\n";
echo "  Arrears  : KSH " . number_format($loan->arrears_amount, 0) . "\n";
echo "  OLB      : KSH " . number_format($loan->outstanding_balance, 0) . "\n\n";

// ── Helper ─────────────────────────────────────────────────────
$pass = 0;
$fail = 0;

function run_test(string $name, string $transId, string $accountRef, string $msisdn, float $amount, string $expectStatus): void
{
    global $pass, $fail;

    // Clean up previous test records
    MpesaC2bCallback::where('transaction_id', $transId)->delete();
    SuspenseAccount::where('external_reference', $transId)->delete();

    $payload = json_encode([
        'TransID'          => $transId,
        'TransTime'        => '20260701120000',
        'TransAmount'      => (string)$amount,
        'BusinessShortCode'=> '4325075',
        'BillRefNumber'    => $accountRef,
        'MSISDN'           => $msisdn,
        'FirstName'        => 'Test',
        'LastName'         => 'Customer',
    ]);

    $ch = curl_init('http://127.0.0.1:8000/payment/c2b/confirmation');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $body     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded     = json_decode($body, true);
    $resultCode  = $decoded['ResultCode'] ?? 'MISSING';
    $callback    = MpesaC2bCallback::where('transaction_id', $transId)->first();
    $actualStatus= $callback?->status ?? 'not_created';

    $httpOk      = $httpCode === 200;
    $resultOk    = $resultCode === '0';
    $statusOk    = $actualStatus === $expectStatus;
    $allOk       = $httpOk && $resultOk && $statusOk;

    $icon = $allOk ? '✓' : '✗';
    printf("  %s %-40s HTTP:%d ResultCode:%s DB:%s (expected:%s)\n",
        $icon, $name, $httpCode, $resultCode, $actualStatus, $expectStatus);

    if (!$allOk) {
        echo "    Raw response: $body\n";
        if ($callback) {
            echo "    Callback loan_id: {$callback->loan_id}, customer_id: {$callback->customer_id}\n";
        }
    }

    $allOk ? $pass++ : $fail++;
}

// ── TESTS ──────────────────────────────────────────────────────
echo "Running tests...\n\n";

// Format phone in different formats to test all
$phone07  = '0' . substr(ltrim($customer->phone_number, '+0'), -9);
$phone254 = '254' . substr(ltrim($customer->phone_number, '+0'), -9);

// Test 1: Account ref = phone in 07 format (most common)
run_test(
    "Phone as acct ref (07 format)",
    'C2B_T1_' . time(),
    $phone07,
    '254700000001',  // different MSISDN (paying on behalf)
    1500.0,
    'completed'
);

// Test 2: Account ref = phone in 254 format
run_test(
    "Phone as acct ref (254 format)",
    'C2B_T2_' . (time()+1),
    $phone254,
    '254700000002',
    1500.0,
    'completed'
);

// Test 3: No account ref — MSISDN fallback
run_test(
    "No acct ref — MSISDN fallback",
    'C2B_T3_' . (time()+2),
    '',              // empty account ref
    $phone254,       // payer's own phone = customer's phone
    1500.0,
    'completed'
);

// Test 4: Account ref = loan number (backward compat)
run_test(
    "Loan number as acct ref",
    'C2B_T4_' . (time()+3),
    $loan->loan_number,
    '254700000003',
    1500.0,
    'completed'
);

// Test 5: Unknown phone — should go to suspense
run_test(
    "Unknown phone → suspense",
    'C2B_T5_' . (time()+4),
    '0799999999',
    '254799999999',
    500.0,
    'suspended'
);

// ── Summary ────────────────────────────────────────────────────
echo "\n========================================\n";
echo "  Results: {$pass} passed, {$fail} failed\n";
echo "========================================\n\n";

if ($fail === 0) {
    echo "All tests passed! The C2B matching is working correctly.\n";
    echo "Customers can use their phone number as account number.\n";
} else {
    echo "Some tests failed. Check the output above.\n";
}

// ── Cleanup ────────────────────────────────────────────────────
echo "\nCleaning up test loan...\n";
$loan->delete();
MpesaC2bCallback::whereIn('transaction_id', [
    'C2B_T1', 'C2B_T2', 'C2B_T3', 'C2B_T4', 'C2B_T5',
])->delete();
echo "Done.\n\n";
