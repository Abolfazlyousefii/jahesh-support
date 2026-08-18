<?php

namespace App\Http\Controllers\Portal;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Ticket;
use App\Services\Finance\CustomerFinanceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalDashboardController extends Controller
{
    public function __invoke(Request $request, CustomerFinanceService $finance): View
    {
        $customer = $request->user('customer');
        $activeTicketsQuery = $this->activeTicketsQuery($customer);

        return view('portal.dashboard', [
            'customer' => $customer,
            'activeTickets' => (clone $activeTicketsQuery)->limit(8)->get(),
            'activeTicketCount' => (clone $activeTicketsQuery)->count(),
            'financeSummary' => $finance->summary($customer),
            'unreadTickets' => $this->unreadTicketsCount($customer),
        ]);
    }

    public function activeTickets(Request $request): JsonResponse
    {
        $customer = $request->user('customer');
        $activeTicketsQuery = $this->activeTicketsQuery($customer);
        $activeTickets = (clone $activeTicketsQuery)->limit(8)->get();

        return response()->json([
            'html' => view('portal.partials.active-ticket-list', compact('activeTickets'))->render(),
            'active_count' => (clone $activeTicketsQuery)->count(),
            'unread_count' => $this->unreadTicketsCount($customer),
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    private function activeTicketsQuery(Customer $customer): Builder
    {
        return Ticket::query()
            ->where('customer_id', $customer->id)
            ->whereIn('status', $this->activeStatusValues())
            ->with('latestPublicMessage.author')
            ->orderByRaw('CASE WHEN last_staff_message_at IS NOT NULL AND (customer_last_read_at IS NULL OR last_staff_message_at > customer_last_read_at) THEN 0 ELSE 1 END')
            ->latest('updated_at');
    }

    /** @return array<int, string> */
    private function activeStatusValues(): array
    {
        return [
            TicketStatus::New->value,
            TicketStatus::InReview->value,
            TicketStatus::InProgress->value,
            TicketStatus::WaitingCustomer->value,
        ];
    }

    private function unreadTicketsCount(Customer $customer): int
    {
        return $customer->tickets()
            ->whereIn('status', $this->activeStatusValues())
            ->whereNotNull('last_staff_message_at')
            ->where(function (Builder $query) {
                $query->whereNull('customer_last_read_at')
                    ->orWhereColumn('last_staff_message_at', '>', 'customer_last_read_at');
            })
            ->count();
    }
}
