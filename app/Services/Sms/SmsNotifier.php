<?php

namespace App\Services\Sms;

use App\Models\Customer;
use App\Models\CustomerPaymentReceipt;
use App\Models\SmsSetting;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use Throwable;

final class SmsNotifier
{
    public function __construct(private readonly SmsService $sms) {}

    public function ticketCreated(Ticket $ticket): void
    {
        $this->safely(function () use ($ticket) {
            $ticket->loadMissing(['customer.primaryPhone']);

            if ($phone = $this->customerPhone($ticket->customer)) {
                $this->sms->queue(
                    'ticket_created_customer',
                    $phone,
                    [$ticket->customer->name, $ticket->id],
                    $ticket->getMorphClass(),
                    $ticket->id,
                );
            }

            foreach ($this->internalRecipients() as $user) {
                $this->sms->queue(
                    'ticket_created_staff',
                    $user->phone,
                    [$user->name, $ticket->id, $ticket->customer->name],
                    $ticket->getMorphClass(),
                    $ticket->id,
                );
            }
        });
    }

    public function ticketAssigned(Ticket $ticket): void
    {
        $this->safely(function () use ($ticket) {
            $ticket->loadMissing(['assignee', 'customer']);

            if ($ticket->assignee === null) {
                return;
            }

            $this->sms->queue(
                'ticket_assigned',
                $ticket->assignee->phone,
                [$ticket->assignee->name, $ticket->id, $ticket->customer->name],
                $ticket->getMorphClass(),
                $ticket->id,
            );
        });
    }

    public function ticketStaffReply(Ticket $ticket): void
    {
        $this->safely(function () use ($ticket) {
            $ticket->loadMissing(['customer.primaryPhone']);

            if ($phone = $this->customerPhone($ticket->customer)) {
                $this->sms->queue(
                    'ticket_staff_reply',
                    $phone,
                    [$ticket->customer->name, $ticket->id],
                    $ticket->getMorphClass(),
                    $ticket->id,
                );
            }
        });
    }

    public function ticketCustomerReply(Ticket $ticket): void
    {
        $this->safely(function () use ($ticket) {
            $ticket->loadMissing(['assignee', 'customer']);

            if ($ticket->assignee !== null) {
                $this->sms->queue(
                    'ticket_customer_reply',
                    $ticket->assignee->phone,
                    [$ticket->assignee->name, $ticket->id, $ticket->customer->name],
                    $ticket->getMorphClass(),
                    $ticket->id,
                );

                return;
            }

            foreach ($this->internalRecipients() as $user) {
                $this->sms->queue(
                    'ticket_customer_reply',
                    $user->phone,
                    [$user->name, $ticket->id, $ticket->customer->name],
                    $ticket->getMorphClass(),
                    $ticket->id,
                );
            }
        });
    }

    public function ticketResolved(Ticket $ticket): void
    {
        $this->safely(function () use ($ticket) {
            $ticket->loadMissing(['customer.primaryPhone']);

            if ($phone = $this->customerPhone($ticket->customer)) {
                $this->sms->queue(
                    'ticket_resolved',
                    $phone,
                    [$ticket->customer->name, $ticket->id],
                    $ticket->getMorphClass(),
                    $ticket->id,
                );
            }
        });
    }

    public function taskAssigned(Task $task, ?User $actor = null): void
    {
        $this->safely(function () use ($task, $actor) {
            $task->loadMissing('assignee');

            if ($task->assignee === null || ($actor !== null && $task->assignee_id === $actor->id)) {
                return;
            }

            $this->sms->queue(
                'task_assigned',
                $task->assignee->phone,
                [$task->assignee->name, $task->id],
                $task->getMorphClass(),
                $task->id,
            );
        });
    }

    public function receiptSubmitted(CustomerPaymentReceipt $receipt): void
    {
        $this->safely(function () use ($receipt) {
            $receipt->loadMissing('customer');

            foreach ($this->internalRecipients() as $user) {
                $this->sms->queue(
                    'receipt_submitted',
                    $user->phone,
                    [$user->name, $receipt->customer->name, number_format($receipt->amount)],
                    $receipt->getMorphClass(),
                    $receipt->id,
                );
            }
        });
    }

    public function receiptApproved(CustomerPaymentReceipt $receipt): void
    {
        $this->safely(fn () => $this->receiptCustomerNotification($receipt, 'receipt_approved'));
    }

    public function receiptRejected(CustomerPaymentReceipt $receipt): void
    {
        $this->safely(fn () => $this->receiptCustomerNotification($receipt, 'receipt_rejected'));
    }

    private function receiptCustomerNotification(CustomerPaymentReceipt $receipt, string $pattern): void
    {
        $receipt->loadMissing(['customer.primaryPhone']);

        if ($phone = $this->customerPhone($receipt->customer)) {
            $this->sms->queue(
                $pattern,
                $phone,
                [$receipt->customer->name, number_format($receipt->amount)],
                $receipt->getMorphClass(),
                $receipt->id,
            );
        }
    }

    private function customerPhone(Customer $customer): ?string
    {
        return $customer->primaryPhone?->phone
            ?? $customer->phones()->orderByDesc('is_primary')->value('phone');
    }

    private function internalRecipients()
    {
        $ids = array_values(array_filter((array) SmsSetting::current()->internal_recipient_user_ids));

        return User::query()
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->get(['id', 'name', 'phone']);
    }

    private function safely(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
