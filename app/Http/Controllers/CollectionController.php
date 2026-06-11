<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessSmsScheduleJob;
use App\Jobs\SendSmsJob;
use App\Models\Branch;
use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\LoanRepayment;
use App\Models\SmsLog;
use App\Models\SmsSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CollectionController extends Controller
{
    // ══════════════════════════════════════════════════════════════
    // COLLECTION DASHBOARD
    // ══════════════════════════════════════════════════════════════

    public function index()
    {
        $user        = auth()->user();
        $isOfficer   = $user->hasRole('loan_officer') && !$user->hasAnyRole(['admin', 'super_admin', 'branch_manager']);

        $baseQuery = fn() => Loan::with(['customer', 'product', 'branch'])
            ->whereIn('status', ['disbursed', 'active'])
            ->when($isOfficer, fn($q) => $q->where('relationship_officer_id', $user->id));

        // Loans due today
        $dueToday = $baseQuery()
            ->whereDate('next_due_date', today())
            ->orderBy('next_due_date')
            ->get();

        // Top overdue loans
        $overdueLoans = $baseQuery()
            ->where('days_in_arrears', '>', 0)
            ->orderByDesc('days_in_arrears')
            ->limit(10)
            ->get();

        // Stats — scope to officer if needed
        $statBase = Loan::whereIn('status', ['disbursed', 'active'])
            ->when($isOfficer, fn($q) => $q->where('relationship_officer_id', $user->id));

        $totalDueToday   = $dueToday->sum('weekly_installment');
        $totalOverdue    = (clone $statBase)->where('days_in_arrears', '>', 0)->count();
        $totalArrearsAmt = (clone $statBase)->sum('arrears_amount');
        $smsSentToday    = SmsLog::whereDate('sent_at', today())->where('status', 'sent')
            ->when($isOfficer, fn($q) => $q->where('created_by', $user->id))
            ->count();
        $schedulesActive = SmsSchedule::where('status', 'active')->count();

        // PAR buckets
        $par30  = (clone $statBase)->whereBetween('days_in_arrears', [1, 30])->count();
        $par60  = (clone $statBase)->whereBetween('days_in_arrears', [31, 60])->count();
        $par90  = (clone $statBase)->whereBetween('days_in_arrears', [61, 90])->count();
        $par90p = (clone $statBase)->where('days_in_arrears', '>', 90)->count();

        // Recent SMS activity
        $recentSms = SmsLog::with('customer')
            ->when($isOfficer, fn($q) => $q->where('created_by', $user->id))
            ->latest()->limit(8)->get();

        // Pending payments awaiting confirmation
        $pendingPayments = LoanRepayment::with(['loan.customer', 'customer'])
            ->where('status', 'pending')
            ->when($isOfficer, fn($q) => $q->whereHas('loan', fn($lq) => $lq->where('relationship_officer_id', $user->id)))
            ->latest()
            ->limit(10)
            ->get();

        $pendingCount = LoanRepayment::where('status', 'pending')
            ->when($isOfficer, fn($q) => $q->whereHas('loan', fn($lq) => $lq->where('relationship_officer_id', $user->id)))
            ->count();

        $pendingTotal = LoanRepayment::where('status', 'pending')
            ->when($isOfficer, fn($q) => $q->whereHas('loan', fn($lq) => $lq->where('relationship_officer_id', $user->id)))
            ->sum('amount');

        return view('loans.collection.index', compact(
            'dueToday', 'overdueLoans',
            'totalDueToday', 'totalOverdue', 'totalArrearsAmt',
            'smsSentToday', 'schedulesActive',
            'par30', 'par60', 'par90', 'par90p',
            'recentSms',
            'pendingPayments', 'pendingCount', 'pendingTotal'
        ));
    }

    // ══════════════════════════════════════════════════════════════
    // OVERDUE LOANS LIST
    // ══════════════════════════════════════════════════════════════

    public function overdue(Request $request)
    {
        $user      = auth()->user();
        $isOfficer = $user->hasRole('loan_officer') && !$user->hasAnyRole(['admin', 'super_admin', 'branch_manager']);

        $query = Loan::with(['customer', 'product', 'branch', 'relationshipOfficer'])
            ->whereIn('status', ['disbursed', 'active'])
            ->where('days_in_arrears', '>', 0)
            ->when($isOfficer, fn($q) => $q->where('relationship_officer_id', $user->id));

        if ($request->filled('branch'))    $query->where('branch_id', $request->branch);
        if ($request->filled('product'))   $query->where('product_id', $request->product);
        if ($request->filled('min_days'))  $query->where('days_in_arrears', '>=', $request->min_days);
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('loan_number', 'like', "%{$s}%")
                  ->orWhereHas('customer', fn($c) => $c->where('full_name', 'like', "%{$s}%")
                      ->orWhere('phone_number', 'like', "%{$s}%"));
            });
        }

        $loans    = $query->orderByDesc('days_in_arrears')->paginate(25)->withQueryString();
        $branches = Branch::where('status', 'active')->orderBy('name')->get();
        $products = LoanProduct::where('status', 'active')->orderBy('name')->get();

        $totalArrears = Loan::whereIn('status', ['disbursed', 'active'])
            ->where('days_in_arrears', '>', 0)
            ->when($isOfficer, fn($q) => $q->where('relationship_officer_id', $user->id))
            ->sum('arrears_amount');

        return view('loans.collection.overdue', compact('loans', 'branches', 'products', 'totalArrears'));
    }

    // ══════════════════════════════════════════════════════════════
    // SMS LOGS
    // ══════════════════════════════════════════════════════════════

    public function smsLogs(Request $request)
    {
        $query = SmsLog::with(['customer', 'loan', 'createdBy']);

        if ($request->filled('status'))       $query->where('status', $request->status);
        if ($request->filled('message_type')) $query->where('message_type', $request->message_type);
        if ($request->filled('date_from'))    $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))      $query->whereDate('created_at', '<=', $request->date_to);
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('phone_number', 'like', "%{$s}%")
                  ->orWhere('at_message_id', 'like', "%{$s}%")
                  ->orWhereHas('customer', fn($c) => $c->where('full_name', 'like', "%{$s}%"));
            });
        }

        $logs = $query->latest()->paginate(25)->withQueryString();

        // Stats
        $sentCount        = SmsLog::where('status', 'sent')->count();
        $failedCount      = SmsLog::where('status', 'failed')->count();
        $pendingCount     = SmsLog::where('status', 'pending')->count();
        $blacklistedCount = SmsLog::where('status', 'blacklisted')->count();
        $totalCost        = SmsLog::where('status', 'sent')->sum('at_cost');

        return view('loans.collection.sms-logs', compact(
            'logs', 'sentCount', 'failedCount', 'pendingCount', 'blacklistedCount', 'totalCost'
        ));
    }

    // ══════════════════════════════════════════════════════════════
    // SEND MANUAL SMS
    // ══════════════════════════════════════════════════════════════

    public function sendSms(Request $request)
    {
        $request->validate([
            'recipient_type'  => 'required|in:loan,custom',
            'loan_id'         => 'required_if:recipient_type,loan|nullable|exists:loans,id',
            'phone_number'    => 'required_if:recipient_type,custom|nullable|string',
            'message'         => 'required|string|min:5|max:459',
            'message_type'    => 'required|in:payment_reminder,overdue_notice,payment_received,loan_approved,loan_disbursed,custom',
            'scheduled_at'    => 'nullable|date|after:now',
        ]);

        if ($request->recipient_type === 'loan') {
            $loan  = Loan::with('customer')->findOrFail($request->loan_id);
            $phone = $loan->customer->phone_number;
            $customerId = $loan->customer_id;
            $loanId     = $loan->id;
        } else {
            $phone      = $request->phone_number;
            $customerId = null;
            $loanId     = null;
        }

        $log = SmsLog::create([
            'customer_id'  => $customerId,
            'loan_id'      => $loanId,
            'phone_number' => $phone,
            'message'      => $request->message,
            'message_type' => $request->message_type,
            'status'       => 'pending',
            'scheduled_at' => $request->scheduled_at,
            'created_by'   => auth()->id(),
        ]);

        // Dispatch immediately or schedule
        if ($request->scheduled_at) {
            SendSmsJob::dispatch($log)->delay(Carbon::parse($request->scheduled_at));
        } else {
            SendSmsJob::dispatch($log);
        }

        return back()->with('success', 'SMS queued successfully.');
    }

    // ══════════════════════════════════════════════════════════════
    // BULK SMS — send to all overdue / due today
    // ══════════════════════════════════════════════════════════════

    public function sendBulkSms(Request $request)
    {
        $request->validate([
            'target'       => 'required|in:overdue,due_today,all_active,par30,par60,par90plus',
            'message'      => 'required|string|min:5|max:459',
            'message_type' => 'required|in:payment_reminder,overdue_notice,custom',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $query = $this->resolveTargetQuery($request->target);
        $totalLoans = $query->count();

        if ($totalLoans === 0) {
            return back()->with('error', 'No loans matched the selected target.');
        }

        $batchId = Str::uuid()->toString();
        $count   = 0;

        // Process in chunks to avoid memory and connection issues
        $query->chunk(100, function ($loans) use ($request, $batchId, &$count) {
            foreach ($loans as $loan) {
                if (!$loan->customer?->phone_number) continue;

                $log = SmsLog::create([
                    'customer_id'   => $loan->customer_id,
                    'loan_id'       => $loan->id,
                    'phone_number'  => $loan->customer->phone_number,
                    'message'       => $request->message,
                    'message_type'  => $request->message_type,
                    'status'        => 'pending',
                    'scheduled_at'  => $request->scheduled_at,
                    'is_bulk'       => true,
                    'bulk_batch_id' => $batchId,
                    'created_by'    => auth()->id(),
                ]);

                if ($request->scheduled_at) {
                    SendSmsJob::dispatch($log)->delay(Carbon::parse($request->scheduled_at));
                } else {
                    SendSmsJob::dispatch($log);
                }

                $count++;
            }
        });

        return back()->with('success', "{$count} SMS messages queued (Batch: {$batchId}).");
    }

    // ══════════════════════════════════════════════════════════════
    // CANCEL SMS
    // ══════════════════════════════════════════════════════════════

    public function cancelSms(SmsLog $smsLog)
    {
        if ($smsLog->status !== 'pending') {
            return response()->json(['error' => 'Only pending messages can be cancelled.'], 422);
        }
        $smsLog->update(['status' => 'cancelled']);
        return response()->json(['success' => true]);
    }

    // ══════════════════════════════════════════════════════════════
    // SMS SCHEDULES — CRUD
    // ══════════════════════════════════════════════════════════════

    public function schedules()
    {
        $schedules = SmsSchedule::with(['targetProduct', 'targetBranch', 'createdBy'])
            ->orderByDesc('created_at')
            ->get();

        $products = LoanProduct::where('status', 'active')->orderBy('name')->get();
        $branches = Branch::where('status', 'active')->orderBy('name')->get();

        return view('loans.collection.schedules', compact('schedules', 'products', 'branches'));
    }

    public function storeSchedule(Request $request)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string|max:500',
            'trigger_type'      => 'required|in:days_before_due,days_after_due,on_due_date,manual',
            'trigger_days'      => 'required_unless:trigger_type,on_due_date,manual|integer|min:0|max:365',
            'target'            => 'required|in:all_active,overdue,due_today,specific_product,specific_branch',
            'target_product_id' => 'required_if:target,specific_product|nullable|exists:loan_products,id',
            'target_branch_id'  => 'required_if:target,specific_branch|nullable|exists:branches,id',
            'message_template'  => 'required|string|min:10|max:459',
            'status'            => 'required|in:active,paused,draft',
        ]);

        SmsSchedule::create(array_merge($validated, ['created_by' => auth()->id()]));

        return back()->with('success', 'SMS schedule created successfully.');
    }

    public function updateSchedule(Request $request, SmsSchedule $schedule)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string|max:500',
            'trigger_type'      => 'required|in:days_before_due,days_after_due,on_due_date,manual',
            'trigger_days'      => 'nullable|integer|min:0|max:365',
            'target'            => 'required|in:all_active,overdue,due_today,specific_product,specific_branch',
            'target_product_id' => 'nullable|exists:loan_products,id',
            'target_branch_id'  => 'nullable|exists:branches,id',
            'message_template'  => 'required|string|min:10|max:459',
            'status'            => 'required|in:active,paused,draft',
        ]);

        $schedule->update($validated);

        return back()->with('success', 'Schedule updated.');
    }

    public function destroySchedule(SmsSchedule $schedule)
    {
        $schedule->delete();
        return back()->with('success', 'Schedule deleted.');
    }

    public function runSchedule(SmsSchedule $schedule)
    {
        if ($schedule->status === 'paused') {
            return back()->with('error', 'Schedule is paused. Activate it first.');
        }

        ProcessSmsScheduleJob::dispatch($schedule);

        return back()->with('success', "Schedule \"{$schedule->name}\" is running in the background.");
    }

    public function toggleSchedule(SmsSchedule $schedule)
    {
        $schedule->update([
            'status' => $schedule->status === 'active' ? 'paused' : 'active',
        ]);
        return back()->with('success', "Schedule " . ($schedule->status === 'active' ? 'activated' : 'paused') . ".");
    }

    // ══════════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════════

    private function resolveTargetQuery(string $target)
    {
        $query = Loan::with('customer')->whereIn('status', ['disbursed', 'active']);

        return match($target) {
            'overdue'    => $query->where('days_in_arrears', '>', 0),
            'due_today'  => $query->whereDate('next_due_date', today()),
            'par30'      => $query->whereBetween('days_in_arrears', [1, 30]),
            'par60'      => $query->whereBetween('days_in_arrears', [31, 60]),
            'par90plus'  => $query->where('days_in_arrears', '>', 90),
            default      => $query,
        };
    }
}
