<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreBankAccountRequest;
use App\Http\Requests\Finance\UpdateBankAccountRequest;
use App\Models\FinancialBankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BankAccountController extends Controller
{
    public function index(): View
    {
        return view('finance.bank-accounts.index', [
            'accounts' => FinancialBankAccount::query()
                ->orderBy('sort_order')
                ->latest('id')
                ->get(),
        ]);
    }

    public function store(StoreBankAccountRequest $request): RedirectResponse
    {
        FinancialBankAccount::query()->create($request->validated());

        return redirect()->route('finance.bank-accounts.index')->with('success', 'حساب بانکی اضافه شد.');
    }

    public function update(UpdateBankAccountRequest $request, FinancialBankAccount $bankAccount): RedirectResponse
    {
        $bankAccount->update($request->validated());

        return redirect()->route('finance.bank-accounts.index')->with('success', 'اطلاعات حساب بانکی به‌روزرسانی شد.');
    }

    public function destroy(FinancialBankAccount $bankAccount): RedirectResponse
    {
        $bankAccount->delete();

        return redirect()->route('finance.bank-accounts.index')->with('success', 'حساب بانکی از لیست فعال حذف شد.');
    }
}
