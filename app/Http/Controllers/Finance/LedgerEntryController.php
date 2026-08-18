<?php

namespace App\Http\Controllers\Finance;

use App\Actions\Finance\VoidLedgerEntryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\VoidLedgerEntryRequest;
use App\Models\CustomerLedgerEntry;
use Illuminate\Http\RedirectResponse;

class LedgerEntryController extends Controller
{
    public function void(
        VoidLedgerEntryRequest $request,
        CustomerLedgerEntry $entry,
        VoidLedgerEntryAction $action,
    ): RedirectResponse {
        $action->execute($entry, $request->user(), $request->validated('void_reason'));

        return redirect()->route('finance.customers.show', $entry->customer_id)
            ->with('success', 'سند مالی ابطال شد و دیگر در مانده حساب محاسبه نمی‌شود.');
    }
}
