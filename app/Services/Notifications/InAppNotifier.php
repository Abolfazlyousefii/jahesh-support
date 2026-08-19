<?php

namespace App\Services\Notifications;

use App\Models\Customer;
use App\Models\CustomerPaymentReceipt;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\PersonalNotification;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

final class InAppNotifier
{
    public function ticketCreated(Ticket $ticket): void
    {
        $this->safely(function () use ($ticket): void {
            $ticket->loadMissing('customer');

            foreach ($this->usersWithPermission('tickets.view_all') as $user) {
                $this->notify(
                    $user,
                    'ticket.created',
                    'تیکت جدید ثبت شد',
                    "{$ticket->customer->name} تیکت #{$ticket->id} را ثبت کرد: {$ticket->subject}",
                    route('tickets.show', $ticket, false),
                    'amber',
                    $ticket,
                );
            }
        });
    }

    public function ticketAssigned(Ticket $ticket, ?User $actor = null): void
    {
        $this->safely(function () use ($ticket, $actor): void {
            $ticket->loadMissing(['assignee', 'customer']);

            if ($ticket->assignee === null || $ticket->assignee->id === $actor?->id) {
                return;
            }

            $this->notify(
                $ticket->assignee,
                'ticket.assigned',
                'تیکت به شما ارجاع شد',
                "تیکت #{$ticket->id} مشتری {$ticket->customer->name} به شما ارجاع شد.",
                route('tickets.show', $ticket, false),
                'blue',
                $ticket,
            );
        });
    }

    public function ticketStaffReply(Ticket $ticket): void
    {
        $this->safely(function () use ($ticket): void {
            $ticket->loadMissing('customer');

            $this->notify(
                $ticket->customer,
                'ticket.staff_reply',
                'پاسخ جدید پشتیبانی',
                "برای تیکت #{$ticket->id} شما پاسخ جدیدی ثبت شد.",
                route('portal.tickets.show', $ticket, false),
                'blue',
                $ticket,
            );
        });
    }

    public function ticketCustomerReply(Ticket $ticket): void
    {
        $this->safely(function () use ($ticket): void {
            $ticket->loadMissing(['assignee', 'customer']);

            if ($ticket->assignee !== null) {
                $this->notify(
                    $ticket->assignee,
                    'ticket.customer_reply',
                    'پاسخ جدید مشتری',
                    "{$ticket->customer->name} به تیکت #{$ticket->id} پاسخ داد.",
                    route('tickets.show', $ticket, false),
                    'amber',
                    $ticket,
                );

                return;
            }

            foreach ($this->usersWithPermission('tickets.view_all') as $user) {
                $this->notify(
                    $user,
                    'ticket.customer_reply',
                    'پاسخ جدید در تیکت بدون مسئول',
                    "{$ticket->customer->name} به تیکت #{$ticket->id} پاسخ داد و این تیکت هنوز مسئول ندارد.",
                    route('tickets.show', $ticket, false),
                    'amber',
                    $ticket,
                );
            }
        });
    }

    public function ticketResolved(Ticket $ticket): void
    {
        $this->safely(function () use ($ticket): void {
            $ticket->loadMissing('customer');

            $this->notify(
                $ticket->customer,
                'ticket.resolved',
                'درخواست شما حل شد',
                "تیکت #{$ticket->id} با موضوع «{$ticket->subject}» به وضعیت حل‌شده رسید.",
                route('portal.tickets.show', $ticket, false),
                'green',
                $ticket,
            );
        });
    }

    public function taskAssigned(Task $task, ?User $actor = null): void
    {
        $this->safely(function () use ($task, $actor): void {
            $task->loadMissing('assignee');

            if ($task->assignee === null || $task->assignee->id === $actor?->id) {
                return;
            }

            $this->notify(
                $task->assignee,
                'task.assigned',
                'تسک جدید برای شما',
                "تسک #{$task->id} «{$task->title}» به شما واگذار شد.",
                route('tasks.show', $task, false),
                $task->priority->value === 'urgent' ? 'red' : 'blue',
                $task,
            );
        });
    }

    public function receiptSubmitted(CustomerPaymentReceipt $receipt): void
    {
        $this->safely(function () use ($receipt): void {
            $receipt->loadMissing('customer');

            foreach ($this->usersWithPermission('finance.review_payments') as $user) {
                $this->notify(
                    $user,
                    'finance.receipt_submitted',
                    'فیش جدید منتظر بررسی',
                    "{$receipt->customer->name} فیش ".number_format($receipt->amount).' تومان ثبت کرد.',
                    route('finance.receipts.show', $receipt, false),
                    'violet',
                    $receipt,
                );
            }
        });
    }

    public function receiptApproved(CustomerPaymentReceipt $receipt): void
    {
        $this->receiptReviewed($receipt, true);
    }

    public function receiptRejected(CustomerPaymentReceipt $receipt): void
    {
        $this->receiptReviewed($receipt, false);
    }

    private function receiptReviewed(CustomerPaymentReceipt $receipt, bool $approved): void
    {
        $this->safely(function () use ($receipt, $approved): void {
            $receipt->loadMissing('customer');

            $this->notify(
                $receipt->customer,
                $approved ? 'finance.receipt_approved' : 'finance.receipt_rejected',
                $approved ? 'فیش پرداخت تأیید شد' : 'فیش پرداخت رد شد',
                $approved
                    ? 'فیش '.number_format($receipt->amount).' تومان تأیید و در حساب مالی شما ثبت شد.'
                    : 'فیش '.number_format($receipt->amount).' تومان تأیید نشد. برای مشاهده جزئیات وارد بخش مالی شوید.',
                route('portal.finance.index', [], false),
                $approved ? 'green' : 'red',
                $receipt,
            );
        });
    }

    /**
     * @return Collection<int, User>
     */
    private function usersWithPermission(string $permission): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->permission($permission)
            ->get();
    }

    private function notify(
        User|Customer|null $recipient,
        string $event,
        string $title,
        string $message,
        string $url,
        string $tone,
        object $subject,
    ): void {
        if ($recipient === null) {
            return;
        }

        $recipient->notify(new PersonalNotification(
            event: $event,
            title: $title,
            message: $message,
            url: $url,
            tone: $tone,
            subjectType: method_exists($subject, 'getMorphClass') ? $subject->getMorphClass() : $subject::class,
            subjectId: isset($subject->id) ? (int) $subject->id : null,
        ));
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
