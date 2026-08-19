<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\FinancialBankAccount;
use App\Services\Finance\CustomerFinanceService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalFinanceController extends Controller
{
    public function __invoke(Request $request, CustomerFinanceService $finance): View
    {
        $customer = $request->user('customer');

        return view('portal.finance.index', [
            'customer' => $customer,
            'summary' => $finance->summary($customer),
            'entries' => $customer->ledgerEntries()
                ->effective()
                ->latest('entry_date')
                ->latest('id')
                ->paginate(app(SettingsService::class)->paginationPerPage(), ['*'], 'ledger_page'),
            'receipts' => $customer->paymentReceipts()
                ->with(['bankAccount', 'ledgerEntry'])
                ->latest()
                ->limit(10)
                ->get(),
            'bankAccounts' => FinancialBankAccount::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
        ]);
    }
}
