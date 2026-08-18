<?php

namespace App\Http\Controllers\Finance;

use App\Actions\Finance\CreateLedgerEntryAction;
use App\Enums\LedgerEntryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreLedgerEntryRequest;
use App\Models\Customer;
use App\Services\Finance\CustomerFinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CustomerFinanceController extends Controller
{
    public function show(Customer $customer, CustomerFinanceService $finance): View
    {
        $customer->load('phones');

        $entries = $customer->ledgerEntries()
            ->with(['creator', 'voider', 'paymentReceipt'])
            ->latest('entry_date')
            ->latest('id')
            ->paginate(20, ['*'], 'ledger_page');

        $receipts = $customer->paymentReceipts()
            ->with(['bankAccount', 'reviewer', 'ledgerEntry'])
            ->latest()
            ->limit(10)
            ->get();

        return view('finance.customer', [
            'customer' => $customer,
            'summary' => $finance->summary($customer),
            'entries' => $entries,
            'receipts' => $receipts,
            'entryTypes' => LedgerEntryType::cases(),
        ]);
    }

    public function storeEntry(
        StoreLedgerEntryRequest $request,
        Customer $customer,
        CreateLedgerEntryAction $action,
    ): RedirectResponse {
        $action->execute($customer, $request->validated(), $request->user());

        return redirect()->route('finance.customers.show', $customer)
            ->with('success', 'سند مالی با موفقیت ثبت شد.');
    }
}
