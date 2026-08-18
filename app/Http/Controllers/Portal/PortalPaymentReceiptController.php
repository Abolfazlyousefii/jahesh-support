<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Finance\SubmitPaymentReceiptAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StorePaymentReceiptRequest;
use App\Models\CustomerPaymentReceipt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PortalPaymentReceiptController extends Controller
{
    public function store(
        StorePaymentReceiptRequest $request,
        SubmitPaymentReceiptAction $action,
    ): RedirectResponse {
        $action->execute(
            $request->user('customer'),
            $request->validated(),
            $request->file('receipt'),
        );

        return redirect()->route('portal.finance.index')
            ->with('success', 'فیش شما ثبت شد و پس از بررسی واحد مالی روی مانده حساب اعمال می‌شود.');
    }

    public function file(Request $request, CustomerPaymentReceipt $receipt): BinaryFileResponse
    {
        $customer = $request->user('customer');
        abort_unless($receipt->customer_id === $customer->id, 404);
        abort_unless(Storage::disk('local')->exists($receipt->receipt_path), 404);

        return response()->file(Storage::disk('local')->path($receipt->receipt_path), [
            'Content-Type' => $receipt->mime_type ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
