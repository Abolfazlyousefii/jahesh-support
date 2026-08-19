<?php

namespace App\Http\Controllers\Finance;

use App\Enums\LedgerEntryType;
use App\Enums\PaymentReceiptStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\CustomerPaymentReceipt;
use App\Support\PhoneNormalizer;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());
        $normalizedSearch = PhoneNormalizer::normalize($search);

        $customers = Customer::query()
            ->with('primaryPhone')
            ->withSum([
                'ledgerEntries as debit_total' => fn (Builder $query) => $query
                    ->effective()
                    ->where('type', LedgerEntryType::Debit),
            ], 'amount')
            ->withSum([
                'ledgerEntries as credit_total' => fn (Builder $query) => $query
                    ->effective()
                    ->where('type', LedgerEntryType::Credit),
            ], 'amount')
            ->withCount([
                'paymentReceipts as pending_receipts_count' => fn (Builder $query) => $query
                    ->where('status', PaymentReceiptStatus::Pending),
            ])
            ->when($search !== '', function (Builder $query) use ($search, $normalizedSearch) {
                $query->where(function (Builder $query) use ($search, $normalizedSearch) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->when($normalizedSearch !== '', fn (Builder $query) => $query->orWhereHas(
                            'phones',
                            fn (Builder $phones) => $phones->where('phone', 'like', "%{$normalizedSearch}%"),
                        ));
                });
            })
            ->latest()
            ->paginate(app(SettingsService::class)->paginationPerPage())
            ->withQueryString();

        $debit = (int) CustomerLedgerEntry::query()->effective()->where('type', LedgerEntryType::Debit)->sum('amount');
        $credit = (int) CustomerLedgerEntry::query()->effective()->where('type', LedgerEntryType::Credit)->sum('amount');

        return view('finance.index', [
            'customers' => $customers,
            'search' => $search,
            'metrics' => [
                'debit' => $debit,
                'credit' => $credit,
                'net' => $debit - $credit,
                'pending_receipts' => CustomerPaymentReceipt::query()->pending()->count(),
                'pending_amount' => (int) CustomerPaymentReceipt::query()->pending()->sum('amount'),
            ],
        ]);
    }
}
