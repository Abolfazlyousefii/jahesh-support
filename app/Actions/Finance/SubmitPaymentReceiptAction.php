<?php

namespace App\Actions\Finance;

use App\Enums\PaymentReceiptStatus;
use App\Models\Customer;
use App\Models\CustomerPaymentReceipt;
use App\Services\Activity\ActivityLogger;
use App\Services\Notifications\InAppNotifier;
use App\Services\Sms\SmsNotifier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SubmitPaymentReceiptAction
{
    public function __construct(
        private readonly SmsNotifier $sms,
        private readonly InAppNotifier $notifications,
        private readonly ActivityLogger $activity,
    ) {}

    public function execute(Customer $customer, array $data, UploadedFile $file): CustomerPaymentReceipt
    {
        $directory = 'finance/receipts/'.$customer->id.'/'.now()->format('Y/m');
        $path = $file->store($directory, 'local');

        try {
            $receipt = DB::transaction(fn () => CustomerPaymentReceipt::query()->create([
                'customer_id' => $customer->id,
                'bank_account_id' => $data['bank_account_id'],
                'amount' => $data['amount'],
                'paid_at' => $data['paid_at'],
                'tracking_code' => $data['tracking_code'] ?? null,
                'receipt_path' => $path,
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                'mime_type' => $file->getMimeType(),
                'customer_note' => $data['customer_note'] ?? null,
                'status' => PaymentReceiptStatus::Pending,
            ]));
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        $this->activity->record(
            'finance.receipt_submitted',
            $receipt,
            $customer,
            'مشتری فیش پرداخت جدید ثبت کرد.',
            new: $this->activity->snapshot($receipt, [
                'bank_account_id', 'amount', 'paid_at', 'tracking_code', 'status',
            ]),
        );

        $this->sms->receiptSubmitted($receipt);
        $this->notifications->receiptSubmitted($receipt);

        return $receipt;
    }
}
