<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreBankAccountRequest;
use App\Http\Requests\Finance\UpdateBankAccountRequest;
use App\Models\FinancialBankAccount;
use App\Services\Activity\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(StoreBankAccountRequest $request, ActivityLogger $activity): RedirectResponse
    {
        $bankAccount = FinancialBankAccount::query()->create($request->validated());

        $activity->record(
            'finance.bank_account_created',
            $bankAccount,
            $request->user(),
            'حساب بانکی جدید برای دریافت پرداخت‌های مشتریان ثبت شد.',
            new: $this->auditSnapshot($bankAccount),
        );

        return redirect()->route('finance.bank-accounts.index')->with('success', 'حساب بانکی اضافه شد.');
    }

    public function update(
        UpdateBankAccountRequest $request,
        FinancialBankAccount $bankAccount,
        ActivityLogger $activity,
    ): RedirectResponse {
        $before = $this->auditSnapshot($bankAccount);

        $bankAccount->update($request->validated());
        $bankAccount->refresh();

        $changes = $activity->changed($before, $this->auditSnapshot($bankAccount));

        if ($changes['old'] !== [] || $changes['new'] !== []) {
            $activity->record(
                'finance.bank_account_updated',
                $bankAccount,
                $request->user(),
                'اطلاعات حساب بانکی ویرایش شد.',
                $changes['old'],
                $changes['new'],
            );
        }

        return redirect()->route('finance.bank-accounts.index')->with('success', 'اطلاعات حساب بانکی به‌روزرسانی شد.');
    }

    public function destroy(
        Request $request,
        FinancialBankAccount $bankAccount,
        ActivityLogger $activity,
    ): RedirectResponse {
        $activity->record(
            'finance.bank_account_deleted',
            $bankAccount,
            $request->user(),
            'حساب بانکی از لیست فعال حذف شد.',
            old: $this->auditSnapshot($bankAccount),
        );

        $bankAccount->delete();

        return redirect()->route('finance.bank-accounts.index')->with('success', 'حساب بانکی از لیست فعال حذف شد.');
    }

    /** @return array<string,mixed> */
    private function auditSnapshot(FinancialBankAccount $bankAccount): array
    {
        return [
            'bank_name' => $bankAccount->bank_name,
            'account_holder' => $bankAccount->account_holder,
            'card_number' => $this->maskedIdentifier($bankAccount->card_number),
            'iban' => $this->maskedIdentifier($bankAccount->iban),
            'account_number' => $this->maskedIdentifier($bankAccount->account_number),
            'is_active' => $bankAccount->is_active,
            'sort_order' => $bankAccount->sort_order,
        ];
    }

    private function maskedIdentifier(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        $value = (string) $value;

        return '•••• '.mb_substr($value, -4);
    }
}
