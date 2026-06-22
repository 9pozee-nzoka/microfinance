<?php

namespace App\Traits;

use App\Services\ReportExportService;
use Illuminate\Http\Request;

/**
 * Shared report export helpers for admin and staff report controllers.
 */
trait ReportExportTrait
{
    /**
     * Dispatch export based on the requested format.
     */
    protected function handleReportExport(Request $request, string $reportName, array $data)
    {
        $format = in_array($request->export, ['pdf', 'excel', 'csv', 'word']) ? $request->export : 'csv';
        $safeName = $this->exportService->safeFileName($reportName);
        $dateSuffix = now()->format('Ymd_His');

        $headers = $this->exportHeaders($reportName);
        $rows = $this->exportRows($reportName, $data);

        $viewData = array_merge($data, [
            'reportTitle' => str_replace('_', ' ', $reportName),
            'reportName'  => $reportName,
            'headers'     => $headers,
            'rows'        => $rows,
        ]);

        switch ($format) {
            case 'pdf':
                return $this->exportService->downloadPdf(
                    'reports.exports.pdf-master',
                    $viewData,
                    "{$safeName}_{$dateSuffix}.pdf"
                );

            case 'excel':
                return $this->exportService->downloadExcel(
                    $headers,
                    $rows,
                    "{$safeName}_{$dateSuffix}.xlsx",
                    ucwords(str_replace('_', ' ', $reportName))
                );

            case 'word':
                return $this->exportService->downloadWord(
                    $headers,
                    $rows,
                    "{$safeName}_{$dateSuffix}.docx",
                    ucwords(str_replace('_', ' ', $reportName))
                );

            case 'csv':
            default:
                return $this->exportService->downloadCsv($headers, $rows, "{$safeName}_{$dateSuffix}.csv");
        }
    }

    /**
     * Column headers for each report export.
     */
    protected function exportHeaders(string $reportName): array
    {
        return match ($reportName) {
            'outstanding_loan_balances' => ['#', 'Loan No.', 'Customer', 'Phone', 'Product', 'Principal', 'Outstanding', 'Total Paid', 'Arrears', 'Days Arrears', 'Risk', 'Next Due', 'Branch'],
            'portfolio_at_risk' => ['#', 'Loan No.', 'Customer', 'Phone', 'Product', 'Outstanding', 'Arrears', 'Days in Arrears', 'Risk', 'Branch', 'Officer'],
            'disbursed_loans' => ['#', 'Loan No.', 'Customer', 'Phone', 'Principal', 'Total Repayable', 'Product', 'Method', 'Disbursed Date', 'Branch', 'Officer'],
            'loan_collections' => ['#', 'Receipt', 'Customer', 'Phone', 'Amount', 'Principal', 'Interest', 'Penalty', 'Method', 'Date', 'Received By'],
            'prepayment_analytics' => ['#', 'Loan No.', 'Customer', 'Amount', 'Type', 'Payment Method', 'Date'],
            'daily_activity' => ['#', 'Txn No.', 'Customer', 'Type', 'Direction', 'Amount', 'Status', 'Date'],
            'loans_due' => ['#', 'Due Date', 'Customer', 'Phone', 'Loan No.', 'Installment', 'Amount Due', 'Branch'],
            'new_loans' => ['#', 'Loan No.', 'Customer', 'Phone', 'Principal', 'Product', 'Status', 'Applied Date', 'Branch', 'Officer'],
            'pending_disbursements' => ['#', 'Loan No.', 'Customer', 'Phone', 'Principal', 'Product', 'Approved Date', 'Branch', 'Officer'],
            'officer_performance' => ['Officer', 'Designation', 'Loans Created', 'Disbursed', 'Collections Count', 'Collections Amount', 'Active Loans', 'OLB', 'Arrears'],
            'branch_performance' => ['Branch', 'Customers', 'Active Customers', 'Active Loans', 'OLB', 'Arrears', 'Disbursed Period', 'Collected Period'],
            'income_statement' => ['Month', 'Interest Income', 'Fees', 'Penalties', 'Total Income'],
            'transaction_ledger' => ['#', 'Txn No.', 'Customer', 'Phone', 'Type', 'Direction', 'Source', 'Amount', 'Status', 'Date'],
            'customer_register' => ['#', 'Customer No.', 'Full Name', 'Phone', 'ID No.', 'Branch', 'Officer', 'Employment', 'Monthly Income', 'Savings', 'Credit Score', 'Status', 'Joined'],
            'credit_score_distribution' => ['Band', 'Count', 'Average Limit'],
            'loan_arrears' => ['#', 'Full Name', 'Phone Number', 'Branch', 'Principal Amount', 'Interest Amount', 'OLB', 'Arrears', 'Over Due Days', 'Borrow Date', 'New/Repeat', 'Business Type', 'Guarantor Name', 'Guarantor Phone'],
            'loan_arrears_summary' => ['Dimension', 'Category', 'Count', 'OLB', 'Arrears'],
            'loan_dues_summary' => ['Due Date', 'Count', 'Amount Due'],
            default => ['#', 'Data'],
        };
    }

