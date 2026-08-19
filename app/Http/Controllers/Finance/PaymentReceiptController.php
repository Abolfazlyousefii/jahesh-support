<?php

namespace App\Http\Controllers\Finance;

use App\Actions\Finance\ReviewPaymentReceiptAction;
use App\Enums\PaymentReceiptStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\RejectPaymentReceiptRequest;
use App\Models\CustomerPaymentReceipt;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaymentReceiptController extends Controller
{
    public function index(Request $request): View
    {
        $statusParam = $request->string('status')->toString();
        $status = $statusParam === 'all' ? null : PaymentReceiptStatus::tryFrom($statusParam);
        if ($statusParam === '') {
            $status = PaymentReceiptStatus::Pending;
        }
        $search = trim($request->string('q')->toString());

        $receipts = CustomerPaymentReceipt::query()
            ->with(['customer.primaryPhone', 'bankAccount', 'reviewer', 'ledgerEntry'])
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search) {
                $query->where('tracking_code', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn (Builder $customer) => $customer
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate(app(SettingsService::class)->paginationPerPage())
            ->withQueryString();

        return view('finance.receipts.index', [
            'receipts' => $receipts,
            'status' => $status,
            'statuses' => PaymentReceiptStatus::cases(),
            'search' => $search,
            'pendingCount' => CustomerPaymentReceipt::query()->pending()->count(),
        ]);
    }

    public function show(CustomerPaymentReceipt $receipt): View
    {
        $receipt->load(['customer.phones', 'bankAccount', 'reviewer', 'ledgerEntry.creator']);

        return view('finance.receipts.show', compact('receipt'));
    }

    public function file(CustomerPaymentReceipt $receipt): BinaryFileResponse
    {
        abort_unless(Storage::disk('local')->exists($receipt->receipt_path), 404);

        return response()->file(Storage::disk('local')->path($receipt->receipt_path), [
            'Content-Type' => $receipt->mime_type ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function approve(
        CustomerPaymentReceipt $receipt,
        ReviewPaymentReceiptAction $action,
    ): RedirectResponse {
        $action->approve($receipt, request()->user());

        return redirect()->route('finance.receipts.show', $receipt)
            ->with('success', 'فیش تأیید شد و سند بستانکار به‌صورت خودکار ثبت شد.');
    }

    public function reject(
        RejectPaymentReceiptRequest $request,
        CustomerPaymentReceipt $receipt,
        ReviewPaymentReceiptAction $action,
    ): RedirectResponse {
        $action->reject($receipt, $request->user(), $request->validated('rejection_reason'));

        return redirect()->route('finance.receipts.show', $receipt)
            ->with('success', 'فیش رد شد. دلیل رد برای مشتری قابل مشاهده است.');
    }
}
