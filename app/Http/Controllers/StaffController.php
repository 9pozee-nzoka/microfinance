<?php
// app/Http/Controllers/StaffController.php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\PasswordResetLog;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\AfricasTalkingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class StaffController extends Controller
{
    // ── Staff Overview ───────────────────────────────────────────
    public function index(Request $request)
    {
        $query = User::with('branch')
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'customer');
            });

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('employee_id', 'like', "%{$s}%")
                  ->orWhere('phone_number', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('branch')) {
            $query->where('branch_id', $request->branch);
        }

        $staff = $query->latest()->paginate(20)->withQueryString();
        $branches = Branch::where('status', 'active')->orderBy('name')->get();

        // Summary stats
        $totalStaff = User::whereDoesntHave('roles', fn($q) => $q->where('name', 'customer'))->count();
        $activeStaff = User::where('status', 'active')->whereDoesntHave('roles', fn($q) => $q->where('name', 'customer'))->count();
        $inactiveStaff = User::where('status', 'inactive')->whereDoesntHave('roles', fn($q) => $q->where('name', 'customer'))->count();

        return view('staff.index', compact('staff', 'branches', 'totalStaff', 'activeStaff', 'inactiveStaff'));
    }

    // ── Create Staff Form ────────────────────────────────────────
    public function create()
    {
        $branches = Branch::where('status', 'active')->orderBy('name')->get();
        $roles = Role::where('guard_name', 'web')
            ->whereNotIn('name', ['customer', 'super_admin'])
            ->orderBy('name')
            ->get();

        return view('staff.create', compact('branches', 'roles'));
    }

    // ── Store New Staff ──────────────────────────────────────────
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|max:255|unique:users,email',
            'phone_number'=> 'required|string|max:20|unique:users,phone_number',
            'employee_id' => 'nullable|string|max:50|unique:users,employee_id',
            'designation' => 'required|string|max:100',
            'branch_id'   => 'required|exists:branches,id',
            'role'        => 'required|exists:roles,name',
            'status'      => 'required|in:active,inactive,suspended',
            'send_sms'    => 'nullable|boolean',
        ]);

        $tempPassword = Str::random(12);

        $user = User::create([
            'name'          => $validated['name'],
            'email'         => $validated['email'],
            'password'      => Hash::make($tempPassword),
            'temp_password' => $tempPassword,
            'phone_number'  => $validated['phone_number'],
            'employee_id'   => $validated['employee_id'] ?? null,
            'designation'   => $validated['designation'],
            'branch_id'     => $validated['branch_id'],
            'status'        => $validated['status'],
        ]);

        $user->assignRole($validated['role']);

        // Send credentials via SMS and/or Email
        $result = $this->sendCredentials($user, $tempPassword, !empty($validated['send_sms']), 'welcome');

        $message = "Staff {$user->name} created successfully. Temporary password: {$tempPassword}";

        if ($result['sms_sent'] && $result['email_sent']) {
            $message .= " Credentials sent via SMS and Email.";
        } elseif ($result['sms_sent']) {
            $message .= " Credentials sent via SMS.";
        } elseif ($result['email_sent']) {
            $message .= " Credentials sent via Email.";
        } else {
            $message .= " Failed to send credentials. SMS: {$result['sms_error']}. Email: {$result['email_error']}";
        }

        return redirect()->route('staff.index')
            ->with('success', $message);
    }

    // ── Reset Staff Password ─────────────────────────────────────
    public function resetPassword(Request $request, User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Use your profile settings to change your own password.');
        }

        if ($user->hasRole('super_admin')) {
            return back()->with('error', 'Cannot reset super admin password via this method.');
        }

        $newPassword = Str::random(12);

        $user->update([
            'password'      => Hash::make($newPassword),
            'temp_password' => $newPassword,
        ]);

        // Send new password via SMS and Email
        $result = $this->sendCredentials($user, $newPassword, true, 'reset');

        // Log the reset
        PasswordResetLog::create([
            'user_id'      => $user->id,
            'reset_by'     => auth()->id(),
            'method'       => 'admin',
            'channel'      => $result['channel'],
            'sms_sent'     => $result['sms_sent'],
            'sms_error'    => $result['sms_error'],
            'email_sent'   => $result['email_sent'],
            'email_error'  => $result['email_error'],
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->userAgent(),
        ]);

        $message = "Password for {$user->name} has been reset. New password: {$newPassword}";

        if ($result['sms_sent'] && $result['email_sent']) {
            $message .= " Sent via SMS and Email.";
        } elseif ($result['sms_sent']) {
            $message .= " Sent via SMS.";
        } elseif ($result['email_sent']) {
            $message .= " Sent via Email.";
        } else {
            $message .= " Failed to send. SMS: {$result['sms_error']}. Email: {$result['email_error']}";
        }

        return back()->with('success', $message);
    }

    // ── Show Change Password Form ────────────────────────────────
    public function showChangePassword()
    {
        return view('staff.change-password');
    }

    // ── Update Own Password ──────────────────────────────────────
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update([
            'password'      => Hash::make($validated['password']),
            'temp_password' => null, // Clear temp password since user set their own
        ]);

        return back()->with('success', 'Password changed successfully.');
    }

    // ── Staff Performance ────────────────────────────────────────
    public function performance(User $user)
    {
        $customersCount = Customer::where('relationship_officer_id', $user->id)->count();
        $activeCustomers = Customer::where('relationship_officer_id', $user->id)
            ->where('status', 'active')->count();

        $loansCount = Loan::where('relationship_officer_id', $user->id)->count();
        $activeLoans = Loan::where('relationship_officer_id', $user->id)
            ->whereIn('status', ['disbursed', 'active'])->count();
        $totalDisbursed = Loan::where('relationship_officer_id', $user->id)
            ->whereIn('status', ['disbursed', 'active', 'completed'])
            ->sum('principal_amount');
        $totalCollected = LoanRepayment::where('received_by', $user->id)
            ->where('status', 'confirmed')
            ->sum('amount');

        $recentLoans = Loan::with('customer')
            ->where('relationship_officer_id', $user->id)
            ->latest()
            ->limit(10)
            ->get();

        return view('staff.performance', compact(
            'user', 'customersCount', 'activeCustomers',
            'loansCount', 'activeLoans', 'totalDisbursed', 'totalCollected',
            'recentLoans'
        ));
    }

    // ══════════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ══════════════════════════════════════════════════════════════

    /**
     * Send credentials to user via SMS and/or Email.
     * Returns array with delivery status.
     */
    private function sendCredentials(User $user, string $password, bool $trySms, string $type): array
    {
        $smsSent = false;
        $smsError = null;
        $emailSent = false;
        $emailError = null;

        // Try SMS first
        if ($trySms && $user->phone_number) {
            try {
                $at = new AfricasTalkingService();
                $message = $type === 'welcome'
                    ? "Welcome to Mweela Cash Capital! Your login credentials:\nEmail: {$user->email}\nPassword: {$password}\nPlease change your password after first login."
                    : "Your Mweela Cash Capital password has been reset.\nNew password: {$password}\nPlease change your password after login.";

                $smsLog = SmsLog::create([
                    'customer_id'  => null,
                    'loan_id'      => null,
                    'phone_number' => $user->phone_number,
                    'message'      => $message,
                    'message_type' => 'custom',
                    'status'       => 'pending',
                    'created_by'   => auth()->id(),
                ]);

                $smsSent = $at->send($smsLog);
                $smsLog->refresh();

                if (!$smsSent) {
                    $smsError = $smsLog->failure_reason ?? 'Unknown SMS error';
                }
            } catch (\Throwable $e) {
                $smsError = $e->getMessage();
                Log::error('Failed to send credentials SMS', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } elseif ($trySms && !$user->phone_number) {
            $smsError = 'No phone number on file';
        }

        // Fallback to email if SMS failed or no phone
        if (!$smsSent && $user->email) {
            try {
                $subject = $type === 'welcome'
                    ? 'Welcome to Mweela Cash Capital - Your Login Credentials'
                    : 'Your Mweela Cash Capital Password Has Been Reset';

                Mail::send('emails.staff-credentials', [
                    'name'     => $user->name,
                    'email'    => $user->email,
                    'password' => $password,
                    'isReset'  => $type === 'reset',
                ], function ($message) use ($user, $subject) {
                    $message->to($user->email, $user->name)
                            ->subject($subject);
                });

                $emailSent = true;
            } catch (\Throwable $e) {
                $emailError = $e->getMessage();
                Log::error('Failed to send credentials email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } elseif (!$user->email) {
            $emailError = 'No email on file';
        }

        // Also send email if SMS succeeded (dual delivery for security)
        if ($smsSent && $user->email) {
            try {
                $subject = $type === 'welcome'
                    ? 'Welcome to Mweela Cash Capital - Your Login Credentials'
                    : 'Your Mweela Cash Capital Password Has Been Reset';

                Mail::send('emails.staff-credentials', [
                    'name'     => $user->name,
                    'email'    => $user->email,
                    'password' => $password,
                    'isReset'  => $type === 'reset',
                ], function ($message) use ($user, $subject) {
                    $message->to($user->email, $user->name)
                            ->subject($subject);
                });

                $emailSent = true;
            } catch (\Throwable $e) {
                $emailError = $e->getMessage();
            }
        }

        $channel = [];
        if ($smsSent) $channel[] = 'sms';
        if ($emailSent) $channel[] = 'email';

        return [
            'sms_sent'    => $smsSent,
            'sms_error'   => $smsError,
            'email_sent'  => $emailSent,
            'email_error' => $emailError,
            'channel'     => implode('+', $channel) ?: null,
        ];
    }
}