    /**
     * Build export rows for each report.
     */
    protected function exportRows(string $reportName, array $data): array
    {
        $rows = [];
        $index = 1;

        switch ($reportName) {
            case 'outstanding_loan_balances':
                foreach ($data['loans'] as $loan) {
                    $rows[] = [
                        $index++, $loan->loan_number, $loan->customer?->full_name, $loan->customer?->phone_number,
                        $loan->product?->name, $loan->principal_amount, $loan->outstanding_balance, $loan->total_paid,
                        $loan->arrears_amount, $loan->days_in_arrears, $loan->risk_category,
                        $loan->next_due_date?->format('d M Y'), $loan->branch?->name,
                    ];
                }
                break;

            case 'portfolio_at_risk':
                foreach ($data['loans'] as $loan) {
                    $rows[] = [
                        $index++, $loan->loan_number, $loan->customer?->full_name, $loan->customer?->phone_number,
                        $loan->product?->name, $loan->outstanding_balance, $loan->arrears_amount,
                        $loan->days_in_arrears, $loan->risk_category, $loan->branch?->name, $loan->relationshipOfficer?->name,
                    ];
                }
                break;

            case 'disbursed_loans':
                foreach ($data['loans'] as $loan) {
                    $rows[] = [
                        $index++, $loan->loan_number, $loan->customer?->full_name, $loan->customer?->phone_number,
                        $loan->principal_amount, $loan->total_repayable, $loan->product?->name, $loan->disbursement_method,
                        $loan->disbursement_date?->format('d M Y'), $loan->branch?->name, $loan->relationshipOfficer?->name,
                    ];
                }
                break;

            case 'loan_collections':
                foreach ($data['repayments'] as $r) {
                    $rows[] = [
                        $index++, $r->receipt_number ?? $r->id, $r->customer?->full_name, $r->customer?->phone_number,
                        $r->amount, $r->principal_portion, $r->interest_portion, $r->penalty_portion,
                        $r->payment_method, $r->created_at?->format('d M Y H:i'), $r->receivedBy?->name,
                    ];
                }
                break;

            case 'prepayment_analytics':
                foreach ($data['earlyPayments'] as $r) {
                    $rows[] = [$index++, $r->loan?->loan_number, $r->customer?->full_name, $r->amount, 'Early Payment', $r->payment_method, $r->created_at?->format('d M Y')];
                }
                foreach ($data['closures'] as $loan) {
                    $rows[] = [$index++, $loan->loan_number, $loan->customer?->full_name ?? 'N/A', $loan->closure_payment_amount, ucwords(str_replace('_', ' ', $loan->closure_type)), $loan->closure_payment_method, $loan->updated_at?->format('d M Y')];
                }
                break;

            case 'daily_activity':
                foreach ($data['transactions'] as $t) {
                    $rows[] = [
                        $index++, $t->transaction_number, $t->customer?->full_name, $t->transaction_type,
                        $t->direction, $t->amount, $t->status, $t->created_at?->format('d M Y H:i'),
                    ];
                }
                break;

            case 'loans_due':
                foreach ($data['schedules'] as $s) {
                    $rows[] = [
                        $index++, $s->due_date?->format('d M Y'), $s->loan?->customer?->full_name,
                        $s->loan?->customer?->phone_number, $s->loan?->loan_number, $s->installment_number,
                        max(0, $s->total_amount - $s->total_paid), $s->loan?->branch?->name,
                    ];
                }
                break;

            case 'new_loans':
                foreach ($data['loans'] as $loan) {
                    $rows[] = [
                        $index++, $loan->loan_number, $loan->customer?->full_name, $loan->customer?->phone_number,
                        $loan->principal_amount, $loan->product?->name, $loan->status,
                        $loan->created_at?->format('d M Y'), $loan->branch?->name, $loan->relationshipOfficer?->name,
                    ];
                }
                break;

            case 'pending_disbursements':
                foreach ($data['loans'] as $loan) {
                    $rows[] = [
                        $index++, $loan->loan_number, $loan->customer?->full_name, $loan->customer?->phone_number,
                        $loan->principal_amount, $loan->product?->name, $loan->approved_at?->format('d M Y'),
                        $loan->branch?->name, $loan->relationshipOfficer?->name,
                    ];
                }
                break;

            case 'officer_performance':
                foreach ($data['officers'] as $o) {
                    $portfolio = $data['activePortfolio'][$o->id] ?? null;
                    $rows[] = [
                        $o->name, $o->designation, $o->loans_created, $o->total_disbursed,
                        $o->collections_count, $o->collections_amount, $portfolio?->active_loans ?? 0,
                        $portfolio?->olb ?? 0, $portfolio?->arrears ?? 0,
                    ];
                }
                break;

            case 'branch_performance':
                foreach ($data['branches'] as $b) {
                    $rows[] = [
                        $b->name, $b->customers_count, $b->active_customers_count, $b->active_loans_count,
                        $b->olb, $b->arrears, $b->disbursed_period, $b->collected_period,
                    ];
                }
                break;

            case 'income_statement':
                foreach ($data['trend'] as $t) {
                    $rows[] = [$t['month'], $t['interest'], $t['fees'], $t['penalty'], $t['interest'] + $t['fees'] + $t['penalty']];
                }
                break;

            case 'transaction_ledger':
                foreach ($data['transactions'] as $t) {
                    $rows[] = [
                        $index++, $t->transaction_number, $t->customer?->full_name, $t->customer?->phone_number,
                        $t->transaction_type, $t->direction, $t->source, $t->amount, $t->status,
                        $t->created_at?->format('d M Y H:i'),
                    ];
                }
                break;

            case 'customer_register':
                foreach ($data['customers'] as $c) {
                    $rows[] = [
                        $index++, $c->customer_number, $c->full_name, $c->phone_number, $c->id_number,
                        $c->branch?->name, $c->relationshipOfficer?->name, $c->employment_type,
                        $c->monthly_income, $c->savings_balance, $c->credit_score, $c->status,
                        $c->created_at?->format('d M Y'),
                    ];
                }
                break;

            case 'credit_score_distribution':
                foreach ($data['bands'] as $b) {
                    $rows[] = [$b['label'], $b['count'], round($b['avg_limit'], 2)];
                }
                break;

            case 'loan_arrears':
                foreach ($data['loans'] as $loan) {
                    $guarantor = $loan->guarantors->first();
                    $rows[] = [
                        $index++, $loan->customer?->full_name, $loan->customer?->phone_number, $loan->branch?->name,
                        $loan->principal_amount, $loan->interest_amount, $loan->outstanding_balance,
                        $loan->arrears_amount, $loan->days_in_arrears, $loan->disbursement_date?->format('d/m/Y'),
                        $loan->customer?->loans?->count() > 1 ? 'Repeat Loan' : 'New Loan',
                        $loan->customer?->business_type, $guarantor?->name, $guarantor?->phone_number,
                    ];
                }
                break;

            case 'loan_arrears_summary':
                foreach ($data['byBranch'] as $row) {
                    $rows[] = ['Branch', $row->branch, $row->count, $row->olb, $row->arrears];
                }
                foreach ($data['byOfficer'] as $row) {
                    $rows[] = ['Officer', $row->officer, $row->count, $row->olb, $row->arrears];
                }
                foreach ($data['byRisk'] as $row) {
                    $rows[] = ['Risk', ucfirst($row->risk_category), $row->count, $row->olb, $row->arrears];
                }
                break;

            case 'loan_dues_summary':
                foreach ($data['byDay'] as $row) {
                    $rows[] = [$row->due_date?->format('d M Y'), $row->count, $row->amount];
                }
                break;
        }

        return $rows;
    }
}
